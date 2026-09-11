<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$title = trim($_POST['title']);
$user_id = $_SESSION['user']['id'];
$group_id = $_POST['group_id'] ?? null;

if (!$group_id || trim($title) === '') {
    $_SESSION['message'] = "กรุณาเลือกหัวข้อและระบุชื่อเรื่อง";
    header("Location: topics.php?group=" . $group_id);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO topics (user_id, title, group_id) VALUES (?, ?, ?)");
$stmt->execute([$user_id, $title, $group_id]);

$_SESSION['message'] = "เพิ่มเรื่องใหม่เรียบร้อยแล้ว";
header("Location: topics.php?group=" . $group_id);
exit;