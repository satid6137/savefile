<?php
session_start();
require 'db.php';

$user = $_SESSION['user'] ?? null;
$userRole = $user['role'] ?? null;

$group_id = $_GET['group'] ?? 0;

// ⭐⭐ นับจำนวนเข้าดูหัวข้อ ⭐⭐
$pdo->prepare("UPDATE topic_groups SET views = views + 1 WHERE id = ?")
    ->execute([$group_id]);

// ⭐ ดึงข้อมูลหัวข้อ
$stmt = $pdo->prepare("
    SELECT tg.*, c.visibility AS cat_visibility, c.name AS cat_name
    FROM topic_groups tg
    LEFT JOIN categories c ON tg.category_id = c.id
    WHERE tg.id = ?
");
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group) {
    die("ไม่พบหัวข้อ");
}

// ⭐⭐ Pagination
$perPage = 15; // จำนวนเรื่องต่อหน้า
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// ⭐ จำนวนเรื่องทั้งหมดในหัวข้อนี้
$stmt = $pdo->prepare("SELECT COUNT(*) FROM topics WHERE group_id = ?");
$stmt->execute([$group_id]);
$totalTopics = $stmt->fetchColumn();
$totalPages = ceil($totalTopics / $perPage);

$search = $_GET['search'] ?? '';
$creator = $_GET['creator'] ?? '';
$vis = $_GET['vis'] ?? '';
$upload_status = $_GET['upload_status'] ?? '';

$where = "t.group_id = ?";
$params = [$group_id];

// ค้นหาชื่อเรื่อง
if ($search !== '') {
    $where .= " AND t.title LIKE ?";
    $params[] = "%$search%";
}

// กรองผู้สร้าง
if ($creator !== '') {
    $where .= " AND u.username LIKE ?";
    $params[] = "%$creator%";
}

// กรอง visibility
if ($vis !== '') {
    $where .= " AND t.visibility = ?";
    $params[] = $vis;
}

// กรองสถานะแนบไฟล์
if ($upload_status === 'complete') {
    // แนบครบแล้ว
    $where .= " AND (
        (SELECT COUNT(*) FROM paragraphs WHERE topic_id = t.id AND allow_upload = 1)
        =
        (SELECT COUNT(DISTINCT p.id)
         FROM paragraphs p
         LEFT JOIN files f ON f.paragraph_id = p.id
         WHERE p.topic_id = t.id AND (f.filepath IS NOT NULL OR f.url IS NOT NULL))
    )";
}

if ($upload_status === 'incomplete') {
    // ยังไม่ครบ
    $where .= " AND (
        (SELECT COUNT(*) FROM paragraphs WHERE topic_id = t.id AND allow_upload = 1)
        >
        (SELECT COUNT(DISTINCT p.id)
         FROM paragraphs p
         LEFT JOIN files f ON f.paragraph_id = p.id
         WHERE p.topic_id = t.id AND (f.filepath IS NOT NULL OR f.url IS NOT NULL))
    )";
}

// จำนวนเรื่องทั้งหมด (สำหรับ pagination)
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM topics t
    LEFT JOIN users u ON t.user_id = u.id
    WHERE $where
");
$stmt->execute($params);
$totalTopics = $stmt->fetchColumn();
$totalPages = ceil($totalTopics / $perPage);

