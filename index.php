<?php
session_start();
require 'db.php';

$user = $_SESSION['user'] ?? null;

// ดึงหมวดที่เป็น public เท่านั้น
$stmt = $pdo->prepare("SELECT * FROM categories WHERE visibility = 'public' ORDER BY name ASC");
$stmt->execute();
$categories = $stmt->fetchAll();

// ดึงหัวข้อทั้งหมด (แต่จะกรองทีหลัง)
$stmt2 = $pdo->query("SELECT * FROM topic_groups ORDER BY name ASC");
$groups_raw = $stmt2->fetchAll();

// ดึงจำนวนเรื่องในแต่ละหัวข้อ (แม้ไม่แสดง แต่ใช้ badge)
$stmt3 = $pdo->query("SELECT group_id, COUNT(*) AS total_topics FROM topics GROUP BY group_id");
$topic_count_raw = $stmt3->fetchAll();
$topic_count = [];
foreach ($topic_count_raw as $tc) {
    $topic_count[$tc['group_id']] = $tc['total_topics'];
}

// จัดกลุ่มหัวข้อเข้า category_id
$groups = [];
foreach ($groups_raw as $g) {
    $groups[$g['category_id']][] = $g;
}

// ฟังก์ชัน resolve visibility แบบเดียวกับระบบ admin
function resolveVisibility($topic_visibility, $group_visibility, $cat_visibility)
{
    if ($topic_visibility === 'public' || $topic_visibility === 'internal')
        return $topic_visibility;
    if ($group_visibility === 'public' || $group_visibility === 'internal')
        return $group_visibility;
    return $cat_visibility;
}

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hangchat Hospital Webboard/Drive</title>
    <link rel="icon" href="/savefile/assets/icons/health48.png" type="image/png">
    <link rel="apple-touch-icon" href="/savefile/assets/icons/health48.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index.css">
</head>

<body>

    <?php if (!$user): ?>
        <div class="login-bar">
            <form method="POST" action="login_process.php" class="login-form">
                <input name="username" placeholder="ชื่อผู้ใช้ (admin)" required>
                <input name="password" type="password" placeholder="รหัสผ่าน" required>
                <button type="submit" class="btn">เข้าสู่ระบบ</button>
            </form>
            <form method="GET" action="oauth_login.php" class="login-form" style="margin-top:.5rem;">
                <button type="submit" class="btn" style="background:#2d6a4f;border-color:#2d6a4f;">
                    เข้าสู่ระบบด้วย Provider ID
                </button>
            </form>
            <div class="page-header">
                <p>**เจ้าหน้าที่โรงพยาบาลห้างฉัตรให้ เข้าสู่ระบบด้วย Provider ID หากมีปัญหาในการใช้งานติดต่อเบอร์ภายใน 148
                    (กลุ่มงานสุขภาพดิจิทัล)**</p>
            </div>
        </div>
    <?php else: ?>
        <nav class="topbar">
            <div class="topbar-brand"><span>&#9679;</span> Hangchat Hospital Webboard/Drive</div>
            <div class="topbar-right">
                <span>สวัสดี, <strong>
                        <?= htmlspecialchars($user['username']) ?>
                    </strong></span>
                <a href="dashboard.php" class="btn btn-ghost">Dashboard</a>
                <a href="logout.php" class="btn btn-ghost">ออกจากระบบ</a>
            </div>
        </nav>
    <?php endif; ?>

    <div class="container">

        <?php if (!$user): ?>
            <div class="page-header">
                <h1>Hangchat Hospital Webboard/Drive</h1>
                <p>ระบบจัดการเอกสาร โรงพยาบาลห้างฉัตร</p>
            </div>
        <?php else: ?>
            <div class="page-header" style="margin-bottom:1.5rem;">
                <h1>Hangchat Hospital Webboard/Drive</h1>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['login_error'])): ?>
            <div class="alert">
                <?= $_SESSION['login_error'];
                unset($_SESSION['login_error']); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">หมวดหมู่สำหรับประชาชน</div>
            <div class="card-body">

                <?php foreach ($categories as $c): ?>

                    <?php
                    // นับหัวข้อที่ประชาชนเห็นได้จริง
                    $public_groups = [];

                    if (!empty($groups[$c['id']])) {
                        foreach ($groups[$c['id']] as $g) {

                            // resolve visibility ของหัวข้อ
                            $realVis = resolveVisibility($g['visibility'], $g['visibility'], $c['visibility']);

                            if ($realVis === 'public') {
                                $public_groups[] = $g;
                            }
                        }
                    }

                    ?>

                    <div class="group-block">

                        <div class="group-title">
                            <strong>
                                <?= htmlspecialchars($c['name']) ?>
                            </strong>

                            <span class="badge badge-inherit">
                                <?= count($public_groups) ?> หัวข้อ
                            </span>
                        </div>

                        <?php if (!empty($public_groups)): ?>
                            <?php foreach ($public_groups as $g): ?>

                                <?php
                                $topic_total = $topic_count[$g['id']] ?? 0;
                                ?>

                                <div class="topic-actions"
                                    style="padding-left:20px; display:flex; justify-content:space-between; align-items:center;">

                                    <!-- ซ้าย: ไอคอน + ชื่อหัวข้อ + จำนวนเรื่อง -->
                                    <div style="display:flex; align-items:center; gap:10px;">

                                        <span style="font-size:18px; color:var(--accent);">📄</span>

                                        <a href="topics.php?group=<?= $g['id'] ?>" class="topic-link">
                                            <?= htmlspecialchars($g['name']) ?>
                                        </a>

                                        <span class="badge badge-public">
                                            <?= $topic_total ?> เรื่อง
                                        </span>
                                    </div>

                                    <!-- ขวา: ผู้สร้าง -->
                                    <?php
                                    $stmtCreator = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                                    $stmtCreator->execute([$g['created_by']]);
                                    $creatorName = $stmtCreator->fetchColumn() ?: 'unknown';
                                    ?>
                                    <div style="font-size:12px; color:#555;">
                                        สร้างโดย: <?= htmlspecialchars($creatorName) ?>
                                    </div>

                                </div>

                            <?php endforeach; ?>
                        <?php else: ?>

                            <div class="topic-actions" style="padding-left:20px;">
                                <span class="text-muted">ไม่มีหัวข้อที่เปิดให้ประชาชน</span>
                            </div>

                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>

            </div>
        </div>


        <footer class="hos-footer text-center">
            ระบบ Hangchat Hospital Webboard/Drive · Developed by <strong>นายสาธิต รินคำ</strong> นักวิชาการคอมพิวเตอร์
            กลุ่มงานสุขภาพดิจิตอล
            โรงพยาบาลห้างฉัตร
            · Coder Copilot ·
            <?= date('Y') ?>
        </footer>

</body>

</html>