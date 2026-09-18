<?php
require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['error' => 'Method not allowed'], 405);
}

$body = read_json_body();
$name = trim($body['full_name'] ?? '');
$church = trim($body['local_church'] ?? '');
$area = trim($body['area'] ?? '');

if ($name === '' || $church === '' || $area === '') {
    json_out(['error' => 'Please enter your name, local church, and area.'], 422);
}
if (mb_strlen($name) > 150 || mb_strlen($church) > 150 || mb_strlen($area) > 100) {
    json_out(['error' => 'One of the fields is too long.'], 422);
}

if (!is_voting_open()) {
    json_out(['error' => 'Check-in is currently closed. Please see a convention official.'], 403);
}

$existing = find_soft_duplicate($name, $church, $area);
if ($existing) {
    if ((int)$existing['ballot_submitted'] === 1) {
        json_out(['error' => 'It looks like you have already checked in and submitted a ballot under this name.'], 409);
    }
    // Already checked in but hasn't voted yet — resume their session instead of duplicating.
    json_out([
        'token' => $existing['checkin_token'],
        'full_name' => $existing['full_name'],
        'resumed' => true,
    ]);
}

$pdo = get_pdo();
$token = bin2hex(random_bytes(16));
$token = substr($token, 0, 8) . '-' . substr($token, 8, 4) . '-' . substr($token, 12, 4) . '-' . substr($token, 16, 4) . '-' . substr($token, 20, 12);

$stmt = $pdo->prepare('INSERT INTO checkins
    (checkin_token, full_name, local_church, area, name_norm, church_norm, area_norm)
    VALUES (?,?,?,?,?,?,?)');
$stmt->execute([$token, $name, $church, $area, norm($name), norm($church), norm($area)]);

json_out(['token' => $token, 'full_name' => $name, 'resumed' => false]);
