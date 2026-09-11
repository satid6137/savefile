<?php
require '../db.php';
session_start();

// ต้อง login ก่อน
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

$user = $_SESSION['user'];

// เฉพาะ admin เท่านั้นที่ disable 2FA
if ($user['role'] !== 'admin') {
    header("Location: ../dashboard.php");
    exit;
}

// อัปเดตสถานะ 2FA ของ admin คนที่ login อยู่จริง
$stmt = $pdo->prepare("UPDATE users SET twofa_enabled = 0, twofa_secret = NULL WHERE id = ?");
$stmt->execute([$user['id']]);

// โหลดข้อมูลใหม่เข้ากลับ session
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$_SESSION['user'] = $stmt->fetch();

// กลับไปหน้า admin
header("Location: index.php");
exit;
