<?php
session_start();
require 'db.php';

$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>เข้าสู่ระบบ | Savefile</title>

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
            <img src="/savefile/assets/icons/11156-5.png" width="150" height="150" class="mb-2">

            <!-- Card -->
            <div class="hos-auth-card mt-3">

                <h1 class="hos-page-title text-center mb-3">เข้าสู่ระบบ</h1>

                <?php if ($error): ?>
                    <div class="alert alert-danger text-start">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login_process.php">

                    <div class="mb-3 text-start">
                        <label class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label">รหัสผ่าน</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mt-2">
                        เข้าสู่ระบบ
                    </button>

                </form>

                <a href="index.php" class="btn btn-outline-secondary btn-sm mt-3">
                    หน้าแรก
                </a>

            </div>
            <div class="login-bar">
                <form method="GET" action="oauth_login.php" class="login-form" style="margin-top:.5rem;">
                    <button type="submit" class="btn" style="background:#2d6a4f;border-color:#2d6a4f;">
                        เข้าสู่ระบบด้วย Provider ID
                    </button>
                </form>
                <div class="page-header">
                    <p>**เจ้าหน้าที่โรงพยาบาลห้างฉัตรให้ เข้าสู่ระบบด้วย Provider ID
                        หากมีปัญหาในการใช้งานติดต่อเบอร์ภายใน 148
                        (กลุ่มงานสุขภาพดิจิทัล)**</p>
                </div>
            </div>

        </div>
    </div>
</body>

</html>