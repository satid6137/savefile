<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$file_id = $_POST['file_id'] ?? null;
$topic_id = $_POST['topic_id'] ?? null;

if (!$file_id || !$topic_id) {
    $_SESSION['message'] = "ข้อมูลไม่ครบถ้วน";
    header("Location: view_topic.php?id=$topic_id");
    exit;
}

// ✅ ดึง path ของไฟล์
$stmt = $pdo->prepare("SELECT filepath FROM files WHERE id = ?");
$stmt->execute([$file_id]);
$file = $stmt->fetch();

if ($file && file_exists($file['filepath'])) {
    unlink($file['filepath']); // ✅ ลบไฟล์จากโฟลเดอร์
}

// ✅ ลบจากฐานข้อมูล
$stmt = $pdo->prepare("DELETE FROM files WHERE id = ?");
$stmt->execute([$file_id]);

$_SESSION['message'] = "ลบไฟล์เรียบร้อยแล้ว";
header("Location: view_topic.php?id=$topic_id");
exit;