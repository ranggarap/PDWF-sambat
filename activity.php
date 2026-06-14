<?php
session_start();
require_once 'config/db.php';

// Cek autentikasi
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}
$user_id = $_SESSION['user_id'];

// Helper avatar
function getAvatarUrl($username, $avatarPath) {
    if (!empty($avatarPath) && file_exists('uploads/avatars/' . $avatarPath)) {
        return 'uploads/avatars/' . $avatarPath;
    }
    return 'https://api.dicebear.com/7.x/initials/svg?seed=' . urlencode($username) . '&backgroundType=gradientLinear&fontSize=40';
}

// Ambil info user aktif
$stmt_me = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt_me->execute([$user_id]);
$me = $stmt_me->fetch();
$is_admin = $me && intval($me['is_admin']) === 1;

// Ambil riwayat aktivitas gabungan (Likes pada postingan saya, Komentar pada postingan saya, dan Pengikut baru)
$activity_query = "
SELECT * FROM (
    SELECT 'like' AS activity_type, l.created_at, u.username, u.avatar_path, p.content_text AS post_preview, NULL AS comment_text
    FROM likes l
    JOIN posts p ON l.post_id = p.id
    JOIN users u ON l.user_id = u.id
    WHERE p.user_id = ? AND l.user_id != ?
    UNION ALL
    SELECT 'comment' AS activity_type, c.created_at, u.username, u.avatar_path, p.content_text AS post_preview, c.comment_text
    FROM comments c
    JOIN posts p ON c.post_id = p.id
    JOIN users u ON c.user_id = u.id
    WHERE p.user_id = ? AND c.user_id != ?
    UNION ALL
    SELECT 'follow' AS activity_type, f.created_at, u.username, u.avatar_path, NULL AS post_preview, NULL AS comment_text
    FROM follows f
    JOIN users u ON f.follower_id = u.id
    WHERE f.following_id = ?
) AS activities
ORDER BY created_at DESC
LIMIT 30
";
$stmt_act = $pdo->prepare($activity_query);
$stmt_act->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
$activities = $stmt_act->fetchAll();

