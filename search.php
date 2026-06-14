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

$search_results_posts = [];
$search_results_users = [];
$search_query = '';

if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
    $search_query = trim($_GET['q']);
    $keyword = "%" . $search_query . "%";
    
    // 1. Cari Postingan / Sambatan (beserta total_likes, total_comments, dan status user_liked)
    $stmt_posts = $pdo->prepare("SELECT p.*, u.username, u.avatar_path,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS total_likes,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS total_comments,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) AS user_liked
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.content_text LIKE ? 
        ORDER BY p.created_at DESC");
    $stmt_posts->execute([$user_id, $keyword]);
    $search_results_posts = $stmt_posts->fetchAll();
    
    // 2. Cari Pengguna / User lain (beserta status is_followed oleh user aktif)
    $stmt_users = $pdo->prepare("SELECT id, username, avatar_path,
        (SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = users.id) AS is_followed
        FROM users 
        WHERE username LIKE ? AND id != ? 
        LIMIT 10");
    $stmt_users->execute([$user_id, $keyword, $user_id]);
    $search_results_users = $stmt_users->fetchAll();
}

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
    <title>Cari Sambatan - Sambat</title>
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
                        <a href="search.php" class="flex items-center gap-4 p-4 rounded-2xl font-bold transition-all duration-300 sambat-sidebar-link active">
                            <span class="text-xl">🔍</span>
                            <span class="text-sm tracking-wide">Cari</span>
                        </a>
                        <a href="messages.php" class="flex items-center gap-4 p-4 rounded-2xl font-medium transition-all duration-300 sambat-sidebar-link">
                            <span class="text-xl">✉️</span>
                            <span class="text-sm tracking-wide">Pesan</span>
                        </a>
                        <a href="activity.php" class="flex items-center gap-4 p-4 rounded-2xl font-medium transition-all duration-300 sambat-sidebar-link">
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
            
            <!-- ================= UTAMA (MIDDLE PENCARIAN) ================= -->
            <main class="col-span-12 md:col-span-7 min-h-screen pb-24 md:pb-8 pt-4 md:pt-6 border-x sambat-border px-4 md:px-6">
                <!-- Mobile Header -->
                <div class="flex md:hidden items-center justify-between pb-4 mb-4 border-b sambat-border">
                    <span class="text-2xl font-black bg-gradient-to-r from-red-500 to-rose-600 bg-clip-text text-transparent">Sambat</span>
                    <a href="profile.php">
                        <img src="<?= getAvatarUrl($me['username'], $me['avatar_path']) ?>" class="w-8 h-8 rounded-full border border-white/20">
                    </a>
                </div>
                
                <div class="mb-6">
                    <h2 class="text-2xl font-extrabold tracking-tight sambat-text-primary">🔍 Cari Sambatan</h2>
                    <p class="text-xs mt-1 sambat-text-muted">Temukan pengguna lain atau sambatan spesifik.</p>
                </div>
                
                <!-- Form Pencarian -->
                <form action="search.php" method="GET" class="mb-8">
                    <div class="flex gap-2">
                        <div class="relative flex-grow">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-500 text-sm">🔍</span>
                            <input type="text" name="q" value="<?= htmlspecialchars($search_query) ?>" placeholder="Cari username atau topik sambatan..." required
                                   class="w-full text-sm pl-10 pr-4 py-3.5 rounded-2xl outline-none transition-all duration-300 placeholder-gray-500 sambat-input">
                        </div>
                        <button type="submit" class="bg-red-600 hover:bg-red-500 text-white font-bold px-6 py-3.5 rounded-2xl shadow-lg shadow-red-600/10 text-sm transition-all duration-300">
                            Cari
                        </button>
                    </div>
                </form>
                
                <?php if (!empty($search_query)): ?>
                    <div class="space-y-8">
                        
                        <!-- 1. HASIL CARI PENGGUNA -->
                        <div class="space-y-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider border-b sambat-border pb-2 sambat-text-muted">👥 Pengguna ditemukan (<?= count($search_results_users) ?>)</h3>
                            <?php if (empty($search_results_users)): ?>
                                <p class="text-xs py-2 sambat-text-muted">Tidak menemukan pengguna dengan username tersebut.</p>
                            <?php else: ?>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <?php foreach($search_results_users as $u): 
                                        $uAvatar = getAvatarUrl($u['username'], $u['avatar_path']);
                                        $isFollowed = $u['is_followed'] > 0;
                                    ?>
                                    <div class="sambat-card p-3.5 rounded-2xl flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3 overflow-hidden">
                                            <img src="<?= $uAvatar ?>" class="w-10 h-10 rounded-full object-cover shadow-sm">
                                            <div class="overflow-hidden">
                                                <h4 class="text-sm font-bold truncate sambat-text-primary">@<?= htmlspecialchars($u['username']) ?></h4>
                                            </div>
                                        </div>
                                        
                                        <!-- Follow / Unfollow Form -->
                                        <form action="actions/follow_action.php" method="POST">
                                            <input type="hidden" name="following_id" value="<?= $u['id'] ?>">
                                            <button type="submit" class="font-bold text-xs px-3 py-1.5 rounded-full transition-all duration-200 <?= $isFollowed ? 'bg-white/[0.08] hover:bg-white/[0.12] text-gray-300 border border-white/[0.05]' : 'bg-red-600 hover:bg-red-500 text-white shadow-md shadow-red-600/10' ?>">
                                                <?= $isFollowed ? 'Mengikuti' : 'Ikuti' ?>
                                            </button>
                                        </form>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- 2. HASIL CARI SAMBATAN (POSTS) -->
                        <div class="space-y-4">
                            <h3 class="text-xs font-bold uppercase tracking-wider border-b sambat-border pb-2 sambat-text-muted">🔥 Sambatan ditemukan (<?= count($search_results_posts) ?>)</h3>
                            <?php if (empty($search_results_posts)): ?>
                                <p class="text-xs py-2 sambat-text-muted">Tidak menemukan sambatan dengan kata kunci tersebut.</p>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach($search_results_posts as $post): 
                                        $avatarUrl = getAvatarUrl($post['username'], $post['avatar_path']);
                                        $isLiked = $post['user_liked'] > 0;
                                        $is_post_owner = intval($post['user_id']) === $user_id;
                                    ?>
                                    <article class="sambat-card p-5 rounded-3xl transition-all duration-300">
                                        <div class="flex gap-4">
                                            <img src="<?= $avatarUrl ?>" class="w-11 h-11 rounded-full object-cover flex-shrink-0">
                                            <div class="flex-grow min-w-0">
                                                <div class="flex justify-between items-center mb-1">
                                                    <h3 class="font-bold text-sm sambat-text-primary">@<?= htmlspecialchars($post['username']) ?></h3>
                                                    
                                                    <!-- Post Info & Delete Button -->
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-xs sambat-text-muted">
                                                            <?= timeAgo($post['created_at']) ?>
                                                        </span>
                                                        <?php if ($is_post_owner || $is_admin): ?>
                                                            <form action="actions/delete_post.php" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus sambatan ini secara permanen?');">
                                                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                                                <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors text-xs ml-1 focus:outline-none" title="Hapus Sambatan">
                                                                    🗑️
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <p class="text-sm leading-relaxed mb-3 whitespace-pre-wrap sambat-text-secondary"><?= htmlspecialchars($post['content_text']) ?></p>
                                                
                                                <?php if(!empty($post['image_path'])): ?>
                                                    <div class="mb-3 rounded-2xl overflow-hidden border sambat-border max-h-80 bg-black/20">
                                                        <img src="uploads/posts/<?= htmlspecialchars($post['image_path']) ?>" class="w-full object-cover max-h-80">
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Actions -->
                                                <div class="flex items-center gap-6 pt-2 text-gray-500 border-t sambat-border">
                                                    <button onclick="toggleLike(<?= $post['id'] ?>, this)" 
                                                            class="flex items-center gap-2 group text-xs font-semibold focus:outline-none transition-all duration-300 hover:text-red-500 <?= $isLiked ? 'text-red-500' : '' ?>">
                                                        <svg class="w-5 h-5 transition-transform duration-300 group-hover:scale-125 <?= $isLiked ? 'fill-red-600 text-red-600' : '' ?>" 
                                                             fill="<?= $isLiked ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                                        </svg>
                                                        <span class="like-count"><?= $post['total_likes'] ?></span>
                                                    </button>
                                                    <button onclick="toggleComments(<?= $post['id'] ?>)" 
                                                            class="flex items-center gap-2 group text-xs font-semibold hover:text-rose-500 transition-colors">
                                                        <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                                        </svg>
                                                        <span><?= $post['total_comments'] ?></span>
                                                    </button>
                                                </div>
                                                
                                                <!-- Collapsible comments -->
                                                <div id="comment-section-<?= $post['id'] ?>" class="hidden mt-4 pt-4 border-t sambat-border space-y-4">
                                                    <div class="space-y-3 max-h-60 overflow-y-auto pr-1" id="comment-list-<?= $post['id'] ?>">
                                                        <?php
                                                        $commentStmt = $pdo->prepare("SELECT c.*, u.username, u.avatar_path FROM comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = ? ORDER BY c.created_at ASC");
                                                        $commentStmt->execute([$post['id']]);
                                                        $comments = $commentStmt->fetchAll();
                                                        
                                                        if (empty($comments)):
                                                        ?>
                                                            <p class="text-xs text-center py-2 no-comments-msg sambat-text-muted">Belum ada komentar.</p>
                                                        <?php else: ?>
                                                            <?php foreach ($comments as $comment): 
                                                                $cmtAvatar = getAvatarUrl($comment['username'], $comment['avatar_path']);
                                                                $is_comment_owner = intval($comment['user_id']) === $user_id;
                                                            ?>
                                                                <div class="flex gap-3 text-sm">
                                                                    <img src="<?= $cmtAvatar ?>" class="w-7 h-7 rounded-full object-cover">
                                                                    <div class="p-2.5 rounded-2xl flex-grow sambat-card">
                                                                        <div class="flex justify-between items-center mb-0.5">
                                                                            <span class="font-bold text-xs sambat-text-primary">@<?= htmlspecialchars($comment['username']) ?></span>
                                                                            
                                                                            <!-- Comment Delete Button -->
                                                                            <div class="flex items-center gap-1.5">
                                                                                <span class="text-[10px] sambat-text-muted"><?= timeAgo($comment['created_at']) ?></span>
                                                                                <?php if ($is_comment_owner || $is_admin): ?>
                                                                                    <form action="actions/delete_comment.php" method="POST" class="inline" onsubmit="return confirm('Hapus komentar ini?');">
                                                                                        <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                                                                        <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors text-[10px] focus:outline-none" title="Hapus Komentar">
                                                                                            🗑️
                                                                                        </button>
                                                                                    </form>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        </div>
                                                                        <p class="text-xs leading-relaxed sambat-text-secondary"><?= htmlspecialchars($comment['comment_text']) ?></p>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <form action="actions/comment_action.php" method="POST" class="flex gap-2 pt-2 border-t sambat-border">
                                                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                                        <input type="text" name="comment_text" placeholder="Tulis komentar..." required autocomplete="off"
                                                               class="w-full text-xs px-3.5 py-2.5 rounded-xl outline-none transition-all placeholder-gray-500 sambat-input">
                                                        <button type="submit" class="bg-red-600 hover:bg-red-500 text-white font-bold px-4 py-2 rounded-xl text-xs transition-all">
                                                            Kirim
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- State Awal Sebelum Mencari -->
                    <div class="sambat-card p-10 rounded-3xl text-center mt-6">
                        <span class="text-4xl block mb-3">🔍</span>
                        Ketikkan sesuatu di kolom pencarian untuk menemukan hal-hal menarik di Sambat.
                    </div>
                <?php endif; ?>
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
            <a href="search.php" class="text-red-500 text-xl font-bold flex flex-col items-center">
                <span>🔍</span>
                <span class="text-[9px] font-medium tracking-wide mt-0.5 text-red-500">Cari</span>
            </a>
            <a href="messages.php" class="text-xl flex flex-col items-center sambat-sidebar-link">
                <span>✉️</span>
                <span class="text-[9px] font-medium tracking-wide mt-0.5">Pesan</span>
            </a>
            <a href="activity.php" class="text-xl flex flex-col items-center sambat-sidebar-link">
                <span>❤️</span>
                <span class="text-[9px] font-medium tracking-wide mt-0.5">Aktivitas</span>
            </a>
            <a href="profile.php" class="text-xl flex flex-col items-center sambat-sidebar-link">
                <span>👤</span>
                <span class="text-[9px] font-medium tracking-wide mt-0.5">Profil</span>
            </a>
        </nav>
    </div>
    
    <!-- ================= JAVASCRIPT LOGIC ================= -->
    <script>
        function toggleComments(postId) {
            const commentBox = document.getElementById(`comment-section-${postId}`);
            if (commentBox.classList.contains('hidden')) {
                commentBox.classList.remove('hidden');
                const list = document.getElementById(`comment-list-${postId}`);
                if (list) { list.scrollTop = list.scrollHeight; }
            } else {
                commentBox.classList.add('hidden');
            }
        }
        
        // AJAX Toggle Like (Fetch API)
        function toggleLike(postId, button) {
            button.disabled = true;
            fetch('actions/like_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ post_id: postId })
            })
            .then(response => response.json())
            .then(data => {
                button.disabled = false;
                if (data.status === 'success') {
                    const likeCountSpan = button.querySelector('.like-count');
                    const svg = button.querySelector('svg');
                    
                    likeCountSpan.textContent = data.total_likes;
                    
                    if (data.action === 'liked') {
                        button.classList.add('text-red-500');
                        svg.classList.add('fill-red-600', 'text-red-600');
                        svg.classList.add('scale-125');
                        setTimeout(() => svg.classList.remove('scale-125'), 300);
                    } else {
                        button.classList.remove('text-red-500');
                        svg.classList.remove('fill-red-600', 'text-red-600');
                        svg.setAttribute('fill', 'none');
                    }
                }
            })
            .catch(error => {
                button.disabled = false;
                console.error('Error toggling like:', error);
            });
        }
    </script>
</body>
</html>