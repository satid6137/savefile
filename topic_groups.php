<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

$cat_id = $_GET['cat'] ?? 0;

// ดึงหมวด
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$cat_id]);
$category = $stmt->fetch();

if (!$category) {
    die("ไม่พบหมวด");
}

// ดึงหัวข้อในหมวดนี้
$stmt = $pdo->prepare("
    SELECT * FROM topic_groups
    WHERE category_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$cat_id]);
$groups = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>จัดการหัวข้อ</title>
    <link rel="icon" href="/savefile/assets/icons/health48.png" type="image/png">
    <link rel="apple-touch-icon" href="/savefile/assets/icons/health48.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/topic_groups.css">
</head>

<body>

    <nav class="topbar">
        <div class="topbar-brand">
            <span>&#9679;</span> จัดการหัวข้อ
        </div>
        <div class="topbar-user">
            <span>สวัสดี, <strong>
                    <?= htmlspecialchars($user['username']) ?>
                </strong></span>
            <a href="categories.php" class="btn btn-ghost btn-sm">หมวด</a>
            <a href="dashboard.php" class="btn btn-ghost btn-sm">Dashboard</a>
            <a href="logout.php" class="btn btn-ghost btn-sm">ออกจากระบบ</a>
        </div>
    </nav>

    <div class="container">

        <!-- สร้างหัวข้อใหม่ -->
        <div class="card">
            <div class="card-header">สร้างหัวข้อใหม่</div>
            <div class="card-body">
                <form method="POST" action="create_topic_group.php">
                    <input type="hidden" name="category_id" value="<?= $cat_id ?>">

                    <div class="form-group">
                        <input name="name" placeholder="ชื่อหัวข้อ..." required>
                    </div>

                    <div class="form-group">
                        <label>สิทธิ์หัวข้อ</label>
                        <select name="visibility">
                            <option value="inherit">inherit (ตามหมวด)</option>
                            <option value="public">public</option>
                            <option value="internal">internal</option>
                        </select>
                    </div>

                    <button class="btn btn-primary">เพิ่มหัวข้อ</button>
                </form>
            </div>
        </div>

        <!-- รายการหัวข้อ -->
        <div class="card">
            <div class="card-header">รายการหัวข้อในหมวด:
                <?= htmlspecialchars($category['name']) ?>
            </div>
            <div class="card-body" style="padding:0;">

                <?php foreach ($groups as $g): ?>

                    <?php
                    // เช็คว่ามีเรื่องอยู่ไหม
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM topics WHERE group_id = ?");
                    $stmt->execute([$g['id']]);
                    $hasTopics = $stmt->fetchColumn() > 0;
                    ?>

                    <div class="group-block">
                        <div class="group-title">
                            <h3>
                                <?= htmlspecialchars($g['name']) ?>
                            </h3>

                            <?php if ($g['visibility'] === 'public'): ?>
                                <span class="badge badge-public">public</span>
                            <?php elseif ($g['visibility'] === 'internal'): ?>
                                <span class="badge badge-internal">internal</span>
                            <?php else: ?>
                                <span class="badge badge-inherit">inherit</span>
                            <?php endif; ?>
                        </div>

                        <div class="topic-actions">
                            <a href="topics.php?group=<?= $g['id'] ?>" class="btn btn-ghost btn-sm">ดูเรื่อง</a>
                            <a href="edit_topic_group.php?id=<?= $g['id'] ?>" class="btn btn-warning btn-sm">แก้ไข</a>

                            <?php if (!$hasTopics): ?>
                                <a href="delete_topic_group.php?id=<?= $g['id'] ?>"
                                    onclick="return confirm('ต้องการลบหัวข้อนี้?')" class="btn btn-danger btn-sm">ลบ</a>
                            <?php else: ?>
                                <span style="color:red; font-size:12px;">มีเรื่องอยู่ ลบไม่ได้</span>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php endforeach; ?>

            </div>
        </div>

    </div>

</body>

</html>