<?php
session_start();
require_once '../config/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : null;
    $user_id = $_SESSION['user_id'];
    $comment_text = trim($_POST['comment_text'] ?? '');
    if ($post_id && !empty($comment_text)) {
        // Cek apakah post ada
        $checkPost = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
        $checkPost->execute([$post_id]);
        if ($checkPost->rowCount() > 0) {
            $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, comment_text) VALUES (?, ?, ?)");
            $stmt->execute([$post_id, $user_id, $comment_text]);
        }
    }
}
// Redirect dinamis ke halaman asal, jika tidak ada fallback ke index.php
$redirect = $_SERVER['HTTP_REFERER'] ?? '../index.php';
header("Location: " . $redirect);
exit();
?>