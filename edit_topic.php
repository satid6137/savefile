<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$topic_id = $_GET['id'];
$user_id = $_SESSION['user']['id'];

$stmt = $pdo->prepare("SELECT * FROM topics WHERE id = ?");
$stmt->execute([$topic_id]);
$topic = $stmt->fetch();

if (!$topic || ($topic['user_id'] != $user_id && $_SESSION['user']['role'] !== 'admin')) {
    die("Unauthorized");
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขชื่อเรื่อง</title>
    <link rel="icon" href="/savefile/assets/icons/health48.png" type="image/png">
    <link rel="apple-touch-icon" href="/savefile/assets/icons/health48.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/edit_topic.css">
</head>

<body>

    <nav class="topbar">
        <div class="topbar-brand"><span class="dot">&#9679;</span> Hangchat Hospital Webboard/Drive</div>
        <a href="dashboard.php" class="btn btn-ghost">ย้อนกลับ</a>
    </nav>

    <div class="main">
        <div class="card">
            <div class="card-header">
                <h2>แก้ไขชื่อเรื่อง</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="update_topic.php">
                    <input type="hidden" name="topic_id" value="<?= $topic['id'] ?>">
                    <div class="form-group">
                        <label class="form-label">ชื่อเรื่อง</label>
                        <input type="text" name="title" value="<?= htmlspecialchars($topic['title']) ?>" required
                            autofocus>
                    </div>
                    <div class="form-actions">
                        <a href="topics.php?group=<?= $topic['group_id'] ?>" class="btn btn-ghost">ยกเลิก</a>
                        <button type="submit" class="btn btn-primary">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>

</html>