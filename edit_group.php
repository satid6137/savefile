<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$group_id = $_GET['id'] ?? null;

if (!$group_id) {
    $_SESSION['message'] = "❌ ไม่พบรหัสหัวข้อ";
    header("Location: dashboard.php");
    exit;
}

// ดึงข้อมูลหัวข้อ
$stmt = $pdo->prepare("SELECT * FROM topic_groups WHERE id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group) {
    $_SESSION['message'] = "❌ ไม่พบหัวข้อที่ต้องการแก้ไข";
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>แก้ไขหัวข้อ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-5">
        <h2 class="mb-4">✏️ แก้ไขหัวข้อ</h2>
        <form method="POST" action="update_group.php">
            <input type="hidden" name="group_id" value="<?= $group['id'] ?>">
            <div class="mb-3">
                <input name="new_name" class="form-control" value="<?= htmlspecialchars($group['name']) ?>" required>
            </div>
            <button type="submit" class="btn btn-success">บันทึกการแก้ไข</button>
            <a href="dashboard.php" class="btn btn-secondary">กลับ</a>
        </form>
    </div>
</body>

</html>