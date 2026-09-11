<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$paragraph_id = $_POST['paragraph_id'];
$topic_id = $_POST['topic_id'];
$url = trim($_POST['url']);
$filename = trim($_POST['filename']);


if (!filter_var($url, FILTER_VALIDATE_URL)) {
    $_SESSION['message'] = "ลิงก์ไม่ถูกต้อง";
    header("Location: view_topic.php?id=$topic_id");
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO files (paragraph_id, filename, url, type, uploaded_at)
    VALUES (?, ?, ?, 'link', NOW())
");
$stmt->execute([$paragraph_id, $filename, $url]);

$_SESSION['message'] = "เพิ่มลิงก์เรียบร้อยแล้ว";
header("Location: view_topic.php?id=$topic_id");
exit;
