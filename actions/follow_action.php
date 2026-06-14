<?php
session_start();
require_once '../config/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $follower_id = $_SESSION['user_id'];
    $following_id = isset($_POST['following_id']) ? intval($_POST['following_id']) : null;
    // Jangan izinkan follow diri sendiri dan pastikan ID valid
    if ($following_id && $follower_id !== $following_id) {
        // Pastikan user target memang ada
        $checkUser = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $checkUser->execute([$following_id]);
        
        if ($checkUser->rowCount() > 0) {
            // Cek apakah sudah follow
            $stmt = $pdo->prepare("SELECT * FROM follows WHERE follower_id = ? AND following_id = ?");
            $stmt->execute([$follower_id, $following_id]);
            if ($stmt->rowCount() > 0) {
                // Jika sudah follow, maka Unfollow (hapus dari database)
                $del = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
                $del->execute([$follower_id, $following_id]);
            } else {
                // Jika belum, Follow (masukkan ke database)
                $ins = $pdo->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
                $ins->execute([$follower_id, $following_id]);
            }
        }
    }
}
// Kembalikan ke halaman sebelumnya secara aman
$redirect = $_SERVER['HTTP_REFERER'] ?? '../index.php';
header("Location: " . $redirect);
exit();
?>
?>