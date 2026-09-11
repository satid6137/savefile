<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

// ดึงหมวดทั้งหมด
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

// ดึงหัวข้อทั้งหมด
$stmt2 = $pdo->query("SELECT * FROM topic_groups ORDER BY name ASC");
$groups_raw = $stmt2->fetchAll();

// ดึงจำนวนเรื่องในแต่ละหัวข้อ
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
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>Hangchat Hospital Webboard/Drive</title>

    <link rel="icon" href="/savefile/assets/icons/health48.png" type="image/png">
    <link rel="apple-touch-icon" href="/savefile/assets/icons/health48.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/categories.css">
</head>

<body>

    <nav class="topbar">
        <div class="topbar-brand">
            <span>&#9679;</span> Hangchat Hospital Webboard/Drive
        </div>
        <div class="topbar-user">
            <span>สวัสดี, <strong>
                    <?= htmlspecialchars($user['username']) ?>
                </strong></span>

            <?php if ($user['role'] === 'admin'): ?>
                <a href="admin/index.php" class="btn btn-ghost btn-sm">Admin</a>
            <?php endif; ?>

            <a href="index.php" class="btn btn-ghost btn-sm">INDEX</a>
            <a href="categories.php" class="btn btn-ghost btn-sm">หมวดหมู่</a>
            <a href="logout.php" class="btn btn-ghost btn-sm">ออกจากระบบ</a>
        </div>
    </nav>

    <div class="container">

        <div class="card">
            <div class="card-header">หมวดหมู่ทั้งหมด</div>
            <div class="card-body">

                <?php foreach ($categories as $c): ?>

                    <?php
                    $group_total = isset($groups[$c['id']]) ? count($groups[$c['id']]) : 0;
                    ?>

                    <div class="group-block">

                        <div class="group-title">
                            <strong>
                                <?= htmlspecialchars($c['name']) ?>
                            </strong>

                            <span class="badge badge-inherit">
                                <?= $group_total ?> หัวข้อ
                            </span>
                        </div>

                        <?php if (!empty($groups[$c['id']])): ?>
                            <?php foreach ($groups[$c['id']] as $g): ?>

                                <?php
                                $topic_total = $topic_count[$g['id']] ?? 0;
                                ?>

                                <div class="topic-actions"
                                    style="padding-left:20px; display:flex; justify-content:space-between; align-items:center;">

                                    <!-- ซ้าย: ไอคอน + ชื่อหัวข้อ + จำนวนเรื่อง + สิทธิ์ -->
                                    <div style="display:flex; align-items:center; gap:10px;">

                                        <!-- icon -->
                                        <span style="font-size:18px; color:var(--accent);">📄</span>

                                        <!-- ชื่อหัวข้อ -->
                                        <a href="topics.php?group=<?= $g['id'] ?>" class="topic-link">
                                            <?= htmlspecialchars($g['name']) ?>
                                        </a>

                                        <!-- จำนวนเรื่อง -->
                                        <span class="badge badge-public">
                                            <?= $topic_total ?> เรื่อง
                                        </span>

                                        <!-- สิทธิ์หัวข้อ -->
                                        <?php if ($g['visibility'] === 'public'): ?>
                                            <span class="badge badge-public">สาธารณะ</span>
                                        <?php elseif ($g['visibility'] === 'internal'): ?>
                                            <span class="badge badge-internal">เฉพาะเจ้าหน้าที่</span>
                                        <?php else: ?>
                                            <span class="badge badge-inherit">สิทธิ์ตามหมวด</span>
                                        <?php endif; ?>

                                    </div>

                                    <!-- ขวา: ผู้สร้าง -->
                                    <?php
                                    // ดึงชื่อผู้สร้างจาก users
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
                                <span class="text-muted">ไม่มีหัวข้อในหมวดนี้</span>
                            </div>

                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>

            </div>
        </div>

    </div>

</body>

</html>