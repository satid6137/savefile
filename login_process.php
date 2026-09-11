<?php
session_start();
require 'db.php';

$username = trim($_POST['username']);
$password = $_POST['password'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user) {

    // ⭐ เพิ่มตรงนี้ — ถ้าถูกปิดการใช้งาน ห้าม login
    if ($user['status'] == 0) {
        $_SESSION['login_error'] = "บัญชีนี้ถูกปิดการใช้งาน";
        header("Location: index.php");
        exit;
    }

    if (password_verify($password, $user['password'])) {

        // ถ้า user เปิด 2FA → ห้าม login ทันที
        if ($user['twofa_enabled'] == 1) {

            // เก็บ user ไว้ก่อน แต่ยังไม่ให้เข้า dashboard/admin
            $_SESSION['pending_user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ];

            // ส่งไปหน้าใส่รหัส 2FA
            header("Location: /savefile/2fa_login_verify.php");
            exit;
        }

        // ถ้าไม่เปิด 2FA → login ปกติ
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ];

        // Redirect ตาม role
        if ($user['role'] === 'admin') {
            header("Location: admin/index.php");
        } else {
            header("Location: dashboard.php");
        }
        exit;
    }
}

// ถ้า username/password ไม่ถูกต้อง
$_SESSION['login_error'] = "Invalid credentials.";
header("Location: index.php");
exit;
