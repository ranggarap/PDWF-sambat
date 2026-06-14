<?php
session_start();
require_once '../config/db.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $content = trim($_POST['content_text'] ?? '');
    $user_id = $_SESSION['user_id'];
    $image_name = null;
    // Pastikan folder uploads/posts ada
    $upload_dir = '../uploads/posts/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    // Logika Upload Foto
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = mime_content_type($_FILES['image']['tmp_name']);
        
        if (in_array($file_type, $allowed_types)) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = uniqid('post_') . '.' . $ext; // Nama file unik
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_name);
        }
    }
    // Hanya masukkan ke database jika ada konten teks ATAU ada gambar terunggah
    if (!empty($content) || $image_name !== null) {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content_text, image_path) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $content, $image_name]);
    }
}
header("Location: ../index.php");
exit();
?>