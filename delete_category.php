<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? 0;

// เช็คว่ามีหัวข้ออยู่ไหม
$stmt = $pdo->prepare("SELECT COUNT(*) FROM topic_groups WHERE category_id = ?");
$stmt->execute([$id]);
$hasGroups = $stmt->fetchColumn() > 0;

if ($hasGroups) {
    $_SESSION['message'] = "มีหัวข้ออยู่ในหมวดนี้ ไม่สามารถลบได้";
    header("Location: categories.php");
    exit;
}

// ลบหมวด
$stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
$stmt->execute([$id]);

$_SESSION['message'] = "ลบหมวดเรียบร้อยแล้ว";
header("Location: categories.php");
exit;
