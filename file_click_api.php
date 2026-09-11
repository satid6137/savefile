<?php
require 'db.php';

$file_id = $_POST['id'] ?? null;
if (!$file_id) {
    http_response_code(400);
    echo "Missing id";
    exit;
}

$pdo->prepare("UPDATE files SET clicks = clicks + 1 WHERE id = ?")
    ->execute([$file_id]);

echo "OK";
?>