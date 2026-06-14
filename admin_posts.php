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

// Ambil semua postingan beserta detail pembuat dan statistik komentar/like
$query = "SELECT p.*, u.username, u.avatar_path,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS total_likes,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS total_comments
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC";
$posts = $pdo->query($query)->fetchAll();

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
    <title>Kelola Sambatan - Sambat Admin</title>
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
                        <a href="admin_dashboard.php" class="flex items-center gap-4 p-4 rounded-2xl font-medium transition-all duration-300 sambat-sidebar-link">
                            <span class="text-xl">📊</span>
                            <span class="text-sm tracking-wide">Dashboard</span>
                        </a>
                        <a href="admin_users.php" class="flex items-center gap-4 p-4 rounded-2xl font-medium transition-all duration-300 sambat-sidebar-link">
                            <span class="text-xl">👥</span>
                            <span class="text-sm tracking-wide">Kelola User</span>
                        </a>
                        <a href="admin_posts.php" class="flex items-center gap-4 p-4 rounded-2xl font-bold transition-all duration-300 sambat-sidebar-link active">
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

            <!-- ================= UTAMA (MIDDLE KELOLA SAMBATAN) ================= -->
            <main class="col-span-12 md:col-span-9 min-h-screen pb-24 md:pb-8 pt-4 md:pt-6 px-4 md:px-6">
                <div class="mb-8">
                    <h2 class="text-3xl font-extrabold tracking-tight sambat-text-primary">📝 Moderasi Sambatan</h2>
                    <p class="text-sm mt-1 sambat-text-muted">Daftar postingan unek-unek pengguna. Anda dapat meninjau isi teks, lampiran foto, serta menghapus postingan yang melanggar hukum/kebijakan.</p>
                </div>

                <!-- Daftar Sambatan -->
                <div class="space-y-4">
                    <?php if (empty($posts)): ?>
                        <div class="sambat-card p-12 rounded-3xl text-center text-gray-500">
                            <span class="text-4xl block mb-3">📝</span>
                            Belum ada sambatan yang diunggah di sistem.
                        </div>
                    <?php else: ?>
                        <?php foreach ($posts as $post): 
                            $uAvatar = getAvatarUrl($post['username'], $post['avatar_path']);
                        ?>
                            <div class="sambat-card p-5 rounded-3xl flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center">
                                <div class="flex gap-4 items-start min-w-0 flex-grow">
                                    <img src="<?= $uAvatar ?>" class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-bold text-sm sambat-text-primary">@<?= htmlspecialchars($post['username']) ?></span>
                                            <span class="text-[10px] sambat-text-muted"><?= date('d M Y H:i', strtotime($post['created_at'])) ?></span>
                                        </div>
                                        <p class="text-sm mt-2 leading-relaxed whitespace-pre-wrap sambat-text-secondary">
                                            <?= htmlspecialchars($post['content_text']) ?>
                                        </p>
                                        
                                        <!-- Lampiran Foto jika ada -->
                                        <?php if (!empty($post['image_path'])): ?>
                                            <div class="mt-3 rounded-2xl overflow-hidden border sambat-border max-h-40 max-w-xs bg-black/20">
                                                <img src="uploads/posts/<?= htmlspecialchars($post['image_path']) ?>" class="w-full object-cover max-h-40">
                                            </div>
                                        <?php endif; ?>

                                        <!-- Detail Statistik Kecil -->
                                        <div class="flex gap-4 text-[11px] mt-3 font-semibold sambat-text-muted">
                                            <span>❤️ <?= $post['total_likes'] ?> Suka</span>
                                            <span>💬 <?= $post['total_comments'] ?> Komentar</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tombol Hapus Posting -->
                                <form action="actions/delete_post.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus sambatan ini secara permanen?');"
                                      class="flex-shrink-0 self-end sm:self-center">
                                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                    <button type="submit" class="bg-red-955/20 hover:bg-red-900/30 border border-red-500/10 text-red-500 font-bold text-xs px-4 py-2.5 rounded-xl transition-all duration-200">
                                        🚨 Hapus Post
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>

        </div>
    </div>
</body>
</html>
