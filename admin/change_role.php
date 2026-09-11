<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$id = $_GET['id'] ?? null;
$role = $_GET['role'] ?? null;

$valid_roles = ['user', 'admin'];
if (!$id || !in_array($role, $valid_roles)) {
    $_SESSION['message'] = "❌ ค่าที่ส่งมาไม่ถูกต้อง";
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
$stmt->execute([$role, $id]);

$_SESSION['message'] = "🔁 เปลี่ยนสิทธิ์ผู้ใช้เรียบร้อยแล้ว";
header("Location: index.php");
exit;
