<?php
require_once __DIR__ . '/../config/database.php';

function json_out($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function norm(string $s): string
{
    return strtolower(trim(preg_replace('/\s+/', ' ', $s)));
}

function get_settings(): array
{
    $pdo = get_pdo();
    return $pdo->query('SELECT * FROM settings WHERE id = 1')->fetch();
}

function is_voting_open(): bool
{
    return (bool)(get_settings()['voting_open'] ?? 1);
}

function find_checkin_by_token(string $token): ?array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM checkins WHERE checkin_token = ?');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function find_soft_duplicate(string $name, string $church, string $area): ?array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM checkins WHERE name_norm = ? AND church_norm = ? AND area_norm = ?');
    $stmt->execute([norm($name), norm($church), norm($area)]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function active_amendments(): array
{
    $pdo = get_pdo();
    return $pdo->query('SELECT * FROM amendments WHERE is_active = 1 ORDER BY display_order ASC, id ASC')->fetchAll();
}

function all_amendments(): array
{
    $pdo = get_pdo();
    return $pdo->query('SELECT * FROM amendments ORDER BY display_order ASC, id ASC')->fetchAll();
}

function stats_snapshot(): array
{
    $pdo = get_pdo();
    $checkedIn = (int)$pdo->query('SELECT COUNT(*) c FROM checkins')->fetch()['c'];
    $submitted = (int)$pdo->query('SELECT COUNT(*) c FROM checkins WHERE ballot_submitted = 1')->fetch()['c'];
    $turnout = $checkedIn > 0 ? round(($submitted / $checkedIn) * 100, 1) : 0.0;
    return [
        'checked_in' => $checkedIn,
        'ballots_submitted' => $submitted,
        'turnout_rate' => $turnout,
    ];
}

function results_by_amendment(): array
{
    $pdo = get_pdo();
    $amendments = active_amendments();
    $out = [];
    foreach ($amendments as $a) {
        // Vote tallies
        $stmt = $pdo->prepare(
            "SELECT vote_choice, COUNT(*) c FROM votes WHERE amendment_id = ? GROUP BY vote_choice"
        );
        $stmt->execute([$a['id']]);
        $tally = ['yes' => 0, 'no' => 0, 'abstain' => 0, 'comment' => 0];
        foreach ($stmt->fetchAll() as $row) {
            if (isset($tally[$row['vote_choice']])) {
                $tally[$row['vote_choice']] = (int)$row['c'];
            }
        }
        $total = $tally['yes'] + $tally['no'] + $tally['abstain'] + $tally['comment'];

        // Retrieve all comment texts for this amendment (most recent first)
        $cStmt = $pdo->prepare(
            "SELECT comment_text, voted_at
             FROM votes
             WHERE amendment_id = ?
               AND vote_choice = 'comment'
               AND comment_text IS NOT NULL
             ORDER BY voted_at DESC"
        );
        $cStmt->execute([$a['id']]);
        $commentTexts = array_column($cStmt->fetchAll(), 'comment_text');

        $out[] = [
            'id'                 => (int)$a['id'],
            'proposal_no'        => $a['proposal_no'],
            'article_no'         => $a['article_no'],
            'section'            => $a['section'],
            'page_no'            => $a['page_no'],
            'summary'            => $a['summary'],
            'proposed_amendment' => $a['proposed_amendment'],
            'tally'              => $tally,
            'total'              => $total,
            'comments'           => $commentTexts,  // array of strings
        ];
    }
    return $out;
}
