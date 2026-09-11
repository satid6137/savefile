<?php
session_start();
require 'db.php';

// ✅ ตรวจว่า login แล้ว
if (!isset($_SESSION['user'])) {
    $_SESSION['message'] = "❌ กรุณาเข้าสู่ระบบก่อน";
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$group_id = $_POST['group_id'] ?? null;

// ✅ debug: log เพื่อเช็คว่าโพสต์ค่ามาถูกไหม
file_put_contents('debug_delete.txt', print_r($_POST, true));

// ✅ ตรวจว่า group_id เป็นตัวเลข
if (!is_numeric($group_id)) {
    $_SESSION['message'] = "❌ รหัสหัวข้อไม่ถูกต้อง";
    header("Location: categories.php");
    exit;
}

// ✅ ดึงข้อมูลหัวข้อจากฐานข้อมูล
$stmt = $pdo->prepare("SELECT * FROM topic_groups WHERE id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group) {
    $_SESSION['message'] = "❌ ไม่พบหัวข้อที่ต้องการลบ";
    header("Location: categories.php");
    exit;
}

// ✅ ตรวจสิทธิ์: ต้องเป็น admin หรือผู้สร้างหัวข้อนั้น
if ($user['role'] !== 'admin' && $group['created_by'] != $user['id']) {
    $_SESSION['message'] = "❌ คุณไม่มีสิทธิ์ลบหัวข้อนี้";
    header("Location: categories.php");
    exit;
}

// ✅ ตรวจว่ามีเรื่องอยู่ในหัวข้อนี้หรือไม่
$stmt = $pdo->prepare("SELECT COUNT(*) FROM topics WHERE group_id = ?");
$stmt->execute([$group_id]);
$topicCount = $stmt->fetchColumn();

if ($topicCount > 0) {
    $_SESSION['message'] = "❌ ไม่สามารถลบหัวข้อได้ เนื่องจากยังมีเรื่องอยู่ภายในหัวข้อนี้ ($topicCount เรื่อง)";
    header("Location: categories.php");
    exit;
}

// ✅ ลบหัวข้อ
$stmt = $pdo->prepare("DELETE FROM topic_groups WHERE id = ?");
$stmt->execute([$group_id]);

$_SESSION['message'] = "✅ ลบหัวข้อ \"" . htmlspecialchars($group['name']) . "\" เรียบร้อยแล้ว";
header("Location: categories.php");
exit;