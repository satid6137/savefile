<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$topic_id = $_POST['topic_id'];

// ตรวจสอบว่ามีเรื่องนี้อยู่จริง (ผู้ใช้ทุกคนลบได้ — สิทธิ์ที่จำกัดคือลบ "หัวข้อ"/กลุ่มเท่านั้น)
$stmt = $pdo->prepare("SELECT * FROM topics WHERE id = ?");
$stmt->execute([$topic_id]);
$topic = $stmt->fetch();

if (!$topic) {
    die("Topic not found");
}

// ตรวจสอบว่ามีข้อความใน topic หรือไม่
$stmt = $pdo->prepare("SELECT COUNT(*) FROM paragraphs WHERE topic_id = ?");
$stmt->execute([$topic_id]);
$paragraph_count = $stmt->fetchColumn();

if ($paragraph_count > 0) {
    $_SESSION['message'] = "ไม่สามารถลบเรื่องนี้ได้ เนื่องจากมีข้อความอยู่ในเรื่องนี้";
    header("Location: topics.php?group=" . $topic['group_id']);
    exit;
}

// ลบ topic
$stmt = $pdo->prepare("DELETE FROM topics WHERE id = ?");
$stmt->execute([$topic_id]);

$_SESSION['message'] = "ลบเรื่องเรียบร้อยแล้ว";
header("Location: topics.php?group=" . $topic['group_id']);
exit;