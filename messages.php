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

$active_chat_user = null;
$messages = [];

// Handle Kirim Pesan Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_text']) && isset($_POST['receiver_id'])) {
    $msg = trim($_POST['message_text']);
    $to_id = intval($_POST['receiver_id']);
    
    if (!empty($msg) && $to_id > 0) {
        // Pastikan penerima ada
        $checkRec = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $checkRec->execute([$to_id]);
        if ($checkRec->rowCount() > 0) {
            $stmt_send = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)");
            $stmt_send->execute([$user_id, $to_id, $msg]);
            header("Location: messages.php?to_user_id=" . $to_id);
            exit();
        }
    }
}

// Ambil daftar user lain (kontak) untuk dichat
$stmt_contacts = $pdo->prepare("SELECT id, username, avatar_path FROM users WHERE id != ? LIMIT 15");
$stmt_contacts->execute([$user_id]);
$contacts = $stmt_contacts->fetchAll();

// Jika sedang membuka chat dengan user tertentu
if (isset($_GET['to_user_id'])) {
    $to_user_id = intval($_GET['to_user_id']);
    
    $stmt_active = $pdo->prepare("SELECT id, username, avatar_path FROM users WHERE id = ?");
    $stmt_active->execute([$to_user_id]);
    $active_chat_user = $stmt_active->fetch();
    if ($active_chat_user) {
        // Ambil riwayat chat antara dua user ini
        $stmt_msg = $pdo->prepare("SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
        $stmt_msg->execute([$user_id, $to_user_id, $to_user_id, $user_id]);
        $messages = $stmt_msg->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan - Sambat</title>
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
                        <a href="messages.php" class="flex items-center gap-4 p-4 rounded-2xl font-bold transition-all duration-300 sambat-sidebar-link active">
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
            
            <!-- ================= INTERFACE CHAT LENGKAP ================= -->
            <main class="col-span-12 md:col-span-10 min-h-screen pb-24 md:pb-0 border-x sambat-border grid grid-cols-12">
                
                <!-- Sub-Kolom 1: Daftar Kontak (4/12 col) -->
                <div class="col-span-12 sm:col-span-4 border-r sambat-border flex flex-col h-[calc(100vh-68px)] sm:h-screen sticky top-0 py-4">
                    <div class="px-4 mb-4">
                        <h2 class="text-xl font-extrabold tracking-tight flex items-center gap-2 sambat-text-primary">
                            <span>✉️</span> Kotak Masuk
                        </h2>
                        <p class="text-[11px] mt-1 sambat-text-muted">Pilih teman bicara unek-unek Anda.</p>
                    </div>
                    
                    <!-- List Kontak Scrollable -->
                    <div class="flex-grow overflow-y-auto px-2 space-y-1">
                        <?php if (empty($contacts)): ?>
                            <p class="text-xs text-center py-6 sambat-text-muted">Belum ada pengguna lain.</p>
                        <?php else: ?>
                            <?php foreach($contacts as $contact): 
                                $cAvatar = getAvatarUrl($contact['username'], $contact['avatar_path']);
                                $isActive = ($active_chat_user && $active_chat_user['id'] == $contact['id']);
                             ?>
                                <a href="messages.php?to_user_id=<?= $contact['id'] ?>" 
                                   class="flex items-center gap-3 p-3 rounded-2xl transition-all duration-200 <?= $isActive ? 'bg-red-500/10 border sambat-border text-red-500 font-bold' : 'sambat-sidebar-link' ?>">
                                    <img src="<?= $cAvatar ?>" class="w-9 h-9 rounded-full object-cover shadow-sm">
                                    <div class="overflow-hidden flex-grow">
                                        <p class="text-xs truncate">@<?= htmlspecialchars($contact['username']) ?></p>
                                    </div>
                                    <?php if ($isActive): ?>
                                        <span class="w-1.5 h-1.5 bg-red-500 rounded-full animate-ping"></span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Sub-Kolom 2: Isi Obrolan (8/12 col) -->
                <div class="col-span-12 sm:col-span-8 flex flex-col h-[calc(100vh-68px)] sm:h-screen sticky top-0 py-4 px-4">
                    <?php if ($active_chat_user): 
                        $activeAvatar = getAvatarUrl($active_chat_user['username'], $active_chat_user['avatar_path']);
                    ?>
                        <!-- Header Chat -->
                        <div class="flex items-center justify-between border-b sambat-border pb-3 mb-4">
                            <div class="flex items-center gap-3">
                                <img src="<?= $activeAvatar ?>" class="w-10 h-10 rounded-full object-cover border sambat-border">
                                <div>
                                    <h3 class="font-bold text-sm sambat-text-primary">@<?= htmlspecialchars($active_chat_user['username']) ?></h3>
                                    <p class="text-[10px] text-green-500 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full inline-block animate-pulse"></span> Terhubung
                                    </p>
                                </div>
                            </div>
                            <a href="profile.php" class="text-xs sambat-text-muted hover:sambat-text-primary transition-colors">Lihat Profil</a>
                        </div>
                        
                        <!-- Area Chat Bubbles -->
                        <div class="flex-grow overflow-y-auto space-y-4 pr-1 pl-1" id="chatWindow">
                            <?php if(empty($messages)): ?>
                                <div class="flex flex-col items-center justify-center py-12 text-center sambat-text-muted">
                                    <span class="text-3xl block mb-2">💬</span>
                                    <p class="text-xs">Belum ada percakapan. Mulai semburkan obrolan sekarang!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($messages as $msg): 
                                    $is_me = ($msg['sender_id'] == $user_id);
                                ?>
                                    <div class="flex <?= $is_me ? 'justify-end' : 'justify-start' ?>">
                                        <div class="flex flex-col max-w-[70%] space-y-1">
                                            <div class="p-3.5 rounded-2xl text-xs leading-relaxed shadow-sm <?= $is_me ? 'sambat-chat-me rounded-tr-none' : 'sambat-chat-other rounded-tl-none' ?>">
                                                <?= htmlspecialchars($msg['message_text']) ?>
                                            </div>
                                            <span class="text-[9px] px-1.5 self-end tracking-wider sambat-text-muted">
                                                <?= date('H:i', strtotime($msg['created_at'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Input Box Kirim Pesan -->
                        <form action="messages.php" method="POST" class="mt-4 border-t sambat-border pt-4">
                            <input type="hidden" name="receiver_id" value="<?= $active_chat_user['id'] ?>">
                            <div class="flex gap-2">
                                <input type="text" name="message_text" placeholder="Ketik pesan rahasia..." required autocomplete="off"
                                       class="w-full text-xs px-4 py-3.5 rounded-2xl outline-none transition-all placeholder-gray-500 sambat-input">
                                <button type="submit" class="bg-red-600 hover:bg-red-500 text-white font-bold px-6 py-3.5 rounded-2xl text-xs transition-all flex items-center gap-1.5 transform active:scale-95 shadow-lg shadow-red-600/10">
                                    <span>Kirim</span>
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <!-- State Chat Belum Dipilih -->
                        <div class="flex flex-col items-center justify-center h-full text-center p-6 sambat-text-muted">
                            <div class="w-16 h-16 bg-white/[0.02] border sambat-border rounded-full flex items-center justify-center mb-4">
                                <span class="text-3xl">✉️</span>
                            </div>
                            <h3 class="text-base font-bold mb-1 sambat-text-primary">Silakan Pilih Teman Bicara</h3>
                            <p class="text-xs max-w-sm">Gunakan daftar di sebelah kiri untuk memilih pengguna lain dan memulai ruang sambatan privat.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
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
            <a href="messages.php" class="text-red-500 text-xl font-bold flex flex-col items-center">
                <span>✉️</span>
                <span class="text-[9px] font-medium tracking-wide mt-0.5 text-red-500">Pesan</span>
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
        // Auto scroll ke bawah di jendela chat agar pesan terbaru langsung kelihatan
        const chatWin = document.getElementById('chatWindow');
        if(chatWin) { 
            chatWin.scrollTop = chatWin.scrollHeight; 
        }
    </script>
</body>
</html>