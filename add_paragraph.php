<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$content = trim($_POST['content']);
$topic_id = $_POST['topic_id'];
$allow_upload = isset($_POST['allow_upload']) ? 1 : 0;

$is_heading = isset($_POST['is_heading']) ? 1 : 0;

$stmt = $pdo->prepare("INSERT INTO paragraphs (topic_id, content, allow_upload, is_heading) VALUES (?, ?, ?, ?)");
$stmt->execute([$topic_id, $content, $allow_upload, $is_heading]);

$_SESSION['message'] = "เพิ่มข้อความเรียบร้อยแล้ว";
header("Location: view_topic.php?id=$topic_id");