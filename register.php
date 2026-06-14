<?php
session_start();
require_once 'config/db.php';

// Jika sudah login, langsung ke index
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($email) && !empty($password)) {
        // Validasi format username (tanpa spasi dan karakter aneh)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $error = "Username hanya boleh mengandung huruf, angka, dan underscore!";
        } elseif (strlen($password) < 6) {
            $error = "Password minimal harus 6 karakter!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            try {
                // Cek apakah username/email sudah dipakai
                $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $check->execute([$username, $email]);
                
                if ($check->rowCount() > 0) {
                    $error = "Username atau Email sudah terdaftar!";
                } else {
                    // Default avatar set di schema database sebagai 'default_avatar.png'
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                    $stmt->execute([$username, $email, $hashed_password]);
                    header("Location: login.php?msg=registered");
                    exit();
                }
            } catch (PDOException $e) {
                $error = "Gagal mendaftar. Terjadi kesalahan server.";
            }
        }
    } else {
        $error = "Harap isi semua kolom!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Sambat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <script src="js/theme.js"></script>
    <script>
        tailwind.config = { 
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    }
                }
            }
        };
    </script>
</head>
<body class="sambat-body font-sans min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    <!-- Glowing Accent Circles in background -->
    <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-red-600/10 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-red-900/10 rounded-full blur-[120px] pointer-events-none"></div>
    
    <!-- Theme switcher floating button for auth pages -->
    <div class="absolute top-4 right-4 z-20">
        <button onclick="toggleTheme()" class="p-3.5 rounded-2xl transition-all duration-300 sambat-sidebar-link sambat-card focus:outline-none cursor-pointer">
            🌓 Ganti Tema
        </button>
    </div>
    
    <div class="sambat-card p-8 rounded-3xl w-full max-w-md shadow-2xl relative z-10">
        <!-- Logo and Slogan -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-extrabold tracking-tight bg-gradient-to-r from-red-500 to-rose-600 bg-clip-text text-transparent mb-2">
                Sambat
            </h1>
            <p class="text-sm sambat-text-secondary">Bergabunglah dan mulai bagikan beban pikiran Anda.</p>
        </div>
        
        <!-- Alert Notifications -->
        <?php if (!empty($error)): ?>
            <div class="mb-5 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Register Form -->
        <form method="POST" class="space-y-5">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider mb-2 sambat-text-muted" for="username">Username</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-500">@</span>
                    <input type="text" id="username" name="username" placeholder="usernameanda" required 
                           class="w-full pl-9 pr-4 py-3.5 rounded-2xl outline-none transition-all duration-300 placeholder-gray-500 text-sm sambat-input">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider mb-2 sambat-text-muted" for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="nama@email.com" required 
                       class="w-full px-4 py-3.5 rounded-2xl outline-none transition-all duration-300 placeholder-gray-500 text-sm sambat-input">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider mb-2 sambat-text-muted" for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Min. 6 karakter" required 
                       class="w-full px-4 py-3.5 rounded-2xl outline-none transition-all duration-300 placeholder-gray-500 text-sm sambat-input">
            </div>
            <button type="submit" 
                    class="w-full bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 text-white py-3.5 px-4 rounded-2xl font-bold transition-all duration-300 transform active:scale-[0.98] shadow-lg shadow-red-600/20 text-sm mt-2">
                Daftar Sekarang
            </button>
        </form>
        
        <!-- Login Link -->
        <div class="mt-8 pt-6 border-t sambat-border text-center">
            <p class="text-sm sambat-text-secondary">
                Sudah punya akun? 
                <a href="login.php" class="text-red-500 hover:text-red-400 font-semibold transition-colors duration-200 ml-1">Masuk</a>
            </p>
        </div>
    </div>
</body>
</html>