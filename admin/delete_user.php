<?php
session_start();
require '../db.php';

// ตรวจสิทธิ์ admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    $_SESSION['message'] = "❌ คุณไม่มีสิทธิ์ลบผู้ใช้";
    header("Location: index.php");
    exit;
}

// ตรวจว่า id ถูกส่งมาหรือไม่
$id = $_GET['id'] ?? null;
if (!$id) {
    $_SESSION['message'] = "❌ ไม่พบผู้ใช้ที่ต้องการลบ";
    header("Location: index.php");
    exit;
}

// ลบผู้ใช้
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$id]);

$_SESSION['message'] = "🗑️ ลบผู้ใช้เรียบร้อยแล้ว";
header("Location: index.php");
exit;