// ดึงเรื่องเฉพาะหน้าปัจจุบัน
$stmt = $pdo->prepare("
    SELECT t.*, u.username
    FROM topics t
    LEFT JOIN users u ON t.user_id = u.id
    WHERE $where
    ORDER BY t.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$topics = $stmt->fetchAll();

// ⭐ visibility
function resolveVisibility($topic_visibility, $group_visibility, $cat_visibility)
{
    if ($topic_visibility === 'public' || $topic_visibility === 'internal')
        return $topic_visibility;

    if ($group_visibility === 'public' || $group_visibility === 'internal')
        return $group_visibility;

    return $cat_visibility;
}

function visThai($v)
{
    return [
        'public' => 'สาธารณะ',
        'internal' => 'เฉพาะเจ้าหน้าที่',
        'inherit' => 'ตามหัวข้อ',
    ][$v] ?? $v;
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>
        <?= htmlspecialchars($group['name']) ?>
    </title>
    <!-- Icon -->
    <link rel="icon" href="/savefile/assets/icons/health48.png" type="image/png">
    <link rel="apple-touch-icon" href="/savefile/assets/icons/health48.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style_categories.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>

    <nav class="topbar">
        <div class="topbar-brand">
            <span>&#9679;</span>
            <?= htmlspecialchars($group['name']) ?>
        </div>

        <div class="topbar-user">
            <?php if ($user): ?>
                <span>สวัสดี, <strong>
                        <?= htmlspecialchars($user['username']) ?>
                    </strong></span>
                <a href="dashboard.php" class="btn btn-ghost btn-sm">Dashboard</a>
                <a href="categories.php" class="btn btn-ghost btn-sm">หมวดหมู่</a>
                <a href="logout.php" class="btn btn-ghost btn-sm">ออกจากระบบ</a>
            <?php else: ?>
                <a href="index.php" class="btn btn-ghost btn-sm">หน้าแรก</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">

        <div class="card">
            <div class="card-header">หัวข้อ</div>
            <div class="card-body">
                <h2>
                    <?= htmlspecialchars($group['name']) ?>
                </h2>

                <p style="margin-top:10px;">
                    หมวด: <strong>
                        <?= htmlspecialchars($group['cat_name']) ?>
                    </strong>
                    > สิทธิ์หมวด: <strong>
                        <?= visThai($group['cat_visibility']) ?>
                    </strong>
                    > สิทธิ์หัวข้อ: <strong>
                        <?= visThai($group['visibility']) ?>
                    </strong>
                </p>
            </div>
        </div>

        <!-- เฉพาะเจ้าหน้าที่เท่านั้น -->
        <?php if ($user): ?>
            <form method="POST" action="create_topic.php" style="margin-bottom:20px;">
                <input type="hidden" name="group_id" value="<?= $group_id ?>">
                <div class="form-group" style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:6px; font-weight:500;">ชื่อเรื่องใหม่</label>
                    <input name="title" placeholder="กรอกชื่อเรื่อง..." required style="width:100%; padding:10px 12px; border:1px solid var(--border-strong);
                           border-radius:var(--radius-sm); font-size:14px;">
                </div>
                <button class="btn btn-primary btn-sm">เพิ่มเรื่องใหม่</button>
            </form>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">รายการเรื่องในหัวข้อนี้</div>
            <div class="card-body">

                <form method="GET" action="" style="margin-bottom:20px; display:flex; gap:10px; align-items:center;">
                    <input type="hidden" name="group" value="<?= $group_id ?>">

                    <!-- ค้นหาชื่อเรื่อง -->
                    <input name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                        placeholder="ค้นหาชื่อเรื่อง..."
                        style="padding:6px 10px; border:1px solid #ccc; border-radius:6px;">

                    <!-- กรองผู้สร้าง -->
                    <input name="creator" value="<?= htmlspecialchars($_GET['creator'] ?? '') ?>"
                        placeholder="ผู้สร้าง..." style="padding:6px 10px; border:1px solid #ccc; border-radius:6px;">

                    <!-- กรอง visibility -->
                    <select name="vis" style="padding:6px 10px; border:1px solid #ccc; border-radius:6px;">
                        <option value="">-- สิทธิ์ทั้งหมด --</option>
                        <option value="public" <?= ($_GET['vis'] ?? '') === 'public' ? 'selected' : '' ?>>สาธารณะ</option>
                        <option value="internal" <?= ($_GET['vis'] ?? '') === 'internal' ? 'selected' : '' ?>>
                            เฉพาะเจ้าหน้าที่</option>
                        <option value="inherit" <?= ($_GET['vis'] ?? '') === 'inherit' ? 'selected' : '' ?>>ตามหัวข้อ
                        </option>
                    </select>

                    <!-- กรองสถานะการแนบไฟล์ -->
                    <select name="upload_status" style="padding:6px 10px; border:1px solid #ccc; border-radius:6px;">
                        <option value="">-- สถานะแนบไฟล์ --</option>
                        <option value="complete" <?= ($_GET['upload_status'] ?? '') === 'complete' ? 'selected' : '' ?>>
                            แนบครบแล้ว
                        </option>
                        <option value="incomplete" <?= ($_GET['upload_status'] ?? '') === 'incomplete' ? 'selected' : '' ?>>
                            ยังไม่ครบ
                        </option>
                    </select>

                    <button class="btn btn-primary btn-sm">ค้นหา</button>
                    <a href="topics.php?group=<?= $group_id ?>" class="btn btn-secondary btn-sm">ล้าง</a>
                </form>

                <?php foreach ($topics as $t): ?>

                    <?php
                    // visibility ของเรื่อง
                    $realVis = resolveVisibility($t['visibility'], $group['visibility'], $group['cat_visibility']);

                    // ถ้าเป็นประชาชน และเรื่องไม่ใช่ public → ข้าม
                    if (!$user && $realVis !== 'public') {
                        continue;
                    }

                    // A = จำนวน paragraph ที่ต้องแนบไฟล์ (allow_upload = 1)
                    $stmtA = $pdo->prepare("
                        SELECT COUNT(*) 
                        FROM paragraphs 
                        WHERE topic_id = ? 
                          AND allow_upload = 1
                    ");
                    $stmtA->execute([$t['id']]);
                    $must_upload_count = $stmtA->fetchColumn();

                    // B = จำนวน paragraph ที่มีไฟล์จริง
                    $stmtB = $pdo->prepare("
                        SELECT COUNT(DISTINCT p.id)
                        FROM paragraphs p
                        LEFT JOIN files f ON f.paragraph_id = p.id
                        WHERE p.topic_id = ?
                          AND (
                                f.filepath IS NOT NULL
                             OR f.url IS NOT NULL
                          )
                    ");
                    $stmtB->execute([$t['id']]);
                    $uploaded_count = $stmtB->fetchColumn();

                    // สร้าง badge สีเขียว/แดง
                    if ($must_upload_count == 0) {
                        $uploadBadge = '<span class="badge badge-inherit" style="margin-left:10px;"></span>';
                    } elseif ($uploaded_count == $must_upload_count) {
                        $uploadBadge = '<span class="badge" style="margin-left:10px; background:#d8f3dc; color:#2d6a4f;">
                                            แนบครบแล้ว: ' . $uploaded_count . '/' . $must_upload_count . '
                                        </span>';
                    } else {
                        $uploadBadge = '<span class="badge" style="margin-left:10px; background:#fee2e2; color:#991b1b;">
                                            ยังไม่ครบ: ' . $uploaded_count . '/' . $must_upload_count . '
                                        </span>';
                    }
                    ?>

                    <div class="group-block">

                        <div class="group-title" style="display:block; width:100%;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">

                                <!-- ฝั่งซ้าย -->
                                <div>
                                    <?php if (!$user): ?>
                                        <a href="view_public.php?id=<?= $t['id'] ?>" style="font-weight:600; font-size:15px;"
                                            target="_blank">
                                            <?= htmlspecialchars($t['title']) ?>
                                        </a>
                                    <?php else: ?>
                                        <a href="view_topic.php?id=<?= $t['id'] ?>"
                                            style="font-weight:600; font-size:15px; text-decoration:none;">
                                            <?= htmlspecialchars($t['title']) ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($t['visibility'] === 'public'): ?>
                                        <span class="badge badge-public">สาธารณะ</span>
                                    <?php elseif ($t['visibility'] === 'internal'): ?>
                                        <span class="badge badge-internal">เฉพาะเจ้าหน้าที่</span>
                                    <?php else: ?>
                                        <span class="badge badge-inherit"><?= visThai($realVis) ?></span>
                                    <?php endif; ?>

                                    <?= $uploadBadge ?>

                                    <span class="badge badge-public" style="margin-left:10px;">
                                        เข้าดู: <?= $t['views'] ?> ครั้ง
                                    </span>

                                    <!-- ประชาชนไม่เห็นปุ่ม -->
                                    <?php if ($user): ?>

                                        <!-- ไอคอนแก้ไข -->
                                        <a href="edit_topic.php?id=<?= $t['id'] ?>" class="btn btn-ghost btn-sm"
                                            style="margin-left:10px; padding:3px 6px;" title="แก้ไขชื่อเรื่อง">
                                            <i class="fa fa-pencil-alt"></i>
                                        </a>

                                        <!-- ไอคอนลบ -->
                                        <?php if ($uploaded_count == 0): ?>
                                            <form method="POST" action="delete_topic_safe.php"
                                                onsubmit="return confirm('ต้องการลบเรื่องนี้?');" style="display:inline;">
                                                <input type="hidden" name="topic_id" value="<?= $t['id'] ?>">
                                                <button class="btn btn-ghost btn-sm" style="padding:3px 6px; color:#991b1b;"
                                                    title="ลบเรื่อง">
                                                    <i class="fas fa-eraser"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                    <?php endif; ?>
                                </div>

                                <!-- ฝั่งขวา -->
                                <div style="text-align:right;">
                                    <span class="badge badge-inherit" style="margin-left:10px;">
                                        สร้างเมื่อ : <?= date("d/m/Y", strtotime($t['created_at'])) ?>
                                    </span>

                                    <span class="badge badge-internal" style="margin-left:10px;">
                                        สร้างโดย : <?= htmlspecialchars($t['username']) ?>
                                    </span>
                                </div>

                            </div>
                        </div>

                    </div>

                <?php endforeach; ?>

                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" style="margin-top:20px;">
                        <ul class="pagination">

                            <!-- ไปหน้าแรก -->
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link"
                                        href="?group=<?= $group_id ?>&page=1&search=<?= $search ?>&creator=<?= $creator ?>&vis=<?= $vis ?>&upload_status=<?= $upload_status ?>">หน้าแรก</a>
                                </li>
                            <?php endif; ?>

                            <!-- ก่อนหน้า -->
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link"
                                        href="?group=<?= $group_id ?>&page=<?= $page - 1 ?>&search=<?= $search ?>&creator=<?= $creator ?>&vis=<?= $vis ?>&upload_status=<?= $upload_status ?>">ก่อนหน้า</a>
                                </li>
                            <?php endif; ?>

                            <!-- ตัวเลขหน้า -->
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                    <a class="page-link"
                                        href="?group=<?= $group_id ?>&page=<?= $i ?>&search=<?= $search ?>&creator=<?= $creator ?>&vis=<?= $vis ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <!-- ถัดไป -->
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link"
                                        href="?group=<?= $group_id ?>&page=<?= $page + 1 ?>&search=<?= $search ?>&creator=<?= $creator ?>&vis=<?= $vis ?>&upload_status=<?= $upload_status ?>">ถัดไป</a>
                                </li>
                            <?php endif; ?>

                            <!-- ไปหน้าสุดท้าย -->
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link"
                                        href="?group=<?= $group_id ?>&page=<?= $totalPages ?>&search=<?= $search ?>&creator=<?= $creator ?>&vis=<?= $vis ?>&upload_status=<?= $upload_status ?>">หน้าสุดท้าย</a>
                                </li>
                            <?php endif; ?>

                        </ul>
                    </nav>
                <?php endif; ?>

            </div>
        </div>

    </div>

</body>

</html>