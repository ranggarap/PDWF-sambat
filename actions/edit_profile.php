<?php
session_start();
require_once '../config/db.php';

// Cek autentikasi
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $birthdate = !empty($_POST['birthdate']) ? $_POST['birthdate'] : null;
    
    // Pastikan folder uploads/avatars ada
    $upload_dir = '../uploads/avatars/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $avatar_name = null;
    
    // Logika upload avatar
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = mime_content_type($_FILES['avatar']['tmp_name']);
        
        if (in_array($file_type, $allowed_types)) {
            $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $avatar_name = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
            
            // Pindahkan file baru
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $avatar_name)) {
                // Hapus avatar lama jika bukan default
                $oldStmt = $pdo->prepare("SELECT avatar_path FROM users WHERE id = ?");
                $oldStmt->execute([$user_id]);
                $oldAvatar = $oldStmt->fetchColumn();
                if ($oldAvatar && $oldAvatar !== 'default_avatar.png' && file_exists($upload_dir . $oldAvatar)) {
                    unlink($upload_dir . $oldAvatar);
                }
                
                // Update avatar path ke DB
                $updateAvatar = $pdo->prepare("UPDATE users SET avatar_path = ? WHERE id = ?");
                $updateAvatar->execute([$avatar_name, $user_id]);
            }
        }
    }

    // Update data profil lain
    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, bio = ?, birthdate = ? WHERE id = ?");
    $stmt->execute([$full_name, $bio, $birthdate, $user_id]);

    header("Location: ../profile.php?success=1");
    exit();
}

header("Location: ../profile.php");
exit();
?>