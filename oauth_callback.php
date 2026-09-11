<?php
session_start();
require 'db.php';

/**
 * เรียก API แบบ JSON (POST หรือ GET) ด้วย cURL
 */
function oauth_http_json(string $method, string $url, array $data = [], array $headers = []): array
{
    $ch = curl_init();
    $method = strtoupper($method);

    if ($method === 'GET' && !empty($data)) {
        $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($data);
    }

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], $headers),
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        return ['ok' => false, 'status' => 0, 'data' => null, 'error' => $error];
    }

    $json = json_decode($body, true);
    return [
        'ok' => $status >= 200 && $status < 300,
        'status' => $status,
        'data' => $json,
        'error' => $json['message'] ?? $json['error'] ?? null,
    ];
}

function oauth_fail(string $message, $debugData = null): void
{
    http_response_code(400);
    $debugOn = ($_ENV['OAUTH_DEBUG'] ?? '0') === '1';
    ?>
    <!DOCTYPE html>
    <html lang="th">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>เข้าสู่ระบบไม่สำเร็จ</title>
        <link rel="icon" href="/savefile/assets/icons/health48.png" type="image/png">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="assets/css/oauth_callback.css">
    </head>

    <body>
        <div class="card<?= ($debugOn && $debugData !== null) ? ' wide' : '' ?>">
            <div class="card-icon">&#9888;</div>
            <div class="card-body">
                <h2>เข้าสู่ระบบไม่สำเร็จ</h2>
                <p>
                    <?= htmlspecialchars($message) ?>
                </p>
            </div>
            <?php if ($debugOn && $debugData !== null): ?>
                <div class="debug-box">
                    <div class="debug-title">Debug: ข้อมูลดิบที่ได้จาก Provider ID (ตั้ง OAUTH_DEBUG=0 ใน .env เมื่อใช้งานจริง)
                    </div>
                    <pre><?= htmlspecialchars(json_encode($debugData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                </div>
            <?php endif; ?>
            <div class="card-footer">
                <a href="index.php" class="btn">กลับหน้าแรก</a>
            </div>
        </div>
    </body>

    </html>
    <?php
    exit;
}

// ---------- 0) ตรวจ code / state ----------
$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;

if (!$code) {
    oauth_fail('ไม่พบ authorization code จาก Provider ID');
}
if (!$state || !isset($_SESSION['oauth_state']) || !hash_equals($_SESSION['oauth_state'], $state)) {
    oauth_fail('ตรวจสอบ state ไม่ผ่าน กรุณาเข้าสู่ระบบใหม่อีกครั้ง');
}
unset($_SESSION['oauth_state']);

$redirectTo = $_SESSION['oauth_redirect_to'] ?? 'dashboard.php';
unset($_SESSION['oauth_redirect_to']);

// ---------- 1) code -> Health ID access_token ----------
$tokenRes = oauth_http_json('POST', 'https://moph.id.th/api/v1/token', [
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $_ENV['HEALTH_REDIRECT_URI'] ?? '',
    'client_id' => $_ENV['HEALTH_CLIENT_ID'] ?? '',
    'client_secret' => $_ENV['HEALTH_CLIENT_SECRET'] ?? '',
]);
if (!$tokenRes['ok'] || empty($tokenRes['data']['data']['access_token'])) {
    oauth_fail('เข้าสู่ระบบผ่าน Health ID ไม่สำเร็จ: ' . ($tokenRes['error'] ?? 'unknown error'));
}
$healthAccessToken = $tokenRes['data']['data']['access_token'];

// ---------- 2) Health ID token -> Provider ID access_token ----------
$providerTokenRes = oauth_http_json('POST', 'https://provider.id.th/api/v1/services/token', [
    'client_id' => $_ENV['PROVIDER_CLIENT_ID'] ?? '',
    'secret_key' => $_ENV['PROVIDER_CLIENT_SECRET'] ?? '',
    'token_by' => 'Health ID',
    'token' => $healthAccessToken,
]);
if (!$providerTokenRes['ok'] || empty($providerTokenRes['data']['data']['access_token'])) {
    oauth_fail('ขอ Provider ID token ไม่สำเร็จ: ' . ($providerTokenRes['error'] ?? 'unknown error'));
}
$providerAccessToken = $providerTokenRes['data']['data']['access_token'];

// ---------- 3) Provider ID token -> profile ----------
$profileRes = oauth_http_json('GET', 'https://provider.id.th/api/v1/services/profile', ['position_type' => 1], [
    'client-id: ' . ($_ENV['PROVIDER_CLIENT_ID'] ?? ''),
    'secret-key: ' . ($_ENV['PROVIDER_CLIENT_SECRET'] ?? ''),
    'Authorization: Bearer ' . $providerAccessToken,
]);
if (!$profileRes['ok'] || empty($profileRes['data']['data'])) {
    oauth_fail('ดึงข้อมูลโปรไฟล์ไม่สำเร็จ: ' . ($profileRes['error'] ?? 'unknown error'), $profileRes['data']);
}
$profile = $profileRes['data']['data'];

// เก็บ log ของโปรไฟล์ดิบไว้ช่วง dev เพื่อตรวจสอบชื่อ field จริง (ลบ/คอมเมนต์ทิ้งเมื่อขึ้น production)
error_log('[oauth_callback] profile payload: ' . json_encode($profile, JSON_UNESCAPED_UNICODE));

// ---------- 4) ตรวจสอบว่าอยู่หน่วยงานที่อนุญาตหรือไม่ ----------
// รหัสหน่วยงานอยู่ใน organization[].hcode (คนหนึ่งอาจสังกัดได้หลายหน่วยงาน)
$organizations = $profile['organization'] ?? [];
$orgHcodes = array_values(array_filter(array_map(
    fn($org) => $org['hcode'] ?? null,
    $organizations
)));

$allowedList = array_filter(array_map('trim', explode(',', $_ENV['ALLOWED_HOSPCODES'] ?? '')));

if (empty($allowedList)) {
    oauth_fail('ยังไม่ได้ตั้งค่า ALLOWED_HOSPCODES ใน .env (ระบุรหัสหน่วยงานที่อนุญาต เช่น 11156)');
}

// หาโรงพยาบาลที่ตรงกับ allowlist ตัวแรกที่เจอ (ใช้ทั้งบันทึกและแสดงผล)
$matchedOrg = null;
foreach ($organizations as $org) {
    if (isset($org['hcode']) && in_array((string) $org['hcode'], $allowedList, true)) {
        $matchedOrg = $org;
        break;
    }
}

if (!$matchedOrg) {
    $shownCode = !empty($orgHcodes) ? implode(', ', $orgHcodes) : 'ไม่พบข้อมูล';
    oauth_fail('บัญชีของท่านไม่ได้สังกัดหน่วยงานที่กำหนดให้เข้าใช้งานระบบนี้ (รหัสหน่วยงาน: ' . $shownCode . ')', $profile);
}

$hospcode = $matchedOrg['hcode'];

// ---------- 5) หา / สร้างผู้ใช้ในระบบ — สิทธิ์เป็น 'user' เสมอ ----------
$providerUid = $profile['provider_id'] ?? $profile['account_id'] ?? null;
if (!$providerUid) {
    oauth_fail('ไม่พบรหัสอ้างอิงผู้ใช้ (provider_id) ในโปรไฟล์ที่ได้รับ', $profile);
}

$displayName = $profile['name_th']
    ?? trim(($profile['firstname_th'] ?? '') . ' ' . ($profile['lastname_th'] ?? ''));
if ($displayName === '') {
    $displayName = 'provider_' . $providerUid;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE provider_uid = ?");
$stmt->execute([$providerUid]);
$user = $stmt->fetch();

if (!$user) {
    $username = 'pid_' . $providerUid;
    $randomPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT); // ไม่ใช้ล็อกอินด้วยรหัสผ่านนี้

    $stmt = $pdo->prepare("
        INSERT INTO users (username, password, role, provider_uid, hospcode, display_name)
        VALUES (?, ?, 'user', ?, ?, ?)
    ");
    $stmt->execute([$username, $randomPassword, $providerUid, $hospcode, $displayName]);

    $user = [
        'id' => $pdo->lastInsertId(),
        'username' => $username,
        'role' => 'user',
    ];
} else {
    // อัปเดตข้อมูลล่าสุด และบังคับ role เป็น 'user' เสมอสำหรับบัญชีที่เข้าผ่าน Provider ID
    $stmt = $pdo->prepare("UPDATE users SET hospcode = ?, display_name = ?, role = 'user' WHERE id = ?");
    $stmt->execute([$hospcode, $displayName, $user['id']]);
    $user['role'] = 'user';
}

$_SESSION['user'] = [
    'id' => $user['id'],
    'username' => $user['username'],
    'role' => 'user',
];

header("Location: $redirectTo");
exit;