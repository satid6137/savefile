<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

// รับค่าจากฟอร์ม
$name = trim($_POST['group_name'] ?? '');
$category_id = $_POST['category_id'] ?? null;
$visibility = $_POST['visibility'] ?? 'inherit';

// ตรวจสอบชื่อหัวข้อ
if ($name === '') {
    $_SESSION['message'] = "❌ กรุณาระบุชื่อหัวข้อ";
    header("Location: categories.php");
    exit;
}

// ตรวจสอบหมวด
if (!is_numeric($category_id)) {
    $_SESSION['message'] = "❌ หมวดไม่ถูกต้อง";
    header("Location: categories.php");
    exit;
}

// ตรวจสอบสิทธิ์หัวข้อ
if (!in_array($visibility, ['public', 'internal', 'inherit'])) {
    $_SESSION['message'] = "❌ ค่าสิทธิ์หัวข้อไม่ถูกต้อง";
    header("Location: categories.php");
    exit;
}

// บันทึกหัวข้อใหม่
$stmt = $pdo->prepare("
    INSERT INTO topic_groups (name, category_id, visibility, created_by)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$name, $category_id, $visibility, $user['id']]);

$_SESSION['message'] = "✅ เพิ่มหัวข้อใหม่เรียบร้อยแล้ว";
header("Location: categories.php");
exit;
