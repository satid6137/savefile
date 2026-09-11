<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$group_id = $_POST['group_id'] ?? null;
$new_name = trim($_POST['new_name'] ?? '');

if (!$group_id || $new_name === '') {
    $_SESSION['message'] = "❌ กรุณากรอกชื่อหัวข้อใหม่";
    header("Location: dashboard.php");
    exit;
}

$stmt = $pdo->prepare("UPDATE topic_groups SET name = ? WHERE id = ?");
$stmt->execute([$new_name, $group_id]);

$_SESSION['message'] = "✅ แก้ไขหัวข้อเรียบร้อยแล้ว";
header("Location: dashboard.php");
exit;