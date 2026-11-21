<?php
header('Content-Type: application/json; charset=UTF-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$to = 'wgck1996@gmail.com';
$fields = [];
foreach ($_POST as $k => $v) {
    $clean_k = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$k);
    $clean_v = trim(strip_tags((string)$v));
    if ($clean_k !== '' && $clean_v !== '') {
        $fields[$clean_k] = $clean_v;
    }
}

if (empty($fields)) {
    echo json_encode(['ok' => false, 'error' => 'Please fill in your inquiry details.']);
    exit;
}

// Save backup lead record to server-side JSONL (blocked from web access via .htaccess)
$record = [
    'time_utc' => gmdate('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    'data' => $fields
];
@file_put_contents(__DIR__ . '/rfq-inbox-backup.jsonl', json_encode($record, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);

$reply_email = '';
foreach ($fields as $k => $v) {
    if (stripos($k, 'email') !== false && filter_var($v, FILTER_VALIDATE_EMAIL)) {
        $reply_email = $v;
        break;
    }
}

$subject = '[XEA B2B RFQ] New Wholesale Inquiry';
$body = "New B2B Inquiry from XEA Website\n=================================\n";
foreach ($fields as $k => $v) {
    $body .= strtoupper($k) . ": " . $v . "\n";
}
$body .= "=================================\nTime (UTC): " . gmdate('Y-m-d H:i:s') . "\n";

$host = preg_replace('/[^a-zA-Z0-9.-]/', '', $_SERVER['HTTP_HOST'] ?? 'xeatoy.com');
$headers = "From: XEA Website <noreply@" . $host . ">\r\n";
if ($reply_email !== '') {
    $headers .= "Reply-To: " . $reply_email . "\r\n";
}
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

@mail($to, $subject, $body, $headers);
echo json_encode(['ok' => true, 'message' => 'Inquiry received']);
