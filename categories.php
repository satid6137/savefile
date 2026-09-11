<?php
session_start();
require 'db.php';

function resolveVisibility($group_visibility, $cat_visibility)
{
    if ($group_visibility === 'public' || $group_visibility === 'internal')
        return $group_visibility;

    // inherit → ใช้สิทธิ์ของหมวด
    return $cat_visibility;
}

function visThai($v)
{
    return [
        'public' => 'สาธารณะ',
        'internal' => 'เฉพาะเจ้าหน้าที่',
        'inherit' => 'ตามหมวด',
    ][$v] ?? $v;
}

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

// ดึงหมวดทั้งหมด
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

foreach ($categories as $index => $cat) {

    $stmt = $pdo->prepare("
        SELECT tg.*, 
               (SELECT COUNT(*) FROM topics WHERE group_id = tg.id) AS topicCount
        FROM topic_groups tg
        WHERE tg.category_id = ?
        ORDER BY tg.name ASC
    ");
    $stmt->execute([$cat['id']]);
    $cat['groups'] = $stmt->fetchAll();

    // เก็บกลับเข้า array
    $categories[$index] = $cat;
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>จัดการหมวดและหัวข้อ</title>

    <!-- Icon -->
    <link rel="icon" href="/savefile/assets/icons/health48.png" type="image/png">
    <link rel="apple-touch-icon" href="/savefile/assets/icons/health48.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link rel="stylesheet" href="assets/css/categories.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

</head>

<body>

    <nav class="topbar">
        <div class="topbar-brand">
            <span>&#9679;</span> จัดการหมวดและหัวข้อ
        </div>
        <div class="topbar-user">
            <span>สวัสดี, <strong>
                    <?= htmlspecialchars($user['username']) ?>
                </strong></span>
            <a href="dashboard.php" class="btn btn-ghost btn-sm">Dashboard</a>
            <a href="logout.php" class="btn btn-ghost btn-sm">ออกจากระบบ</a>
        </div>
    </nav>

    <div class="container">

        <!-- เพิ่มหมวดใหม่ -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">เพิ่มหมวดใหม่</div>
            <div class="card-body">

                <form method="POST" action="create_category.php">

                    <div class="form-group" style="margin-bottom:15px;">
                        <label style="display:block; margin-bottom:6px; font-weight:500;">ชื่อหมวด</label>
                        <input name="name" placeholder="กรอกชื่อหมวด..." required style="width:100%; padding:10px 12px; border:1px solid var(--border-strong);
                  border-radius:var(--radius-sm); font-size:14px;">
                    </div>

                    <div class="form-group" style="margin-bottom:15px;">
                        <label style="display:block; margin-bottom:6px; font-weight:500;">สิทธิ์หมวด</label>
                        <select name="visibility" style="width:100%; padding:10px 12px; border:1px solid var(--border-strong);
                   border-radius:var(--radius-sm); font-size:14px;">
                            <option value="public">สาธารณะ</option>
                            <option value="internal">เฉพาะเจ้าหน้าที่</option>
                        </select>
                    </div>

                    <button class="btn btn-primary btn-sm">เพิ่มหมวด</button>

                </form>

            </div>
        </div>

        <!-- รายการหมวด + หัวข้อ -->
        <?php foreach ($categories as $cat): ?>

            <?php
            // ดึงหัวข้อในหมวดนี้
            $stmt = $pdo->prepare("SELECT * FROM topic_groups WHERE category_id = ? ORDER BY CAST(SUBSTRING_INDEX(name, '.', 1) AS UNSIGNED) ASC");
            $stmt->execute([$cat['id']]);
            $groups = $stmt->fetchAll();

            // เช็คว่ามีหัวข้อไหม
            $hasGroups = count($groups) > 0;
            ?>

            <div class="card">
                <div class="card-header">
                    หมวด: <?= htmlspecialchars($cat['name']) ?>

                    <?php if ($cat['visibility'] === 'public'): ?>
                        <span class="badge badge-public">สาธารณะ</span>
                    <?php else: ?>
                        <span class="badge badge-internal">เฉพาะเจ้าหน้าที่</span>
                    <?php endif; ?>
                </div>

                <div class="card-body">

                    <!-- แถวจัดการหมวด + เพิ่มหัวข้อ -->
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">

                        <!-- ปุ่มจัดการหมวด -->
                        <div style="display:flex; gap:10px;">
                            <button class="btn btn-warning btn-sm"
                                onclick="openEditCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name']) ?>', '<?= $cat['visibility'] ?>')">
                                แก้ไขหมวด
                            </button>

                            <?php if (!$hasGroups): ?>
                                <a href="delete_category.php?id=<?= $cat['id'] ?>" onclick="return confirm('ต้องการลบหมวดนี้?')"
                                    class="btn btn-danger btn-sm">ลบหมวด</a>
                            <?php else: ?>
                                <span style="color:red; font-size:12px;"></span>
                            <?php endif; ?>
                        </div>

                        <!-- ฟอร์มเพิ่มหัวข้อ -->
                        <form method="POST" action="create_group.php" style="display:flex; gap:10px; align-items:center;">

                            <!-- ส่งหมวดที่หัวข้อนี้อยู่ -->
                            <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">

                            <!-- ชื่อหัวข้อ -->
                            <input name="group_name" placeholder="ชื่อหัวข้อใหม่..." required
                                style="padding:6px 10px; border:1px solid var(--border); border-radius:6px;">

                            <!-- สิทธิ์หัวข้อ -->
                            <select name="visibility"
                                style="padding:6px 10px; border:1px solid var(--border); border-radius:6px;">
                                <option value="inherit">สิทธิ์ตามหมวด</option>
                                <option value="public">สาธารณะ</option>
                                <option value="internal">เฉพาะเจ้าหน้าที่</option>
                            </select>

                            <!-- ปุ่มเพิ่ม -->
                            <button class="btn btn-primary btn-sm">เพิ่มหัวข้อ</button>
                        </form>

                    </div>

                    <hr style="margin:15px 0;">

                    <!-- รายการหัวข้อ -->
                    <?php foreach ($groups as $g): ?>

                        <?php
                        // นับจำนวนเรื่องในหัวข้อนี้
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM topics WHERE group_id = ?");
                        $stmt->execute([$g['id']]);
                        $topicCount = $stmt->fetchColumn();

                        // ดึงชื่อผู้สร้างหัวข้อ
                        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                        $stmt->execute([$g['created_by']]);
                        $creatorName = $stmt->fetchColumn() ?: 'unknown';
                        ?>

                        <div class="group-block">
                            <div class="group-title" style="display:flex; justify-content:space-between; align-items:center;">

                                <!-- ซ้าย: ชื่อหัวข้อ + จำนวนเรื่อง + สิทธิ์ -->
                                <div>
                                    <a href="topics.php?group=<?= $g['id'] ?>"
                                        style="font-weight:600; font-size:15px; text-decoration:none;">
                                        <?= htmlspecialchars($g['name']) ?> (<?= $topicCount ?> เรื่อง)
                                    </a>

                                    <?php
                                    $realVis = resolveVisibility($g['visibility'], $cat['visibility']);
                                    ?>

                                    <?php if ($realVis === 'public'): ?>
                                        <span class="badge badge-public">สาธารณะ</span>
                                    <?php elseif ($realVis === 'internal'): ?>
                                        <span class="badge badge-internal">เฉพาะเจ้าหน้าที่</span>
                                    <?php endif; ?>

                                    <span class="badge badge-public" style="margin-left:10px;">
                                        เข้าดู: <?= $g['views'] ?> ครั้ง
                                    </span>

                                    <!-- ไอคอนแก้ไขหัวข้อ -->
                                    <a href="#"
                                        onclick="openEditGroup(<?= $g['id'] ?>,'<?= htmlspecialchars($g['name']) ?>','<?= $g['visibility'] ?>',<?= $g['category_id'] ?>)"
                                        class="btn btn-ghost btn-sm" style="margin-left:10px; padding:3px 6px;"
                                        title="แก้ไขหัวข้อ">
                                        <i class="fas fa-pencil-alt"></i>
                                    </a>

                                    <!-- ไอคอนลบหัวข้อ -->
                                    <?php if ($topicCount == 0): ?>
                                        <form method="POST" action="delete_group.php"
                                            onsubmit="return confirm('ต้องการลบหัวข้อนี้?');" style="display:inline;">
                                            <input type="hidden" name="group_id" value="<?= $g['id'] ?>">
                                            <button class="btn btn-ghost btn-sm" style="padding:3px 6px; color:#991b1b;"
                                                title="ลบหัวข้อนี้">
                                                <i class="fas fa-eraser"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>

                                <!-- ขวา: ผู้สร้าง -->
                                <div style="font-size:12px; color:#555;">
                                    สร้างโดย: <?= htmlspecialchars($creatorName) ?>
                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>
            </div>

        <?php endforeach; ?>

    </div>

    <!-- Modal แก้ไขหมวด -->
    <div class="modal" id="editCategoryModal">
        <div class="modal-content">
            <h3>แก้ไขหมวด</h3>
            <form method="POST" action="update_category.php">
                <input type="hidden" name="id" id="editCatId">
                <input name="name" id="editCatName" required>
                <select name="visibility" id="editCatVis">
                    <option value="public">สาธารณะ</option>
                    <option value="internal">เฉพาะเจ้าหน้าที่</option>
                </select>
                <button class="btn btn-primary" style="margin-top:10px;">บันทึก</button>
            </form>
        </div>
    </div>

    <!-- Modal แก้ไขหัวข้อ -->
    <div class="modal" id="editGroupModal">
        <div class="modal-content">
            <h3>แก้ไขหัวข้อ</h3>

            <form method="POST" action="update_group.php">

                <input type="hidden" name="id" id="editGroupId">

                <!-- ชื่อหัวข้อ -->
                <div class="form-group" style="margin-bottom:10px;">
                    <label>ชื่อหัวข้อ</label>
                    <input name="name" id="editGroupName" required>
                </div>

                <!-- สิทธิ์หัวข้อ -->
                <div class="form-group" style="margin-bottom:10px;">
                    <label>สิทธิ์หัวข้อ</label>
                    <select name="visibility" id="editGroupVis">
                        <option value="inherit">ตามหมวด</option>
                        <option value="public">สาธารณะ</option>
                        <option value="internal">เฉพาะเจ้าหน้าที่</option>
                    </select>
                </div>

                <!-- ย้ายหมวด -->
                <div class="form-group" style="margin-bottom:10px;">
                    <label>ย้ายไปหมวด</label>
                    <select name="category_id" id="editGroupCategory">
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- ปุ่ม -->
                <div style="display:flex; gap:10px; margin-top:10px;">
                    <button class="btn btn-primary btn-sm">บันทึก</button>

                    <button type="button" class="btn btn-ghost btn-sm"
                        onclick="document.getElementById('editGroupModal').classList.remove('active');">
                        ยกเลิก
                    </button>
                </div>

            </form>
        </div>
    </div>

    <script>
        function openEditCategory(id, name, vis) {
            document.getElementById('editCatId').value = id;
            document.getElementById('editCatName').value = name;
            document.getElementById('editCatVis').value = vis;
            document.getElementById('editCategoryModal').classList.add('active');
        }

        function openEditGroup(id, name, vis, categoryId) {
            document.getElementById('editGroupId').value = id;
            document.getElementById('editGroupName').value = name;
            document.getElementById('editGroupVis').value = vis;
            document.getElementById('editGroupCategory').value = categoryId;
            document.getElementById('editGroupModal').classList.add('active');
        }

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('active');
            }
        });
    </script>

</body>

</html>