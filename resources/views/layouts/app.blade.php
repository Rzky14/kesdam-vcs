<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'KESDAM VCS') }}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <style>
        :root {
            --tni-green: #1a472a;
            --tni-dark: #0d2818;
            --tni-gold: #d4af37;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Figtree', sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }
        
        .app-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 260px;
            background: var(--tni-green);
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 25px 20px;
            background: var(--tni-dark);
            border-bottom: 2px solid var(--tni-gold);
        }
        
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            text-decoration: none;
        }
        
        .sidebar-brand i {
            font-size: 28px;
            color: var(--tni-gold);
        }
        
        .sidebar-brand-text h1 {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
            line-height: 1.2;
        }
        
        .sidebar-brand-text p {
            font-size: 11px;
            margin: 0;
            opacity: 0.8;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }
        
        .sidebar-menu li {
            margin: 0;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu a i {
            width: 24px;
            margin-right: 12px;
            font-size: 16px;
        }
        
        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.1);
            color: var(--tni-gold);
            border-left-color: var(--tni-gold);
        }
        
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.15);
            color: var(--tni-gold);
            border-left-color: var(--tni-gold);
            font-weight: 600;
        }
        
        /* Main Content Area */
        .main-content {
            flex: 1;
            margin-left: 260px;
            min-height: 100vh;
        }
        
        /* Top Bar */
        .top-bar {
            background: linear-gradient(135deg, var(--tni-green) 0%, var(--tni-dark) 100%);
            padding: 12px 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            justify-content: flex-end;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
            border-bottom: 2px solid var(--tni-gold);
        }
        
        .top-bar-left h2 {
            font-size: 20px;
            font-weight: 600;
            color: white;
            margin: 0;
        }
        
        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-menu {
            position: relative;
        }
        
        .user-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            cursor: pointer;
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s;
        }
        
        .user-toggle:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .user-toggle i {
            color: white;
            font-size: 20px;
        }
        
        .user-toggle span {
            font-weight: 500;
            color: white;
            font-size: 14px;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        
        .user-name {
            font-weight: 500;
            color: white;
            font-size: 14px;
            line-height: 1.2;
        }
        
        .user-role {
            font-size: 12px;
            color: var(--tni-gold);
            font-weight: 500;
            margin-top: 3px;
            display: block;
            line-height: 1.3;
        }
        
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 10px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            min-width: 200px;
            display: none;
            border: 1px solid #e9ecef;
        }
        
        .user-dropdown.show {
            display: block;
        }
        
        .user-dropdown a,
        .user-dropdown button {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .user-dropdown a:hover,
        .user-dropdown button:hover {
            background: #f8f9fa;
        }
        
        .user-dropdown button.text-danger {
            color: #dc3545;
        }
        
        .user-dropdown hr {
            margin: 5px 0;
            border: none;
            border-top: 1px solid #e9ecef;
        }
        
        /* Content Area */
        .content-area {
            padding: 30px;
            background: #f5f5f5;
            min-height: calc(100vh - 70px);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -260px;
                transition: margin-left 0.3s;
            }
            
            .sidebar.show {
                margin-left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .mobile-toggle {
                display: block !important;
            }
        }
        
        .mobile-toggle {
            display: none;
            background: var(--tni-green);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="app-wrapper">
        @auth
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="{{ route('dashboard') }}" class="sidebar-brand">
                    <i class="fas fa-shield-alt"></i>
                    <div class="sidebar-brand-text">
                        <h1>KESDAM III/SILIWANGI</h1>
                        <p>Sistem Manajemen Penjadwalan & Surat</p>
                    </div>
                </a>
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="{{ route('schedules.index') }}" class="{{ request()->routeIs('schedules.*') ? 'active' : '' }}">
                        <i class="fas fa-calendar-alt"></i> Manajemen Jadwal
                    </a>
                </li>
                <li>
                    <a href="{{ route('documents.index') }}" class="{{ request()->routeIs('documents.*') ? 'active' : '' }}">
                        <i class="fas fa-envelope"></i> Surat Menyurat
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="fas fa-file-alt"></i> Laporan
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="fas fa-cog"></i> Pengaturan
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <div class="top-bar-right">
                    <div class="user-menu">
                        <div class="user-toggle" onclick="document.getElementById('userDropdown').classList.toggle('show')">
                            <i class="fas fa-user-circle"></i>
                            <div class="user-info">
                                <span class="user-name">{{ Auth::user()->name }}</span>
                                <span class="user-role">{{ Auth::user()->position ?? 'Staff' }}</span>
                            </div>
                            <i class="fas fa-chevron-down" style="font-size: 10px;"></i>
                        </div>
                        <div class="user-dropdown" id="userDropdown">
                            <a href="{{ route('profile.edit') }}">
                                <i class="fas fa-user-edit"></i> Profile
                            </a>
                            <hr>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="text-danger">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="content-area">
                @yield('content')
            </div>
        </div>
        @else
        <div class="main-content" style="margin-left: 0;">
            <div class="content-area">
                @yield('content')
            </div>
        </div>
        @endauth
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.querySelector('.user-menu');
            const userDropdown = document.getElementById('userDropdown');
            if (userMenu && !userMenu.contains(event.target)) {
                userDropdown.classList.remove('show');
            }
        });
    </script>
</body>
</html>
