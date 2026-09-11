<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    $_SESSION['message'] = "ไม่พบผู้ใช้ที่ต้องการเปลี่ยนรหัสผ่าน";
    header("Location: index.php");
    exit;
}

// ดึงชื่อผู้ใช้เพื่อแสดง
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$target = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    if (strlen($new_password) < 4) {
        $error = "รหัสผ่านต้องมีอย่างน้อย 4 ตัวอักษร";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $user_id]);
        $_SESSION['message'] = "เปลี่ยนรหัสผ่านเรียบร้อยแล้ว";
        header("Location: index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="icon" href="/savefile/assets/icons/health48.png" type="image/png">
    <link rel="apple-touch-icon" href="/savefile/assets/icons/health48.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/change_password.css">
</head>

<body>

    <nav class="topbar">
        <div class="topbar-brand">
            <span class="dot">&#9679;</span> Hangchat Hospital Drive
            <span class="topbar-badge">Admin</span>
        </div>
    </nav>

    <div class="main">
        <div class="card">
            <div class="card-header">
                <h2>เปลี่ยนรหัสผ่าน</h2>
                <?php if ($target): ?>
                    <p>ผู้ใช้: <strong><?= htmlspecialchars($target['username']) ?></strong></p>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert-error"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">รหัสผ่านใหม่</label>
                        <input type="password" name="new_password" placeholder="อย่างน้อย 4 ตัวอักษร" required
                            autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                    <a href="index.php" class="btn btn-ghost">ยกเลิก</a>
                </form>
            </div>
        </div>
    </div>

</body>

</html>