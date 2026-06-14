<?php
session_start();
require_once '../config/db.php';

// Cek autentikasi & peran admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Ambil status admin pengakses
$stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->execute([$current_user_id]);
$me = $stmt->fetch();

if (!$me || intval($me['is_admin']) !== 1) {
    header("Location: ../index.php"); // Tolak akses jika bukan admin
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;

    // Jangan izinkan menghapus diri sendiri
    if ($target_user_id && $target_user_id !== $current_user_id) {
        // Hapus avatar user dari disk server jika bukan avatar default
        $stmt_avatar = $pdo->prepare("SELECT avatar_path FROM users WHERE id = ?");
        $stmt_avatar->execute([$target_user_id]);
        $avatar_path = $stmt_avatar->fetchColumn();
        
        if ($avatar_path && $avatar_path !== 'default_avatar.png') {
            $avatar_file = '../uploads/avatars/' . $avatar_path;
            if (file_exists($avatar_file)) {
                unlink($avatar_file);
            }
        }

        // Hapus semua foto postingan milik user ini dari disk server
        $stmt_posts = $pdo->prepare("SELECT image_path FROM posts WHERE user_id = ? AND image_path IS NOT NULL");
        $stmt_posts->execute([$target_user_id]);
        $post_images = $stmt_posts->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($post_images as $image) {
            $image_file = '../uploads/posts/' . $image;
            if (file_exists($image_file)) {
                unlink($image_file);
            }
        }

        // Hapus user dari DB (Relasi ON DELETE CASCADE akan otomatis menghapus postingan, like, komentar, dll)
        $delete = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $delete->execute([$target_user_id]);
    }
}

header("Location: ../admin_users.php");
exit();
?>
