<?php
session_start();
require '../db.php';

$username = trim($_POST['username']);
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$role = $_POST['role'] === 'admin' ? 'admin' : 'user';

try {
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute([$username, $password, $role]);

    $_SESSION['message'] = "✅ เพิ่มผู้ใช้ใหม่เรียบร้อยแล้ว";
    header("Location: index.php");
    exit;
} catch (PDOException $e) {
    $_SESSION['message'] = "❌ ไม่สามารถเพิ่มผู้ใช้ได้: " . $e->getMessage();
    header("Location: index.php");
    exit;
}