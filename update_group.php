<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

$group_id = $_POST['id'] ?? null;
$new_name = trim($_POST['name'] ?? '');
$visibility = $_POST['visibility'] ?? null;
$category_id = $_POST['category_id'] ?? null;

// ตรวจสอบค่าเบื้องต้น
if (!is_numeric($group_id)) {
    $_SESSION['message'] = "❌ รหัสหัวข้อไม่ถูกต้อง";
    header("Location: categories.php");
    exit;
}

if ($new_name === '') {
    $_SESSION['message'] = "❌ กรุณากรอกชื่อหัวข้อ";
    header("Location: categories.php");
    exit;
}

if (!in_array($visibility, ['public', 'internal', 'inherit'])) {
    $_SESSION['message'] = "❌ ค่าสิทธิ์หัวข้อไม่ถูกต้อง";
    header("Location: categories.php");
    exit;
}

if (!is_numeric($category_id)) {
    $_SESSION['message'] = "❌ หมวดที่เลือกไม่ถูกต้อง";
    header("Location: categories.php");
    exit;
}

// ตรวจว่าหัวข้อมีอยู่จริง
$stmt = $pdo->prepare("SELECT * FROM topic_groups WHERE id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group) {
    $_SESSION['message'] = "❌ ไม่พบหัวข้อที่ต้องการแก้ไข";
    header("Location: categories.php");
    exit;
}

// ตรวจชื่อซ้ำ
$stmt = $pdo->prepare("SELECT COUNT(*) FROM topic_groups WHERE name = ? AND id != ?");
$stmt->execute([$new_name, $group_id]);
if ($stmt->fetchColumn() > 0) {
    $_SESSION['message'] = "⚠️ มีหัวข้อชื่อเดียวกันอยู่แล้ว";
    header("Location: categories.php");
    exit;
}

// อัปเดตข้อมูลหัวข้อ
$stmt = $pdo->prepare("
    UPDATE topic_groups
    SET name = ?, visibility = ?, category_id = ?
    WHERE id = ?
");
$stmt->execute([$new_name, $visibility, $category_id, $group_id]);

$_SESSION['message'] = "✅ แก้ไขหัวข้อเรียบร้อยแล้ว";
header("Location: categories.php");
exit;
