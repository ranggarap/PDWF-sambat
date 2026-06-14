<?php
// config/db.php

$host = 'localhost';
$dbname = 'sambat_db';
$username = 'root'; // Username default XAMPP
$password = ''; // Password default XAMPP biasanya kosong

try {
    // Membuat koneksi PDO baru
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set mode error PDO menjadi Exception agar aman dan mudah di-debug
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Kembalikan hasil query sebagai array asosiatif secara default
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Jika koneksi gagal, hentikan eksekusi dan tampilkan error
    die("Koneksi database gagal: " . $e->getMessage());
}
function timeAgo($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);

    if ($seconds <= 60) {
        return "Baru saja";
    } elseif ($minutes <= 60) {
        return $minutes == 1 ? "1 menit lalu" : "$minutes menit lalu";
    } elseif ($hours <= 24) {
        return $hours == 1 ? "1 jam lalu" : "$hours jam lalu";
    } else {
        return $days == 1 ? "1 hari lalu" : "$days hari lalu";
    }
}
?>