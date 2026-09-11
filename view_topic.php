<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$topic_id = $_GET['id'];

$pdo->prepare("UPDATE topics SET views = views + 1 WHERE id = ?")
    ->execute([$topic_id]);

$stmt = $pdo->prepare("SELECT * FROM topics WHERE id = ?");
$stmt->execute([$topic_id]);
$topic = $stmt->fetch();

$group_id = $topic['group_id'];

$stmt = $pdo->prepare("SELECT * FROM paragraphs WHERE topic_id = ? ORDER BY created_at ASC");
$stmt->execute([$topic_id]);
$paragraphs = $stmt->fetchAll();

$fileMap = [];
$stmt = $pdo->prepare("SELECT * FROM files WHERE paragraph_id IN (SELECT id FROM paragraphs WHERE topic_id = ?)");
$stmt->execute([$topic_id]);
foreach ($stmt->fetchAll() as $file) {
    $fileMap[$file['paragraph_id']][] = $file;
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= htmlspecialchars($topic['title']) ?>
    </title>
    <link rel="icon" href="/savefile/assets/icons/health48.png" type="image/png">
    <link rel="apple-touch-icon" href="/savefile/assets/icons/health48.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/view_topic.css">
</head>

<body>

    <nav class="topbar">
        <div class="topbar-title">Hangchat Hospital Webboard/Drive 📄
            <?= htmlspecialchars($topic['title']) ?>
        </div>
        <div class="topbar-actions">
            <a href="view_public.php?id=<?= $topic['id'] ?>" class="btn btn-success btn-sm" target="_blank"
                rel="noopener noreferrer">ดูตัวอย่าง</a>
            <a href="topics.php?group=<?= $group_id ?>" class="btn btn-ghost btn-sm">เรื่อง</a>
            <a href="dashboard.php" class="btn btn-ghost btn-sm">Dashboard</a>
            <a href="logout.php" class="btn btn-ghost btn-sm">ออกจากระบบ</a>
        </div>
    </nav>

    <div class="container">

        <?php if (isset($_SESSION['message'])): ?>
            <div class="flash" id="flash-message">
                <?= $_SESSION['message'];
                unset($_SESSION['message']); ?>
                <button onclick="this.parentElement.remove()">&#215;</button>
            </div>
        <?php endif; ?>

        <div class="page-title">
            <?= htmlspecialchars($topic['title']) ?>
        </div>

        <!-- Add paragraph -->
        <div class="card">
            <div class="card-header">เพิ่มข้อความใหม่</div>
            <div class="card-body">
                <form method="POST" action="add_paragraph.php">
                    <input type="hidden" name="topic_id" value="<?= $topic_id ?>">
                    <div class="form-group">
                        <textarea name="content" placeholder="เขียนข้อความ..." required></textarea>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="allow_upload" id="allow_upload">
                        <label for="allow_upload">อนุญาตให้แนบไฟล์</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_heading" id="is_heading">
                        <label for="is_heading">ตั้งเป็นหัวข้อ</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">เพิ่มข้อความ</button>
                </form>
            </div>
        </div>

        <!-- Paragraph list -->
        <div class="card">
            <div class="card-header">ข้อความทั้งหมด (
                <?= count($paragraphs) ?>)
            </div>
            <div class="card-body">
                <?php if (count($paragraphs) > 0): ?>
                    <?php foreach ($paragraphs as $p): ?>
                        <div class="para-block">
                            <div class="para-top">
                                <div class="para-content">
                                    <?php if (!empty($p['is_heading'])): ?>
                                        <div class="para-heading">
                                            <?= nl2br(htmlspecialchars($p['content'])) ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="para-text">
                                            <?= nl2br(htmlspecialchars($p['content'])) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="para-meta">
                                        <?= date('d M Y', strtotime($p['created_at'])) ?>
                                    </div>
                                </div>
                                <div class="para-actions">
                                    <a href="edit_paragraph.php?id=<?= $p['id'] ?>&topic=<?= $topic_id ?>"
                                        class="btn btn-warning btn-sm">แก้ไข</a>
                                    <form method="POST" action="delete_paragraph.php" onsubmit="return confirm('ลบข้อความนี้?')"
                                        style="display:inline;">
                                        <input type="hidden" name="paragraph_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="topic_id" value="<?= $topic_id ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">ลบ</button>
                                    </form>
                                </div>
                            </div>

                            <!-- Upload zone -->
                            <?php if ($p['allow_upload']): ?>
                                <div class="upload-zone">
                                    <form method="POST" action="upload_file.php" enctype="multipart/form-data">
                                        <input type="hidden" name="paragraph_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="topic_id" value="<?= $topic_id ?>">
                                        <div class="input-group">
                                            <input type="file" name="files[]" multiple required>
                                            <button type="submit" class="btn btn-ghost btn-sm"
                                                style="white-space:nowrap;">อัปโหลด</button>
                                        </div>
                                    </form>
                                    <form method="POST" action="add_link.php">
                                        <input type="hidden" name="paragraph_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="topic_id" value="<?= $topic_id ?>">
                                        <div class="input-group">
                                            <input type="url" name="url" placeholder="URL เช่น https://example.com" required>
                                            <input type="text" name="filename" placeholder="ชื่อที่แสดง" required>
                                            <button type="submit" class="btn btn-ghost btn-sm"
                                                style="white-space:nowrap;">เพิ่มลิงก์</button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>

                            <!-- Attached files -->
                            <?php if (!empty($fileMap[$p['id']])): ?>
                                <div class="files-section">
                                    <div class="files-title">ไฟล์แนบ</div>
                                    <?php foreach ($fileMap[$p['id']] as $f): ?>
                                        <?php $fileType = $f['type'] ?? 'file'; ?>
                                        <div class="file-item">
                                            <div class="file-item-info">
                                                <div class="file-name">
                                                    <?= htmlspecialchars($f['filename']) ?>
                                                </div>
                                                <?php if ($fileType === 'link'): ?>
                                                    <a href="<?= htmlspecialchars($f['url']) ?>" target="_blank" rel="noopener noreferrer"
                                                        class="file-link">🔗 เปิดลิงก์</a>
                                                <?php else: ?>
                                                    <?php
                                                    $mime = $f['mime_type'] ?? '';
                                                    if (str_starts_with($mime, 'image/')) {
                                                        echo "<img src='" . htmlspecialchars($f['filepath']) . "' class='media-img' alt=''>";
                                                    } elseif (str_starts_with($mime, 'audio/')) {
                                                        echo "<audio controls class='media-audio'><source src='" . htmlspecialchars($f['filepath']) . "' type='$mime'></audio>";
                                                    } elseif (str_starts_with($mime, 'video/')) {
                                                        echo "<video controls class='media-video' style='max-height:260px'><source src='" . htmlspecialchars($f['filepath']) . "' type='$mime'></video>";
                                                    } else {
                                                        echo "<a href='" . htmlspecialchars($f['filepath']) . "' target='_blank' class='file-link'>📎 เปิดไฟล์</a>";
                                                    }
                                                    ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="file-item-actions">
                                                <form method="POST" action="delete_file.php" onsubmit="return confirm('ลบไฟล์นี้?')">
                                                    <input type="hidden" name="file_id" value="<?= $f['id'] ?>">
                                                    <input type="hidden" name="topic_id" value="<?= $topic_id ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">ลบ</button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty">ยังไม่มีข้อความ</div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <div class="float-nav">
        <button onclick="window.scrollTo({top:0,behavior:'smooth'})" class="float-btn" title="ขึ้นบนสุด">↑</button>
        <button onclick="window.scrollTo({top:document.body.scrollHeight,behavior:'smooth'})" class="float-btn"
            title="ลงล่างสุด">↓</button>
    </div>

    <script>
        const flash = document.getElementById('flash-message');
        if (flash) setTimeout(() => flash.remove(), 3500);
    </script>

</body>

</html>