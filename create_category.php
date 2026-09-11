<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

$name = trim($_POST['name'] ?? '');
$visibility = $_POST['visibility'] ?? 'internal';

if ($name === '') {
    $_SESSION['message'] = "กรุณาระบุชื่อหมวด";
    header("Location: categories.php");
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO categories (name, visibility, created_by)
    VALUES (?, ?, ?)
");
$stmt->execute([$name, $visibility, $user['id']]);

$_SESSION['message'] = "เพิ่มหมวดเรียบร้อยแล้ว";
header("Location: categories.php");
exit;
