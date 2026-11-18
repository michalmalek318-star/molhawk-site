<?php
// Sends launch-update and early-access form submissions to contact@molhawk.com.
header('Content-Type: application/json; charset=utf-8');

const TO = 'contact@molhawk.com';
const FROM = 'noreply@molhawk.com';

function done(int $code, array $body): void {
    http_response_code($code);
    echo json_encode($body);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    done(405, ['ok' => false, 'error' => 'method']);
}

// Honeypot: real users leave this field empty.
if (!empty($_POST['website'])) {
    done(200, ['ok' => true]);
}

$clean = fn(string $k, int $max = 200): string =>
    trim(mb_substr(str_replace(["\r", "\n"], ' ', strip_tags($_POST[$k] ?? '')), 0, $max));

$type = $clean('form') === 'access' ? 'Early B2B access request' : 'Launch updates sign-up';
$email = filter_var($clean('email'), FILTER_VALIDATE_EMAIL);
if (!$email) {
    done(422, ['ok' => false, 'error' => 'email']);
}

$lines = [
    'Form: ' . $type,
    'Email: ' . $email,
    'Company: ' . $clean('company'),
    'Country: ' . $clean('country'),
    'Interest: ' . $clean('interest'),
    'Date: ' . gmdate('Y-m-d H:i') . ' UTC',
    'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? ''),
];

$headers = implode("\r\n", [
    'From: MOLHAWK website <' . FROM . '>',
    'Reply-To: ' . $email,
    'Content-Type: text/plain; charset=utf-8',
]);

$sent = mail(TO, '=?UTF-8?B?' . base64_encode('MOLHAWK: ' . $type) . '?=', implode("\n", $lines), $headers, '-f' . FROM);
done($sent ? 200 : 500, ['ok' => $sent]);
