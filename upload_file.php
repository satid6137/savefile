<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// รับค่าจากฟอร์ม
$paragraph_id = $_POST['paragraph_id'] ?? null;
$topic_id = $_POST['topic_id'] ?? null;
$files = $_FILES['files'] ?? [];

if (!$paragraph_id || !$topic_id || empty($files['name'])) {
    $_SESSION['message'] = "ข้อมูลไม่ครบถ้วน";
    header("Location: view_topic.php?id=$topic_id");
    exit;
}

// ชนิดไฟล์ที่อนุญาต (โดยนามสกุล)
$allowed_extensions = ['pdf', 'jpeg', 'jpg', 'png', 'gif', 'mp3', 'wav', 'ogg', 'mp4', 'avi', 'mkv', 'webm', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar'];

$target_dir = 'uploads/';
$max_filename_length = 100;

// สร้างโฟลเดอร์ถ้ายังไม่มี
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

$total = count($files['name']);
$success = 0;

$finfo = finfo_open(FILEINFO_MIME_TYPE); // เตรียมใช้ดึง MIME

for ($i = 0; $i < $total; $i++) {
    if ($files['error'][$i] !== UPLOAD_ERR_OK)
        continue;

    $original_name = basename($files['name'][$i]);
    $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    $mime_type = finfo_file($finfo, $files['tmp_name'][$i]);

    // ตรวจว่านามสกุลตรงรายการอนุญาต
    if (!in_array($extension, $allowed_extensions))
        continue;

    // ตรวจชื่อไฟล์
    if (mb_strlen($original_name, 'UTF-8') > $max_filename_length)
        continue;

    // สร้าง prefix ตาม MIME
    $mime_prefix = match (explode('/', $mime_type)[0]) {
        'application' => 'doc',
        'image' => 'img',
        'audio' => 'aud',
        'video' => 'vid',
        default => 'file'
    };

    // สร้างชื่อไฟล์ใหม่
    $filename = $mime_prefix . '_' . time() . '_' . uniqid() . '.' . $extension;
    $filepath = $target_dir . $filename;

    // ย้ายไฟล์เข้า uploads/
    if (move_uploaded_file($files['tmp_name'][$i], $filepath)) {
        $stmt = $pdo->prepare("
            INSERT INTO files (paragraph_id, filename, filepath, mime_type, uploaded_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$paragraph_id, $original_name, $filepath, $mime_type]);
        $success++;
    }
}

finfo_close($finfo);

// เตรียมข้อความแจ้งเตือน
$_SESSION['message'] = $success > 0
    ? "✅ อัปโหลดไฟล์ $success รายการเรียบร้อยแล้ว"
    : "❌ ไม่สามารถอัปโหลดไฟล์ได้";

header("Location: view_topic.php?id=$topic_id");
exit;