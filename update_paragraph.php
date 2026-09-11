<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$id = $_POST['id'];
$topic_id = $_POST['topic_id'];
$content = trim($_POST['content']);
$allow_upload = isset($_POST['allow_upload']) ? 1 : 0;
$is_heading = isset($_POST['is_heading']) ? 1 : 0;

// ✅ ตรวจสอบว่ามีข้อความนี้อยู่จริง (ผู้ใช้ทุกคนแก้ไขได้)
$stmt = $pdo->prepare("SELECT p.*, t.user_id FROM paragraphs p JOIN topics t ON p.topic_id = t.id WHERE p.id = ?");
$stmt->execute([$id]);
$paragraph = $stmt->fetch();

if (!$paragraph) {
    die("Paragraph not found");
}

// ✅ อัปเดตข้อความ
$stmt = $pdo->prepare("UPDATE paragraphs SET content = ?, allow_upload = ?, is_heading = ? WHERE id = ?");
$stmt->execute([$content, $allow_upload, $is_heading, $id]);

$_SESSION['message'] = "แก้ไขข้อความเรียบร้อยแล้ว";
header("Location: view_topic.php?id=$topic_id");