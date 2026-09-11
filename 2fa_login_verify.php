<?php
require 'db.php';
require 'lib/GoogleAuthenticator.php';
session_start();

// ตอน login ต้องใช้ pending_user ไม่ใช่ user
if (!isset($_SESSION['pending_user'])) {
    header("Location: index.php");
    exit;
}

$pending = $_SESSION['pending_user'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$pending['id']]);
$dbUser = $stmt->fetch();

$ga = new PHPGangsta_GoogleAuthenticator();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);

    if ($ga->verifyCode($dbUser['twofa_secret'], $code, 2)) {

        // ผ่าน 2FA → login สำเร็จ
        $_SESSION['user'] = $dbUser;
        unset($_SESSION['pending_user']);

        // redirect ตาม role
        if ($dbUser['role'] === 'admin') {
            header("Location: admin/index.php");
        } else {
            header("Location: dashboard.php");
        }
        exit;

    } else {
        $error = "รหัส 2FA ไม่ถูกต้อง";
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ยืนยันรหัส 2FA | ระบบ Savefile</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/savefile/assets/css/theme.css" rel="stylesheet">

</head>

<body>
    <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="w-100 text-center" style="max-width: 420px;">

            <!-- Logo -->
            <img src="/savefile/assets/icons/11156-5.png" alt="โลโก้โรงพยาบาล" width="300" height="300" class="mb-2">

            <!-- Card -->
            <div class="hos-auth-card mt-3">
                <h1 class="hos-page-title text-center mb-3">ยืนยันรหัส 2FA</h1>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="text" name="code" class="form-control text-center mb-3" placeholder="กรอกรหัส 6 หลัก"
                        maxlength="6" required autofocus>

                    <button type="submit" class="btn btn-primary w-100">
                        ยืนยัน
                    </button>
                </form>

            </div>

        </div>
    </div>
</body>


</html>