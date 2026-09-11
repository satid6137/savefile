<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$id = $_POST['id'] ?? 0;
$name = trim($_POST['name'] ?? '');
$visibility = $_POST['visibility'] ?? 'internal';

if ($name === '') {
    $_SESSION['message'] = "กรุณาระบุชื่อหมวด";
    header("Location: categories.php");
    exit;
}

$stmt = $pdo->prepare("
    UPDATE categories
    SET name = ?, visibility = ?
    WHERE id = ?
");
$stmt->execute([$name, $visibility, $id]);

$_SESSION['message'] = "แก้ไขหมวดเรียบร้อยแล้ว";
header("Location: categories.php");
exit;
