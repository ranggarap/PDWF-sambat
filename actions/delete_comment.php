<?php
// actions/delete_comment.php
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
    $comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : null;

    if ($comment_id) {
        // Cek pembuat komentar
        $stmt_comment = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
        $stmt_comment->execute([$comment_id]);
        $comment = $stmt_comment->fetch();

        if ($comment) {
            // Pengguna hanya boleh menghapus komentar milik sendiri, kecuali admin
            if (intval($comment['user_id']) === $user_id || $is_admin) {
                $delete = $pdo->prepare("DELETE FROM comments WHERE id = ?");
                $delete->execute([$comment_id]);
            }
        }
    }
}

// Redirect dinamis ke halaman asal
$redirect = $_SERVER['HTTP_REFERER'] ?? '../index.php';
header("Location: " . $redirect);
exit();
?>
