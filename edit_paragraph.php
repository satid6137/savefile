<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$paragraph_id = $_GET['id'];
$topic_id = $_GET['topic'];

$stmt = $pdo->prepare("SELECT p.*, t.user_id, t.title as topic_title FROM paragraphs p JOIN topics t ON p.topic_id = t.id WHERE p.id = ?");
$stmt->execute([$paragraph_id]);
$paragraph = $stmt->fetch();

if (!$paragraph) {
    die("Paragraph not found");
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อความ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/edit_paragraph.css">
</head>

<body>

    <nav class="topbar">
        <div class="topbar-brand"><span class="dot">&#9679;</span> Hangchat Hospital Webboard/Drive</div>
        <a href="view_topic.php?id=<?= $topic_id ?>" class="btn btn-ghost">ย้อนกลับ</a>
    </nav>

    <div class="main">
        <div class="card">
            <div class="card-header">
                <h2>แก้ไขข้อความ</h2>
                <p>
                    <?= htmlspecialchars($paragraph['topic_title']) ?>
                </p>
            </div>
            <div class="card-body">
                <form method="POST" action="update_paragraph.php">
                    <input type="hidden" name="id" value="<?= $paragraph_id ?>">
                    <input type="hidden" name="topic_id" value="<?= $topic_id ?>">

                    <div class="form-group">
                        <label class="form-label">ข้อความ</label>
                        <textarea name="content" required><?= htmlspecialchars($paragraph['content']) ?></textarea>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="allow_upload" id="allow_upload" <?= $paragraph['allow_upload'] ? 'checked' : '' ?>>
                        <label for="allow_upload">อนุญาตให้แนบไฟล์</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_heading" id="is_heading" <?= $paragraph['is_heading'] ? 'checked' : '' ?>>
                        <label for="is_heading">ตั้งเป็นหัวข้อ</label>
                    </div>

                    <div class="form-actions">
                        <a href="view_topic.php?id=<?= $topic_id ?>" class="btn btn-ghost">ยกเลิก</a>
                        <button type="submit" class="btn btn-primary">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>

</html>