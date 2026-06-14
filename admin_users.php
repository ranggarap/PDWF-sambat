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

// Ambil semua pengguna di database
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

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
    <title>Kelola Pengguna - Sambat Admin</title>
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
                        <a href="admin_users.php" class="flex items-center gap-4 p-4 rounded-2xl font-bold transition-all duration-300 sambat-sidebar-link active">
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

            <!-- ================= UTAMA (MIDDLE KELOLA USERS) ================= -->
            <main class="col-span-12 md:col-span-9 min-h-screen pb-24 md:pb-8 pt-4 md:pt-6 px-4 md:px-6">
                <div class="mb-8">
                    <h2 class="text-3xl font-extrabold tracking-tight sambat-text-primary">👥 Kelola Pengguna</h2>
                    <p class="text-sm mt-1 sambat-text-muted">Daftar lengkap pengguna terdaftar. Anda dapat mengubah peran admin atau menghapus akun secara permanen.</p>
                </div>

                <!-- Tabel Pengguna Glassmorphism -->
                <div class="sambat-card rounded-3xl overflow-hidden shadow-xl">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b sambat-border text-xs font-bold uppercase tracking-wider sambat-text-muted">
                                    <th class="p-4 pl-6">Pengguna</th>
                                    <th class="p-4">Email</th>
                                    <th class="p-4">Status Peran</th>
                                    <th class="p-4">Tanggal Daftar</th>
                                    <th class="p-4 pr-6 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y sambat-border text-sm">
                                <?php foreach ($users as $u): 
                                    $uAvatar = getAvatarUrl($u['username'], $u['avatar_path']);
                                    $isMe = ($u['id'] == $user_id);
                                    $isAdmin = (intval($u['is_admin']) === 1);
                                ?>
                                    <tr class="hover:bg-white/[0.01] transition-colors">
                                        <!-- Pengguna Info -->
                                        <td class="p-4 pl-6 flex items-center gap-3">
                                            <img src="<?= $uAvatar ?>" class="w-9 h-9 rounded-full object-cover">
                                            <div>
                                                <p class="font-bold sambat-text-primary"><?= htmlspecialchars($u['full_name'] ?? $u['username']) ?></p>
                                                <p class="text-xs sambat-text-muted">@<?= htmlspecialchars($u['username']) ?></p>
                                            </div>
                                        </td>
                                        
                                        <!-- Email -->
                                        <td class="p-4 sambat-text-secondary">
                                            <?= htmlspecialchars($u['email']) ?>
                                        </td>
                                        
                                        <!-- Status Peran (Admin/User) -->
                                        <td class="p-4">
                                            <?php if ($isAdmin): ?>
                                                <span class="bg-red-500/10 text-red-400 border border-red-500/20 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">
                                                    Admin
                                                </span>
                                            <?php else: ?>
                                                <span class="bg-gray-500/10 text-gray-400 border border-gray-500/20 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">
                                                    User
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Tanggal Daftar -->
                                        <td class="p-4 text-xs sambat-text-muted">
                                            <?= date('d M Y H:i', strtotime($u['created_at'])) ?>
                                        </td>
                                        
                                        <!-- Aksi Kelola -->
                                        <td class="p-4 pr-6 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <!-- Toggle Admin Status -->
                                                <form action="actions/admin_toggle_role.php" method="POST">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <button type="submit" <?= $isMe ? 'disabled' : '' ?>
                                                            class="font-bold text-xs px-3 py-1.5 rounded-xl transition-all duration-200 <?= $isMe ? 'bg-gray-800 text-gray-600 cursor-not-allowed opacity-40' : ($isAdmin ? 'bg-gray-500/10 hover:bg-gray-500/20 text-gray-300 border border-white/[0.05]' : 'bg-red-600 hover:bg-red-500 text-white shadow-md shadow-red-600/10') ?>">
                                                        <?= $isAdmin ? 'Jadikan User' : 'Jadikan Admin' ?>
                                                    </button>
                                                </form>

                                                <!-- Hapus Pengguna -->
                                                <form action="actions/admin_delete_user.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna @<?= $u['username'] ?> secara permanen? Semua postingan, foto, like, dan komentar mereka akan terhapus.');">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <button type="submit" <?= $isMe ? 'disabled' : '' ?>
                                                            class="font-bold text-xs px-3 py-1.5 rounded-xl transition-all duration-200 <?= $isMe ? 'bg-gray-800 text-gray-600 cursor-not-allowed opacity-40' : 'bg-red-950/20 hover:bg-red-900/30 border border-red-500/10 text-red-500' ?>">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>

        </div>
    </div>
</body>
</html>
