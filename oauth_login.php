<?php
session_start();
require 'db.php';

// ป้องกัน CSRF ด้วย state แบบสุ่ม เก็บไว้ตรวจตอน callback
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

// ปลายทางหลัง login สำเร็จ (ปรับได้ผ่าน ?redirect_to=xxx.php)
$redirectTo = $_GET['redirect_to'] ?? 'dashboard.php';
// กันไม่ให้ปลายทางหลุดออกนอกระบบ (ต้องเป็นไฟล์ .php ในโฟลเดอร์เดียวกันเท่านั้น)
if (!preg_match('/^[a-zA-Z0-9_\-]+\.php(\?.*)?$/', $redirectTo)) {
    $redirectTo = 'dashboard.php';
}
$_SESSION['oauth_redirect_to'] = $redirectTo;

$clientId    = $_ENV['HEALTH_CLIENT_ID'] ?? '';
$redirectUri = $_ENV['HEALTH_REDIRECT_URI'] ?? '';

if ($clientId === '' || $redirectUri === '') {
    die('Provider ID OAuth ยังไม่ได้ตั้งค่า กรุณาเพิ่ม HEALTH_CLIENT_ID และ HEALTH_REDIRECT_URI ในไฟล์ .env');
}

$url = 'https://moph.id.th/oauth/redirect'
    . '?client_id=' . urlencode($clientId)
    . '&redirect_uri=' . urlencode($redirectUri)
    . '&response_type=code'
    . '&state=' . urlencode($state);

header("Location: $url");
exit;
