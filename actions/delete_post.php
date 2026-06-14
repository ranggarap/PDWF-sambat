<?php
// actions/delete_post.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil info user aktif untuk mengecek status admin
$stmt_me = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt_me->execute([$user_id]);
$me = $stmt_me->fetch();
$is_admin = $me && intval($me['is_admin']) === 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : null;

    if ($post_id) {
        // Cek pembuat postingan dan ambil file gambar
        $stmt_post = $pdo->prepare("SELECT user_id, image_path FROM posts WHERE id = ?");
        $stmt_post->execute([$post_id]);
        $post = $stmt_post->fetch();

        if ($post) {
            // Pengguna hanya boleh menghapus postingan milik sendiri, kecuali admin
            if (intval($post['user_id']) === $user_id || $is_admin) {
                // Hapus file gambar dari server jika ada
                if (!empty($post['image_path'])) {
                    $image_file = '../uploads/posts/' . $post['image_path'];
                    if (file_exists($image_file)) {
                        unlink($image_file);
                    }
                }

                // Hapus postingan (relasi database CASCADE akan menghapus like & komentar terkait)
                $delete = $pdo->prepare("DELETE FROM posts WHERE id = ?");
                $delete->execute([$post_id]);
            }
        }
    }
}

// Redirect dinamis ke halaman asal
$redirect = $_SERVER['HTTP_REFERER'] ?? '../index.php';
header("Location: " . $redirect);
exit();
?>