// Ambil Rekomendasi Follow
$followSuggestStmt = $pdo->prepare("SELECT id, username, avatar_path FROM users WHERE id != ? AND id NOT IN (SELECT following_id FROM follows WHERE follower_id = ?) ORDER BY RAND() LIMIT 3");
$followSuggestStmt->execute([$user_id, $user_id]);
$suggestions = $followSuggestStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivitas - Sambat</title>
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
            
            <!-- ================= SIDEBAR KIRI (DESKTOP) ================= -->
            <aside class="col-span-2 hidden md:flex flex-col justify-between py-6 h-screen sticky top-0 border-r sambat-border">
                <div class="space-y-8">
                    <!-- Logo -->
                    <div class="flex items-center gap-2 pl-4">
                        <span class="text-2xl font-black bg-gradient-to-r from-red-500 to-rose-600 bg-clip-text text-transparent tracking-tight">Sambat</span>
                    </div>
                    
                    <!-- Menu Links -->
                    <nav class="space-y-2">
                        <a href="index.php" class="flex items-center gap-4 p-4 rounded-2xl font-medium transition-all duration-300 sambat-sidebar-link">
                            <span class="text-xl">🏠</span>
                            <span class="text-sm tracking-wide">Untuk Anda</span>
                        </a>
                        <a href="search.php" class="flex items-center gap-4 p-4 rounded-2xl font-medium transition-all duration-300 sambat-sidebar-link">
                            <span class="text-xl">🔍</span>
                            <span class="text-sm tracking-wide">Cari</span>
                        </a>
                        <a href="messages.php" class="flex items-center gap-4 p-4 rounded-2xl font-medium transition-all duration-300 sambat-sidebar-link">
                            <span class="text-xl">✉️</span>
                            <span class="text-sm tracking-wide">Pesan</span>
                        </a>
                        <a href="activity.php" class="flex items-center gap-4 p-4 rounded-2xl font-bold transition-all duration-300 sambat-sidebar-link active">
                            <span class="text-xl">❤️</span>
                            <span class="text-sm tracking-wide">Aktivitas</span>
                        </a>
                        <a href="profile.php" class="flex items-center gap-4 p-4 rounded-2xl font-medium transition-all duration-300 sambat-sidebar-link">
                            <span class="text-xl">👤</span>
                            <span class="text-sm tracking-wide">Profil</span>
                        </a>
                        <?php if ($is_admin): ?>
                        <a href="admin_dashboard.php" class="flex items-center gap-4 p-4 rounded-2xl font-medium transition-all duration-300 sambat-sidebar-link">
                            <span class="text-xl">📊</span>
                            <span class="text-sm tracking-wide">Panel Admin</span>
                        </a>
                        <?php endif; ?>
                        
                        <!-- Theme Toggle Button -->
                        <button onclick="toggleTheme()" class="flex items-center gap-4 p-4 w-full text-left rounded-2xl font-medium transition-all duration-300 sambat-sidebar-link cursor-pointer">
                            <span class="text-xl">🌓</span>
                            <span class="text-sm tracking-wide">Ganti Tema</span>
                        </button>
                    </nav>
                </div>
                
                <!-- User Profile Summary at bottom of sidebar -->
                <div class="pr-4 space-y-4">
                    <div class="flex items-center gap-3 p-3 bg-white/[0.02] border sambat-border rounded-2xl">
                        <img src="<?= getAvatarUrl($me['username'], $me['avatar_path']) ?>" class="w-10 h-10 rounded-full object-cover">
                        <div class="overflow-hidden">
                            <h4 class="text-sm font-bold truncate sambat-text-primary">@<?= htmlspecialchars($me['username']) ?></h4>
                            <p class="text-xs truncate sambat-text-muted"><?= htmlspecialchars($me['email']) ?></p>
                        </div>
                    </div>
                    <a href="logout.php" class="flex items-center justify-center gap-2 w-full p-3.5 bg-red-955/20 hover:bg-red-900/30 border border-red-500/10 text-red-500 font-bold rounded-2xl text-xs tracking-wider uppercase transition-all duration-300">
                        <span>🚪</span> Keluar
                    </a>
                </div>
            </aside>
            
            <!-- ================= FEED AKTIVITAS (MIDDLE) ================= -->
            <main class="col-span-12 md:col-span-7 min-h-screen pb-24 md:pb-8 pt-4 md:pt-6 border-x sambat-border px-4 md:px-6">
                <!-- Mobile Header -->
                <div class="flex md:hidden items-center justify-between pb-4 mb-4 border-b sambat-border">
                    <span class="text-2xl font-black bg-gradient-to-r from-red-500 to-rose-600 bg-clip-text text-transparent">Sambat</span>
                    <a href="profile.php">
                        <img src="<?= getAvatarUrl($me['username'], $me['avatar_path']) ?>" class="w-8 h-8 rounded-full border border-white/20">
                    </a>
                </div>
                
                <div class="mb-6">
                    <h2 class="text-2xl font-extrabold tracking-tight sambat-text-primary">❤️ Aktivitas & Notifikasi</h2>
                    <p class="text-xs mt-1 sambat-text-muted">Pantau interaksi pengguna lain terhadap sambatan Anda.</p>
                </div>
                
                <!-- Daftar Aktivitas -->
                <div class="space-y-4">
                    <?php if (empty($activities)): ?>
                        <div class="sambat-card p-12 rounded-3xl text-center text-gray-500">
                            <span class="text-4xl block mb-3">📭</span>
                            Belum ada aktivitas masuk. Semburkan sambatan terbaik Anda untuk memicu interaksi!
                        </div>
                    <?php else: ?>
                        <?php foreach ($activities as $act): 
                            $actAvatar = getAvatarUrl($act['username'], $act['avatar_path']);
                            $relativeTime = timeAgo($act['created_at']);
                        ?>
                            <div class="sambat-card p-4 rounded-2xl transition-all duration-300 flex gap-4 items-start">
                                <!-- Avatar Pelaku -->
                                <img src="<?= $actAvatar ?>" class="w-10 h-10 rounded-full object-cover shadow-sm flex-shrink-0">
                                
                                <div class="flex-grow min-w-0 text-sm">
                                    <!-- Logika Tampilan per tipe aktivitas -->
                                    <?php if ($act['activity_type'] === 'like'): ?>
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="font-bold hover:underline cursor-pointer sambat-text-primary">@<?= htmlspecialchars($act['username']) ?></span>
                                            <span class="sambat-text-secondary">menyukai unek-unek Anda</span>
                                            <span class="bg-red-500/10 text-red-400 border border-red-500/20 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1">
                                                ❤️ Suka
                                            </span>
                                        </div>
                                        <?php if (!empty($act['post_preview'])): ?>
                                            <p class="text-xs italic truncate mt-1 bg-white/[0.01] p-2 rounded-lg border sambat-border sambat-text-muted">
                                                "<?= htmlspecialchars($act['post_preview']) ?>"
                                            </p>
                                        <?php endif; ?>
                                    <?php elseif ($act['activity_type'] === 'comment'): ?>
                                        <div>
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="font-bold hover:underline cursor-pointer sambat-text-primary">@<?= htmlspecialchars($act['username']) ?></span>
                                                <span class="sambat-text-secondary">mengomentari sambatan Anda</span>
                                                <span class="bg-rose-500/10 text-rose-400 border border-rose-500/20 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                                    💬 Komentar
                                                </span>
                                            </div>
                                            <p class="text-xs mt-2 font-medium bg-white/[0.02] p-3 rounded-xl border sambat-border relative before:content-[''] before:block before:absolute before:left-3 before:-top-1.5 before:w-3 before:h-3 before:bg-white/[0.02] before:rotate-45 before:border-l before:border-t before:border-white/[0.05] sambat-text-secondary">
                                                <?= htmlspecialchars($act['comment_text']) ?>
                                            </p>
                                            <p class="text-[10px] mt-1.5 truncate sambat-text-muted">
                                                Pada: "<?= htmlspecialchars($act['post_preview']) ?>"
                                            </p>
                                        </div>
                                    <?php elseif ($act['activity_type'] === 'follow'): ?>
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="font-bold hover:underline cursor-pointer sambat-text-primary">@<?= htmlspecialchars($act['username']) ?></span>
                                            <span class="sambat-text-secondary">mulai mengikuti Anda!</span>
                                            <span class="bg-blue-500/10 text-blue-400 border border-blue-500/20 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                                👤 Ikuti Baru
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="text-[10px] mt-1.5 tracking-wider sambat-text-muted">
                                        <?= $relativeTime ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
            
            <!-- ================= KANAN: REKOMENDASI (DESKTOP) ================= -->
            <section class="col-span-3 hidden lg:flex flex-col gap-6 py-6 h-screen sticky top-0 overflow-y-auto pl-4">
                <!-- Who to Follow Card -->
                <div class="sambat-card p-5 rounded-3xl space-y-4">
                    <h3 class="font-bold text-sm tracking-wide border-b sambat-border pb-2 sambat-text-primary">👥 Siapa untuk diikuti</h3>
                    <div class="space-y-3">
                        <?php if(empty($suggestions)): ?>
                            <p class="text-xs sambat-text-muted">Semua pengguna sudah diikuti.</p>
                        <?php else: ?>
                            <?php foreach($suggestions as $suggest): 
                                $sugAvatar = getAvatarUrl($suggest['username'], $suggest['avatar_path']);
                            ?>
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2.5 overflow-hidden">
                                        <img src="<?= $sugAvatar ?>" class="w-8 h-8 rounded-full object-cover">
                                        <div class="overflow-hidden">
                                            <p class="text-xs font-bold truncate hover:underline cursor-pointer sambat-text-primary">@<?= htmlspecialchars($suggest['username']) ?></p>
                                        </div>
                                    </div>
                                    <form action="actions/follow_action.php" method="POST">
                                        <input type="hidden" name="following_id" value="<?= $suggest['id'] ?>">
                                        <button type="submit" class="bg-red-600 hover:bg-red-500 text-white font-bold text-[10px] px-3 py-1.5 rounded-full transition-colors">
                                            Ikuti
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="text-[10px] px-2 space-y-1 sambat-text-muted">
                    <p>© 2026 Sambat Inc.</p>
                    <p>Dibuat untuk meluapkan penat hidup.</p>
                </div>
            </section>
        </div>
    </div>
    
    <!-- ================= BOTTOM NAVIGATION BAR (MOBILE ONLY) ================= -->
    <div class="md:hidden fixed bottom-0 left-0 right-0 z-50 border-t sambat-border backdrop-blur-lg" style="background-color: var(--bg-primary); opacity: 0.95;">
        <nav class="flex justify-around items-center py-3">
            <a href="index.php" class="text-xl flex flex-col items-center sambat-sidebar-link">
                <span>🏠</span>
                <span class="text-[9px] font-medium tracking-wide mt-0.5">Home</span>
            </a>
            <a href="search.php" class="text-xl flex flex-col items-center sambat-sidebar-link">
                <span>🔍</span>
                <span class="text-[9px] font-medium tracking-wide mt-0.5">Cari</span>
            </a>
            <a href="messages.php" class="text-xl flex flex-col items-center sambat-sidebar-link">
                <span>✉️</span>
                <span class="text-[9px] font-medium tracking-wide mt-0.5">Pesan</span>
            </a>
            <a href="activity.php" class="text-red-500 text-xl font-bold flex flex-col items-center">
                <span>❤️</span>
                <span class="text-[9px] font-medium tracking-wide mt-0.5 text-red-500">Aktivitas</span>
            </a>
            <a href="profile.php" class="text-xl flex flex-col items-center sambat-sidebar-link">
                <span>👤</span>
                <span class="text-[9px] font-medium tracking-wide mt-0.5">Profil</span>
            </a>
        </nav>
    </div>
</body>
</html>