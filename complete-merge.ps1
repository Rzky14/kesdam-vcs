# Complete stuck git merge
Write-Host "Completing git merge..." -ForegroundColor Yellow

# Set Git environment to avoid terminal prompts
$env:GIT_EDITOR = "true"
$env:GIT_MERGE_AUTOEDIT = "no"

# Try to complete the merge
try {
    # Check if we're in a merge state
    if (Test-Path ".git\MERGE_HEAD") {
        Write-Host "Merge in progress detected. Completing..." -ForegroundColor Cyan
        
        # Complete the merge with a commit
        $commitMsg = "Merge feature/approval-workflow to restore Phase 1-5 migrations"
        git commit -m $commitMsg 2>&1 | Out-Host
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host "`nMerge completed successfully!" -ForegroundColor Green
            Write-Host "`nCurrent branch status:" -ForegroundColor Yellow
            git status 2>&1 | Out-Host
        } else {
            Write-Host "`nFailed to complete merge. Exit code: $LASTEXITCODE" -ForegroundColor Red
        }
    } else {
        Write-Host "No merge in progress." -ForegroundColor Green
        git status 2>&1 | Out-Host
    }
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}
