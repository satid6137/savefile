<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$topic_id = $_POST['topic_id'];

// ตรวจสอบสิทธิ์
$stmt = $pdo->prepare("SELECT * FROM topics WHERE id = ?");
$stmt->execute([$topic_id]);
$topic = $stmt->fetch();

if ($_SESSION['user']['id'] != $topic['user_id'] && $_SESSION['user']['role'] !== 'admin') {
    die("Unauthorized");
}

// ดึงไฟล์ทั้งหมดที่เกี่ยวข้อง
$stmt = $pdo->prepare("SELECT f.filepath FROM files f JOIN paragraphs p ON f.paragraph_id = p.id WHERE p.topic_id = ?");
$stmt->execute([$topic_id]);
$files = $stmt->fetchAll();

// ลบไฟล์จริงจากโฟลเดอร์
foreach ($files as $file) {
    $path = $file['filepath'];
    if (file_exists($path)) {
        unlink($path); // ลบไฟล์
    }
}

// ลบ topic → cascade ลบ paragraphs และ files (ถ้าใช้ foreign key ON DELETE CASCADE)
$stmt = $pdo->prepare("DELETE FROM topics WHERE id = ?");
$stmt->execute([$topic_id]);

header("Location: dashboard.php");