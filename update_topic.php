<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$topic_id = $_POST['topic_id'];
$title = trim($_POST['title']);
$user_id = $_SESSION['user']['id'];

$stmt = $pdo->prepare("SELECT * FROM topics WHERE id = ?");
$stmt->execute([$topic_id]);
$topic = $stmt->fetch();

if (!$topic) {
    die("Topic not found");
}

$stmt = $pdo->prepare("UPDATE topics SET title = ? WHERE id = ?");
$stmt->execute([$title, $topic_id]);

$_SESSION['message'] = "แก้ไขเรื่องเรียบร้อยแล้ว";
header("Location: topics.php?group=" . $topic['group_id']);
exit;