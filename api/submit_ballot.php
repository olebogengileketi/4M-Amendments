<?php
require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['error' => 'Method not allowed'], 405);
}

$body  = read_json_body();
$token = trim($body['token'] ?? '');
$votes = $body['votes'] ?? [];
// votes format: { amendment_id: { choice: 'yes'|'no'|'abstain'|'comment', comment_text?: '...' } }
// Also support legacy flat format: { amendment_id: 'yes'|'no'|'abstain' }

if ($token === '') {
    json_out(['error' => 'Missing check-in token. Please check in again.'], 422);
}
if (!is_voting_open()) {
    json_out(['error' => 'Voting is currently closed.'], 403);
}

$checkin = find_checkin_by_token($token);
if (!$checkin) {
    json_out(['error' => 'We could not find your check-in. Please check in again.'], 404);
}
if ((int)$checkin['ballot_submitted'] === 1) {
    json_out(['error' => 'A ballot has already been submitted for this check-in.'], 409);
}

$amendments   = active_amendments();
$validIds     = array_column($amendments, 'id');
$validChoices = ['yes', 'no', 'abstain', 'comment'];

// Normalise each vote entry and validate
$normalised = [];
foreach ($validIds as $id) {
    // JS sends amendment IDs as string keys; cast to string for unambiguous lookup.
    $key = (string)$id;

    if (!isset($votes[$key])) {
        json_out(['error' => 'Please cast a vote on every amendment before submitting.'], 422);
    }

    $entry = $votes[$key];

    // Support both flat string and object formats
    if (is_string($entry)) {
        $choice      = $entry;
        $commentText = null;
    } elseif (is_array($entry)) {
        $choice      = $entry['choice'] ?? '';
        $commentText = isset($entry['comment_text']) ? trim($entry['comment_text']) : null;
    } else {
        json_out(['error' => 'Invalid vote format.'], 422);
    }

    if (!in_array($choice, $validChoices, true)) {
        json_out(['error' => 'Please cast a vote on every amendment before submitting.'], 422);
    }

    // When choice is 'comment', comment_text is required and must not be blank
    if ($choice === 'comment') {
        if ($commentText === null || $commentText === '') {
            json_out(['error' => 'Please enter your comment or feedback before submitting.'], 422);
        }
        // Hard cap to match the textarea maxlength
        if (mb_strlen($commentText) > 2000) {
            json_out(['error' => 'Comment text must be 2 000 characters or fewer.'], 422);
        }
    } else {
        $commentText = null; // never store text for non-comment votes
    }

    $normalised[$key] = [
        'choice'       => $choice,
        'comment_text' => $commentText,
    ];
}

// Persist
$pdo = get_pdo();
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare(
        'INSERT INTO votes (checkin_token, amendment_id, vote_choice, comment_text)
         VALUES (?, ?, ?, ?)'
    );
    foreach ($validIds as $id) {
        $key = (string)$id;
        $stmt->execute([
            $token,
            $id,
            $normalised[$key]['choice'],
            $normalised[$key]['comment_text'],
        ]);
    }
    $upd = $pdo->prepare(
        'UPDATE checkins SET ballot_submitted = 1, submitted_at = CURRENT_TIMESTAMP
         WHERE checkin_token = ?'
    );
    $upd->execute([$token]);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_out(['error' => 'Something went wrong recording your ballot. Please try again.'], 500);
}

json_out(['success' => true]);
