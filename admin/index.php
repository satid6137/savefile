<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

/* ---------------- USERS ---------------- */
$stmt = $pdo->query("
    SELECT id, username, display_name, role, created_at, status, twofa_enabled
    FROM users
    ORDER BY created_at DESC
");
$users = $stmt->fetchAll();

/* ---------------- TOPICS + PAGINATION ---------------- */
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$totalStmt = $pdo->query("SELECT COUNT(*) FROM topics");
$totalTopics = $totalStmt->fetchColumn();

$stmt2 = $pdo->prepare("
    SELECT t.*, u.username
    FROM topics t
    JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt2->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt2->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt2->execute();
$topics = $stmt2->fetchAll();

$totalPages = ceil($totalTopics / $limit);

// จำนวนแท็บที่ต้องการแสดง
$window = 15;

// คำนวณช่วงหน้า
$start = max(1, $page - floor($window / 2));
$end = min($totalPages, $start + $window - 1);

// ถ้าช่วงท้ายไม่ถึง 10 หน้า ให้ขยับช่วงเริ่มต้น
if (($end - $start + 1) < $window) {
    $start = max(1, $end - $window + 1);
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="icon" href="/savefile/assets/icons/health48.png" type="image/png">
    <link rel="apple-touch-icon" href="/savefile/assets/icons/health48.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/Admin_index.css">
</head>

<body>

    <nav class="topbar">
        <div class="topbar-brand">
            <span class="dot">&#9679;</span> Hangchat Hospital Webboard/Drive
            <span class="topbar-badge">Admin</span>
        </div>
        <div class="topbar-actions">
            <a href="../dashboard.php" class="btn btn-ghost">Dashboard</a>
            <a href="../logout.php" class="btn btn-ghost">ออกจากระบบ</a>
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

        <!-- Create user -->
        <div class="card">
            <div class="card-header">สร้างผู้ใช้ใหม่</div>
            <div class="card-body">
                <form method="POST" action="create_user.php">
                    <div class="my-form-row">
                        <input name="username" placeholder="Username" required>
                        <input name="password" type="password" placeholder="Password" required>
                        <select name="role">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                        <button type="submit" class="btn btn-primary">สร้างผู้ใช้</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users table -->
        <div class="card">
            <div class="card-header">
                ผู้ใช้ทั้งหมด
                <span class="count">
                    <?= count($users) ?> คน
                </span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>ชื่อแสดง</th>
                            <th>Role</th>
                            <th>วันที่สร้าง</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($u['username']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($u['display_name']) ?>
                                </td>
                                <td>
                                    <span class="role-badge <?= $u['role'] === 'admin' ? 'role-admin' : 'role-user' ?>">
                                        <?= $u['role'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?= date('d M Y', strtotime($u['created_at'])) ?>
                                </td>
                                <td>
                                    <div class="actions">

                                        <?php if ($u['role'] === 'user'): ?>
                                            <a href="change_role.php?id=<?= $u['id'] ?>&role=admin"
                                                class="btn btn-warning btn-sm">Make Admin</a>
                                        <?php else: ?>
                                            <a href="change_role.php?id=<?= $u['id'] ?>&role=user"
                                                class="btn btn-ghost btn-sm">Make User</a>
                                        <?php endif; ?>

                                        <a href="change_password.php?id=<?= $u['id'] ?>" class="btn btn-info btn-sm">
                                            Change Password
                                        </a>

                                        <?php if ($u['status'] == 1): ?>
                                            <a href="disable_user.php?id=<?= $u['id'] ?>" class="btn btn-danger btn-sm"
                                                onclick="return confirm('ปิดการใช้งานผู้ใช้นี้?')">Disable</a>
                                        <?php else: ?>
                                            <a href="enable_user.php?id=<?= $u['id'] ?>" class="btn btn-success btn-sm"
                                                onclick="return confirm('เปิดการใช้งานผู้ใช้นี้?')">Enable</a>
                                        <?php endif; ?>

                                        <?php if ($u['twofa_enabled'] == 0): ?>
                                            <a href="enable_2fa.php?id=<?= $u['id'] ?>" class="btn btn-success btn-sm">Enable
                                                2FA</a>
                                        <?php else: ?>
                                            <a href="disable_2fa.php?id=<?= $u['id'] ?>" class="btn btn-warning btn-sm">Disable
                                                2FA</a>
                                        <?php endif; ?>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Topics -->
        <div class="card">
            <div class="card-header">
                เรื่องทั้งหมด
                <span class="count">
                    <?= $totalTopics ?> เรื่อง
                </span>
            </div>

            <?php if (count($topics) > 0): ?>
                <?php foreach ($topics as $topic): ?>
                    <div class="topic-item">
                        <div class="topic-info">
                            <div class="topic-title">
                                <?= htmlspecialchars($topic['title']) ?>
                            </div>
                            <div class="topic-meta">
                                โดย
                                <?= htmlspecialchars($topic['username']) ?> ·
                                <?= date('d M Y', strtotime($topic['created_at'])) ?>
                            </div>
                        </div>
                        <a href="../view_topic.php?id=<?= $topic['id'] ?>" class="btn btn-success btn-sm">ดู</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty">ยังไม่มีเรื่อง</div>
            <?php endif; ?>

            <!-- Pagination -->
            <div class="tab-pagination">

                <!-- เริ่มต้น -->
                <a href="?page=1" class="tab-btn <?= $page == 1 ? 'disabled' : '' ?>">&laquo;</a>

                <!-- ก่อนหน้า -->
                <a href="?page=<?= max(1, $page - 1) ?>"
                    class="tab-btn <?= $page == 1 ? 'disabled' : '' ?>">&lsaquo;</a>

                <!-- ตัวเลข -->
                <div class="tab-numbers">
                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <a href="?page=<?= $i ?>" class="tab-number <?= $i == $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>

                <!-- ถัดไป -->
                <a href="?page=<?= min($totalPages, $page + 1) ?>"
                    class="tab-btn <?= $page == $totalPages ? 'disabled' : '' ?>">&rsaquo;</a>

                <!-- สุดท้าย -->
                <a href="?page=<?= $totalPages ?>"
                    class="tab-btn <?= $page == $totalPages ? 'disabled' : '' ?>">&raquo;</a>

            </div>

        </div>

    </div>

    <script>
        const flash = document.getElementById('flash-message');
        if (flash) setTimeout(() => flash.remove(), 3500);
    </script>

</body>

</html>