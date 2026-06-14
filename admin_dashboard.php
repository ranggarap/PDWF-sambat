<?php
session_start();
require_once 'config/db.php';

// Cek autentikasi & peran admin
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}
$user_id = $_SESSION['user_id'];

// Verifikasi apakah admin
$stmt_me = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt_me->execute([$user_id]);
$me = $stmt_me->fetch();

if (!$me || intval($me['is_admin']) !== 1) {
    header("Location: index.php"); // Tolak akses jika bukan admin
    exit();
}

// Hitung total data
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_posts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$total_comments = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$total_messages = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();

// Ambil postingan terbaru untuk log aktivitas ringkas
$latest_posts = $pdo->query("SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 5")->fetchAll();

// Helper avatar
function getAvatarUrl($username, $avatarPath) {
    if (!empty($avatarPath) && file_exists('uploads/avatars/' . $avatarPath)) {
        return 'uploads/avatars/' . $avatarPath;
    }
    return 'https://api.dicebear.com/7.x/initials/svg?seed=' . urlencode($username) . '&backgroundType=gradientLinear&fontSize=40';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sambat</title>
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
<body class="sambat-body font-sans min-h-screen">
    <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
        <div class="grid grid-cols-12 gap-0 md:gap-6">
            
            <!-- ================= SIDEBAR KIRI ADMIN ================= -->
            <aside class="col-span-12 md:col-span-3 flex flex-col justify-between py-6 h-auto md:h-screen sticky top-0 border-b md:border-b-0 md:border-r sambat-border">
                <div class="space-y-8">
                    <!-- Logo Admin -->
                    <div class="flex items-center gap-2 pl-4">
                        <span class="text-2xl font-black bg-gradient-to-r from-red-500 to-rose-600 bg-clip-text text-transparent tracking-tight">Sambat Admin</span>
                    </div>
                    
                    <!-- Menu Links -->
                    <nav class="space-y-2">
                        <a href="admin_dashboard.php" class="flex items-center gap-4 p-4 rounded-2xl font-bold transition-all duration-300 sambat-sidebar-link active">
                            <span class="text-xl">📊</span>
                            <span class="text-sm tracking-wide">Dashboard</span>
                        </a>
                        <a href="admin_users.php" class="flex items-center gap-4 p-4 rounded-2xl font-medium transition-all duration-300 sambat-sidebar-link">
                            <span class="text-xl">👥</span>
                            <span class="text-sm tracking-wide">Kelola User</span>
                        </a>
                        <a href="admin_posts.php" class="flex items-center gap-4 p-4 rounded-2xl font-medium transition-all duration-300 sambat-sidebar-link">
                            <span class="text-xl">📝</span>
                            <span class="text-sm tracking-wide">Kelola Sambatan</span>
                        </a>
                        <a href="index.php" class="flex items-center gap-4 p-4 rounded-2xl font-medium transition-all duration-300 sambat-sidebar-link mt-10">
                            <span class="text-xl">🏠</span>
                            <span class="text-sm tracking-wide">Kembali ke Sambat</span>
                        </a>
                        <!-- Theme Toggle Button -->
                        <button onclick="toggleTheme()" class="flex items-center gap-4 p-4 w-full text-left rounded-2xl font-medium transition-all duration-300 sambat-sidebar-link cursor-pointer">
                            <span class="text-xl">🌓</span>
                            <span class="text-sm tracking-wide">Ganti Tema</span>
                        </button>
                    </nav>
                </div>

                <!-- Admin Profile Summary -->
                <div class="pr-4 mt-6 md:mt-0 space-y-4">
                    <div class="flex items-center gap-3 p-3 bg-white/[0.02] border sambat-border rounded-2xl">
                        <img src="<?= getAvatarUrl($me['username'], $me['avatar_path']) ?>" class="w-10 h-10 rounded-full object-cover">
                        <div class="overflow-hidden">
                            <h4 class="text-sm font-bold truncate sambat-text-primary">@<?= htmlspecialchars($me['username']) ?></h4>
                            <p class="text-xs text-red-500 uppercase tracking-widest font-extrabold text-[9px]">Super Admin</p>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- ================= UTAMA (MIDDLE ADMIN FEED) ================= -->
            <main class="col-span-12 md:col-span-9 min-h-screen pb-24 md:pb-8 pt-4 md:pt-6 px-4 md:px-6">
                <div class="mb-8">
                    <h2 class="text-3xl font-extrabold tracking-tight sambat-text-primary">📊 Dashboard Statistik</h2>
                    <p class="text-sm mt-1 sambat-text-muted">Gambaran umum data real-time di database aplikasi Sambat.</p>
                </div>

                <!-- Grid Kartu Statistik -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <!-- 1. Total User -->
                    <div class="sambat-card p-5 rounded-3xl flex items-center justify-between shadow-sm">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wider sambat-text-muted">Total User</p>
                            <p class="text-3xl font-black mt-1 sambat-text-primary"><?= $total_users ?></p>
                        </div>
                        <span class="text-3xl p-3 bg-blue-500/10 text-blue-400 rounded-2xl">👥</span>
                    </div>

                    <!-- 2. Total Sambatan -->
                    <div class="sambat-card p-5 rounded-3xl flex items-center justify-between shadow-sm">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wider sambat-text-muted">Sambatan</p>
                            <p class="text-3xl font-black mt-1 sambat-text-primary"><?= $total_posts ?></p>
                        </div>
                        <span class="text-3xl p-3 bg-red-500/10 text-red-400 rounded-2xl">📝</span>
                    </div>

                    <!-- 3. Total Komentar -->
                    <div class="sambat-card p-5 rounded-3xl flex items-center justify-between shadow-sm">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wider sambat-text-muted">Komentar</p>
                            <p class="text-3xl font-black mt-1 sambat-text-primary"><?= $total_comments ?></p>
                        </div>
                        <span class="text-3xl p-3 bg-rose-500/10 text-rose-400 rounded-2xl">💬</span>
                    </div>

                    <!-- 4. Total Pesan -->
                    <div class="sambat-card p-5 rounded-3xl flex items-center justify-between shadow-sm">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wider sambat-text-muted">Pesan Privat</p>
                            <p class="text-3xl font-black mt-1 sambat-text-primary"><?= $total_messages ?></p>
                        </div>
                        <span class="text-3xl p-3 bg-purple-500/10 text-purple-400 rounded-2xl">✉️</span>
                    </div>
                </div>

                <!-- Aktivitas Sambatan Terbaru -->
                <div class="sambat-card p-6 rounded-3xl">
                    <h3 class="font-bold text-lg tracking-wide border-b sambat-border pb-3 mb-4 sambat-text-primary">🆕 Sambatan Terbaru Mengudara</h3>
                    
                    <div class="space-y-4">
                        <?php if (empty($latest_posts)): ?>
                            <p class="text-xs text-center py-4 sambat-text-muted">Belum ada sambatan di database.</p>
                        <?php else: ?>
                            <?php foreach ($latest_posts as $post): 
                                $preview = mb_strimwidth($post['content_text'], 0, 100, "...");
                            ?>
                                <div class="flex justify-between items-start gap-4 p-3 bg-white/[0.01] border sambat-border rounded-2xl hover:bg-white/[0.02] transition-all">
                                    <div>
                                        <p class="text-xs font-bold text-red-500">@<?= htmlspecialchars($post['username']) ?></p>
                                        <p class="text-sm mt-1 sambat-text-secondary">"<?= htmlspecialchars($preview) ?>"</p>
                                    </div>
                                    <span class="text-[10px] self-center flex-shrink-0 sambat-text-muted"><?= timeAgo($post['created_at']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>

        </div>
    </div>
</body>
</html>
