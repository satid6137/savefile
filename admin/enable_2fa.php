<?php
require '../db.php';
require '../lib/GoogleAuthenticator.php';

session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

$uid = intval($_GET['id']);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

if (!$user) {
    die("ไม่พบผู้ใช้งาน");
}

$ga = new PHPGangsta_GoogleAuthenticator();

// สร้าง secret ใหม่
$secret = $ga->createSecret();

// สร้าง QR Code URL (ชื่อสวยขึ้น)
$issuer = "Savefile";
$account = "โรงพยาบาลห้างฉัตร:User-$uid";
$qrCodeUrl = $ga->getQRCodeGoogleUrl("$issuer:$account", $secret, $issuer);

// บันทึก secret ลงฐานข้อมูล
$stmt = $pdo->prepare("UPDATE users SET twofa_secret = ?, twofa_enabled = 0 WHERE id = ?");
$stmt->execute([$secret, $uid]);

$_SESSION['pending_user'] = $user;

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>เปิดใช้งาน 2FA | ระบบ Savefile</title>

    <link rel="icon" href="/savefile/assets/icons/health48.png" type="image/png">
    <link rel="apple-touch-icon" href="/savefile/assets/icons/health48.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/savefile/assets/css/theme.css" rel="stylesheet">
</head>

<body>
    <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="w-100 text-center" style="max-width: 420px;">

            <img src="/savefile/assets/icons/11156-5.png" width="150" height="150" class="mb-2">

            <div class="hos-auth-card mt-3">
                <h1 class="hos-page-title text-center mb-3">เปิดใช้งาน 2FA</h1>

                <p class="text-muted mb-2">สแกน QR Code นี้ด้วย Google Authenticator</p>

                <img src="<?= $qrCodeUrl ?>" class="mb-3" style="width: 240px; height: 240px;">

                <div class="alert alert-secondary text-start">
                    <strong>Secret:</strong>
                    <?= $secret ?><br>
                    <small class="text-muted">ใช้กรณีสแกน QR Code ไม่ได้</small>
                </div>

                <!-- ปุ่มไปหน้า verify -->
                <a href="/savefile/2fa_verify.php?id=<?= $uid ?>" class="btn btn-primary w-100 mt-3">
                    ดำเนินการยืนยันรหัส 2FA
                </a>

                <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm mt-3">กลับ</a>
            </div>

        </div>
    </div>
</body>

</html>