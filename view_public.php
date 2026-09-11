<?php
require 'db.php';

$topic_id = $_GET['id'] ?? null;
if (!$topic_id)
    die("Topic not found.");

// ⭐⭐ นับจำนวนเข้าดูเรื่อง ⭐⭐
$pdo->prepare("UPDATE topics SET views = views + 1 WHERE id = ?")
    ->execute([$topic_id]);

$stmt = $pdo->prepare("SELECT t.*, u.username FROM topics t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
$stmt->execute([$topic_id]);
$topic = $stmt->fetch();

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
    <title><?= htmlspecialchars($topic['title']) ?></title>
    <link rel="icon" href="/savefile/assets/icons/health48.png" type="image/png">
    <link rel="apple-touch-icon" href="/savefile/assets/icons/health48.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/view_public.css">
</head>

<body>
    <a id="top"></a>

    <nav class="topbar">
        <div class="topbar-title">Hangchat Hospital Webboard/Drive 📄 <?= htmlspecialchars($topic['title']) ?></div>
        <div class="topbar-actions">
            <a href="view_topic.php?id=<?= urlencode($topic_id) ?>" class="btn btn-ghost">แก้ไข</a>
            <a href="index.php" class="btn btn-ghost">หน้าหลัก</a>
        </div>
    </nav>

    <div class="container">
        <div class="doc-header">
            <h1><?= htmlspecialchars($topic['title']) ?></h1>
            <div class="doc-meta">โดย <?= htmlspecialchars($topic['username']) ?> ·
                <?= date('d M Y', strtotime($topic['created_at'])) ?>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <?php if (count($paragraphs) > 0): ?>
                    <?php foreach ($paragraphs as $p): ?>
                        <div class="para-block">
                            <?php if ($p['is_heading']): ?>
                                <div class="para-heading"><?= nl2br(htmlspecialchars($p['content'])) ?></div>
                            <?php else: ?>
                                <div class="para-text"><?= nl2br(htmlspecialchars($p['content'])) ?></div>
                            <?php endif; ?>

                            <?php if (!empty($fileMap[$p['id']])): ?>
                                <div class="files-section">
                                    <div class="files-title">ไฟล์แนบ</div>
                                    <?php foreach ($fileMap[$p['id']] as $f): ?>
                                        <?php $fileType = $f['type'] ?? 'file'; ?>
                                        <div class="file-item">
                                            <?php if ($fileType === 'link'): ?>
                                                <a href="<?= htmlspecialchars($f['url']) ?>" target="_blank"
                                                    onclick="countClick(<?= $f['id'] ?>)" rel="noopener noreferrer">
                                                    🔗 <?= htmlspecialchars($f['filename']) ?>
                                                </a>

                                                <span class="badge badge-public" style="margin-left:10px;">
                                                    คลิก: <?= $f['clicks'] ?> ครั้ง
                                                </span>

                                                <div class="file-actions">
                                                    <button class="btn btn-icon" onclick="copyLink('<?= htmlspecialchars($f['url']) ?>')"
                                                        title="คัดลอกลิงก์">📋คัดลอกลิงก์</button>
                                                    <button class="btn btn-icon"
                                                        onclick="shareFile('<?= htmlspecialchars($f['filename']) ?>','<?= htmlspecialchars($f['url']) ?>')"
                                                        title="แชร์">🔗</button>
                                                </div>
                                            <?php else: ?>
                                                <a href="<?= htmlspecialchars($f['filepath']) ?>" target="_blank"
                                                    onclick="countClick(<?= $f['id'] ?>)" rel="noopener noreferrer">
                                                    📎 <?= htmlspecialchars($f['filename']) ?>
                                                </a>

                                                <span class="badge badge-public" style="margin-left:10px;">
                                                    คลิก: <?= $f['clicks'] ?> ครั้ง
                                                </span>

                                                <div class="file-actions">
                                                    <button class="btn btn-icon"
                                                        onclick="copyLink('<?= htmlspecialchars($f['filepath']) ?>')"
                                                        title="คัดลอกลิงก์">📋คัดลอกลิงก์</button>
                                                    <button class="btn btn-icon"
                                                        onclick="shareFile('<?= htmlspecialchars($f['filename']) ?>','<?= htmlspecialchars($f['filepath']) ?>')"
                                                        title="แชร์">🔗</button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                        // Inline media preview
                                        if ($fileType !== 'link') {
                                            $mime = $f['mime_type'] ?? '';
                                            if (str_starts_with($mime, 'image/')) {
                                                echo "<img src='" . htmlspecialchars($f['filepath']) . "' class='media-img' alt=''>";
                                            } elseif (str_starts_with($mime, 'audio/')) {
                                                echo "<audio controls class='media-audio'><source src='" . htmlspecialchars($f['filepath']) . "' type='$mime'></audio>";
                                            } elseif (str_starts_with($mime, 'video/')) {
                                                echo "<video controls class='media-video' style='max-height:280px'><source src='" . htmlspecialchars($f['filepath']) . "' type='$mime'></video>";
                                            }
                                        }
                                        ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty">ยังไม่มีเนื้อหา</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <a id="bottom"></a>

    <div class="float-nav">
        <a href="#top" class="float-btn" title="ขึ้นบนสุด">↑</a>
        <a href="#bottom" class="float-btn" title="ลงล่างสุด">↓</a>
    </div>

    <script>
        function copyLink(url) {
            const full = url.startsWith('http') ? url : window.location.origin + '/savefile/' + url;
            navigator.clipboard ? navigator.clipboard.writeText(full).then(() => alert('คัดลอกแล้ว: ' + full)) : fallbackCopy(full);
        }

        function fallbackCopy(text) {
            const el = document.createElement('input');
            el.value = text; document.body.appendChild(el); el.select(); document.execCommand('copy'); document.body.removeChild(el);
            alert('คัดลอกแล้ว: ' + text);
        }

        function shareFile(name, url) {
            navigator.share ? navigator.share({ title: name, url }).catch(() => { }) : alert('เบราว์เซอร์นี้ไม่รองรับการแชร์');
        }

        function countClick(id) {
            fetch('file_click_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + encodeURIComponent(id)
            });
        }
    </script>
</body>

</html>