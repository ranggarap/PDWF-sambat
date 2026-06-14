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

    // Jangan izinkan mengubah peran diri sendiri
    if ($target_user_id && $target_user_id !== $current_user_id) {
        // Ambil peran target saat ini
        $stmt_target = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt_target->execute([$target_user_id]);
        $target = $stmt_target->fetch();

        if ($target) {
            $new_role = intval($target['is_admin']) === 1 ? 0 : 1;
            
            // Update peran di DB
            $update = $pdo->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
            $update->execute([$new_role, $target_user_id]);
        }
    }
}

header("Location: ../admin_users.php");
exit();
?>
