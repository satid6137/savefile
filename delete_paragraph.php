<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$paragraph_id = $_POST['paragraph_id'];
$topic_id = $_POST['topic_id'];

// ตรวจสอบว่ามีข้อความนี้อยู่จริง (ผู้ใช้ทุกคนลบได้)
$stmt = $pdo->prepare("SELECT p.*, t.user_id FROM paragraphs p JOIN topics t ON p.topic_id = t.id WHERE p.id = ?");
$stmt->execute([$paragraph_id]);
$paragraph = $stmt->fetch();

if (!$paragraph) {
    die("Paragraph not found");
}

// ดึงไฟล์ที่แนบกับ paragraph
$stmt = $pdo->prepare("SELECT * FROM files WHERE paragraph_id = ?");
$stmt->execute([$paragraph_id]);
$files = $stmt->fetchAll();

// ลบไฟล์จริง
foreach ($files as $file) {
    if (file_exists($file['filepath'])) {
        unlink($file['filepath']);
    }
}

// ลบ paragraph และไฟล์จากฐานข้อมูล
$stmt = $pdo->prepare("DELETE FROM paragraphs WHERE id = ?");
$stmt->execute([$paragraph_id]);

$stmt = $pdo->prepare("DELETE FROM files WHERE paragraph_id = ?");
$stmt->execute([$paragraph_id]);

// หลังจากลบสำเร็จ
$_SESSION['message'] = "ลบข้อความสำเร็จแล้ว";

// เปลี่ยนเส้นทางกลับไปยังเรื่อง
header("Location: view_topic.php?id=$topic_id");
exit;