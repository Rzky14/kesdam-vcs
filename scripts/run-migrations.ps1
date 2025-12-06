<#
  run-migrations.ps1

  Helper PowerShell script to run Laravel migrations on Windows when `php` is not on PATH.
  It will try these steps in order:
    1. Use `php` if available on PATH.
    2. Search common Laragon installation directories for php.exe and use the first match.
    3. If composer is available (on PATH) run `composer dump-autoload`.
       If composer.phar exists in project, run it via the discovered php.
    4. Run `php artisan migrate --force` using the discovered php executable.

  Usage:
    Open PowerShell in the project root (c:\laragon\www\kesdam-vcs) and run:
      .\scripts\run-migrations.ps1

  Note: You may need to run `Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass` to permit running this script for the session.
#>

param(
    [switch]$NoComposer
)

function Find-Php {
    # Check if php is on PATH
    try {
        $phpCmd = Get-Command php -ErrorAction Stop
        return $phpCmd.Path
    } catch {
        # not on PATH, search common Laragon locations
    }

    $candidates = @(
        "$env:ProgramFiles\Laragon\bin\php\*\php.exe",
        "$env:ProgramFiles(x86)\Laragon\bin\php\*\php.exe",
        "C:\laragon\bin\php\*\php.exe",
        "C:\Laragon\bin\php\*\php.exe"
    )

    foreach ($pattern in $candidates) {
        $found = Get-ChildItem -Path $pattern -File -ErrorAction SilentlyContinue | Sort-Object LastWriteTime -Descending
        if ($found -and $found.Count -gt 0) {
            return $found[0].FullName
        }
    }

    # Fallback: try to find any php.exe on C:\ drive (fast lookup limited depth)
    Write-Verbose "Attempting system-wide search for php.exe (may take a moment)..."
    $foundSys = Get-ChildItem -Path C:\ -Filter php.exe -Recurse -ErrorAction SilentlyContinue -Depth 4 | Select-Object -First 1
    if ($foundSys) { return $foundSys.FullName }

    return $null
}

Write-Host "Running migration helper..." -ForegroundColor Cyan

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Definition
Set-Location $projectRoot

$php = Find-Php
if (-not $php) {
    Write-Host "ERROR: php.exe not found on PATH or common Laragon locations." -ForegroundColor Red
    Write-Host "Please either:"
    Write-Host "  * Open Laragon Terminal (recommended), or"
    Write-Host "  * Add PHP to your PATH, or"
    Write-Host "  * Provide the php path manually: .\scripts\run-migrations.ps1 -PhpPath 'C:\path\to\php.exe'" -ForegroundColor Yellow
    exit 1
}

Write-Host "Using PHP: $php" -ForegroundColor Green

# composer step
if (-not $NoComposer) {
    $composerPath = $null
    try { $composerCmd = Get-Command composer -ErrorAction Stop; $composerPath = $composerCmd.Path } catch {}

    if ($composerPath) {
        Write-Host "Running: composer dump-autoload" -ForegroundColor Cyan
        & $composerPath dump-autoload
    } elseif (Test-Path "composer.phar") {
        Write-Host "Running: php composer.phar dump-autoload" -ForegroundColor Cyan
        & $php "composer.phar" "dump-autoload"
    } else {
        Write-Host "composer not found (skipping autoload generation)." -ForegroundColor Yellow
    }
}

Write-Host "Running migrations (php artisan migrate --force)" -ForegroundColor Cyan
& $php "artisan" "migrate" "--force"

$exitCode = $LASTEXITCODE
if ($exitCode -eq 0) {
    Write-Host "Migrations completed successfully." -ForegroundColor Green
} else {
    Write-Host "Migrations finished with exit code $exitCode." -ForegroundColor Red
}

exit $exitCode
