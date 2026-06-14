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
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Username atau password salah!";
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
    <title>Masuk - Sambat</title>
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
            <p class="text-sm sambat-text-secondary">Tempat terbaik untuk meluapkan unek-unek.</p>
        </div>
        
        <!-- Alert Notifications -->
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'registered'): ?>
            <div class="mb-5 p-4 rounded-2xl bg-green-500/10 border border-green-500/20 text-green-400 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Pendaftaran berhasil! Silakan masuk.</span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="mb-5 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Login Form -->
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
                <label class="block text-xs font-semibold uppercase tracking-wider mb-2 sambat-text-muted" for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required 
                       class="w-full px-4 py-3.5 rounded-2xl outline-none transition-all duration-300 placeholder-gray-500 text-sm sambat-input">
            </div>
            <button type="submit" 
                    class="w-full bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 text-white py-3.5 px-4 rounded-2xl font-bold transition-all duration-300 transform active:scale-[0.98] shadow-lg shadow-red-600/20 text-sm mt-2">
                Masuk Sekarang
            </button>
        </form>
        
        <!-- Register Link -->
        <div class="mt-8 pt-6 border-t sambat-border text-center">
            <p class="text-sm sambat-text-secondary">
                Belum punya akun? 
                <a href="register.php" class="text-red-500 hover:text-red-400 font-semibold transition-colors duration-200 ml-1">Daftar</a>
            </p>
        </div>
    </div>
</body>
</html>
