<!DOCTYPE html>
<html>
<head>
    <title>Test Login Position Display</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 600px;
            margin: 20px auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .user-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .user-name {
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }
        .user-role {
            font-size: 11px;
            color: #d4af37;
            font-weight: 400;
        }
        h2 {
            color: #1a472a;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="test-card">
        <h2>🔍 Test Display User Position</h2>
        
        <?php
        require __DIR__.'/vendor/autoload.php';
        $app = require_once __DIR__.'/bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        
        echo "<h3>Simulasi Data User yang Login:</h3>";
        
        $admin = App\Models\User::where('email', 'admin@kesdam.mil.id')->first();
        
        if ($admin) {
            echo "<p class='success'>✅ User ditemukan!</p>";
            
            echo "<div style='background: #1a472a; padding: 15px; border-radius: 8px; color: white; margin: 20px 0;'>";
            echo "<div style='display: flex; align-items: center; gap: 10px;'>";
            echo "<i style='font-size: 24px;'>👤</i>";
            echo "<div class='user-info'>";
            echo "<span class='user-name' style='color: white;'>{$admin->name}</span>";
            echo "<span class='user-role'>" . ($admin->position ?? 'Staff') . "</span>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            
            echo "<h3>Data dari Database:</h3>";
            echo "<table style='width: 100%; border-collapse: collapse;'>";
            echo "<tr style='background: #f0f0f0;'>";
            echo "<th style='padding: 10px; text-align: left; border: 1px solid #ddd;'>Field</th>";
            echo "<th style='padding: 10px; text-align: left; border: 1px solid #ddd;'>Value</th>";
            echo "</tr>";
            echo "<tr>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>Name</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>{$admin->name}</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>Email</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>{$admin->email}</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'><strong>Position</strong></td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'><strong>" . ($admin->position ?? 'NULL') . "</strong></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>NRP</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>{$admin->nrp}</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>Rank</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>{$admin->rank}</td>";
            echo "</tr>";
            echo "</table>";
            
            if (empty($admin->position)) {
                echo "<p class='error'>⚠️ WARNING: Position field is EMPTY!</p>";
                echo "<p>Silakan update position di database atau saat edit user.</p>";
            } else {
                echo "<p class='success'>✅ Position field sudah terisi: <strong>{$admin->position}</strong></p>";
            }
        } else {
            echo "<p class='error'>❌ Admin user tidak ditemukan!</p>";
        }
        ?>
        
        <hr style="margin: 20px 0;">
        
        <h3>Cara Test di Browser:</h3>
        <ol>
            <li>Login dengan: <strong>admin@kesdam.mil.id</strong> / <strong>password123</strong></li>
            <li>Setelah login, lihat pojok kanan atas</li>
            <li>Harusnya muncul nama dan <span style="color: #d4af37;">jabatan dengan warna gold</span></li>
        </ol>
        
        <a href="/" style="display: inline-block; background: #1a472a; color: #d4af37; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px;">
            🏠 Ke Halaman Login
        </a>
    </div>
</body>
</html>
