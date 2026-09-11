<?php
require 'db.php';
require 'lib/GoogleAuthenticator.php';

session_start();

if (!isset($_SESSION['pending_user'])) {
    header("Location: index.php");
    exit;
}

$uid = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);

$dbUser = $stmt->fetch();

$ga = new PHPGangsta_GoogleAuthenticator();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);

    error_log("SECRET = " . $dbUser['twofa_secret']);
    error_log("OTP = " . $code);


    if ($ga->verifyCode($dbUser['twofa_secret'], $code, 2)) {

        // อัปเดตสถานะ 2FA
        $update = $pdo->prepare("UPDATE users SET twofa_enabled = 1 WHERE id = ?");
        $update->execute([$dbUser['id']]);

        // โหลดข้อมูลใหม่จากฐานข้อมูล
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$dbUser['id']]);
        $updatedUser = $stmt->fetch();

        // เซ็ต session เป็นข้อมูลล่าสุด
        $_SESSION['user'] = $updatedUser;
        unset($_SESSION['pending_user']);

        // redirect ตาม role ที่ถูกต้อง
        if ($updatedUser['role'] === 'admin') {
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
    <title>ยืนยัน 2FA | ระบบ Savefile</title>

    <!-- Icon -->
    <link rel="icon" href="/savefile/assets/icons/health48.png" type="image/png">
    <link rel="apple-touch-icon" href="/savefile/assets/icons/health48.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Theme -->
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
                    <div class="alert alert-danger" role="alert">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="text" name="code" class="form-control text-center mb-3" placeholder="กรอกรหัส 6 หลัก"
                        maxlength="6" required autofocus>

                    <button type="submit" class="btn btn-primary w-100">ยืนยัน</button>
                </form>

                <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm mt-3">กลับ</a>
            </div>

        </div>
    </div>
</body>

</html>