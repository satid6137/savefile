<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("UPDATE users SET status = 1 WHERE id = ?");
$stmt->execute([$id]);

$_SESSION['message'] = "ผู้ใช้ถูกเปิดการใช้งานแล้ว";
header("Location: index.php");
exit;
