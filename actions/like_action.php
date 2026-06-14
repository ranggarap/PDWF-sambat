<?php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $post_id = isset($data['post_id']) ? intval($data['post_id']) : null;
    $user_id = $_SESSION['user_id'];
    if ($post_id) {
        // Cek apakah post itu ada
        $checkPost = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
        $checkPost->execute([$post_id]);
        if ($checkPost->rowCount() === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Post not found']);
            exit();
        }
        // Cek apakah user sudah like
        $stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
        
        if ($stmt->rowCount() > 0) {
            // Hapus like
            $del = $pdo->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
            $del->execute([$post_id, $user_id]);
            $action = 'unliked';
        } else {
            // Tambah like
            $ins = $pdo->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
            $ins->execute([$post_id, $user_id]);
            $action = 'liked';
        }
        // Hitung ulang total like terbaru
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
        $countStmt->execute([$post_id]);
        $total_likes = $countStmt->fetchColumn();
        echo json_encode([
            'status' => 'success',
            'action' => $action,
            'total_likes' => intval($total_likes)
        ]);
        exit();
    }
}
echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
exit();
?>
?>