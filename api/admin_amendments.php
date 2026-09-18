<?php
require_once __DIR__ . '/../includes/helpers.php';

$pdo    = get_pdo();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    // ──────────────────────────────────────────────────────────────────────────
    case 'GET':
        $action = $_GET['action'] ?? '';

        if ($action === 'get_comments') {
            // Return all comments grouped by amendment, newest first
            $amendmentId = isset($_GET['amendment_id']) ? (int)$_GET['amendment_id'] : 0;

            if ($amendmentId > 0) {
                // Comments for one specific amendment
                $stmt = $pdo->prepare("
                    SELECT v.comment_text, v.voted_at,
                           c.full_name, c.local_church, c.area
                    FROM votes v
                    JOIN checkins c ON c.checkin_token = v.checkin_token
                    WHERE v.amendment_id = ?
                      AND v.vote_choice = 'comment'
                      AND v.comment_text IS NOT NULL
                    ORDER BY v.voted_at DESC
                ");
                $stmt->execute([$amendmentId]);
                json_out(['comments' => $stmt->fetchAll()]);
            }

            // All comments grouped by amendment
            $rows = $pdo->query("
                SELECT a.id AS amendment_id,
                       a.proposal_no,
                       a.summary,
                       v.comment_text,
                       v.voted_at,
                       c.full_name,
                       c.local_church,
                       c.area
                FROM votes v
                JOIN amendments a ON a.id = v.amendment_id
                JOIN checkins   c ON c.checkin_token = v.checkin_token
                WHERE v.vote_choice = 'comment'
                  AND v.comment_text IS NOT NULL
                ORDER BY a.display_order ASC, a.id ASC, v.voted_at DESC
            ")->fetchAll();

            // Group by amendment
            $grouped = [];
            foreach ($rows as $r) {
                $aid = (int)$r['amendment_id'];
                if (!isset($grouped[$aid])) {
                    $grouped[$aid] = [
                        'amendment_id' => $aid,
                        'proposal_no'  => $r['proposal_no'],
                        'summary'      => $r['summary'],
                        'comments'     => [],
                    ];
                }
                $grouped[$aid]['comments'][] = [
                    'comment_text' => $r['comment_text'],
                    'voted_at'     => $r['voted_at'],
                    'full_name'    => $r['full_name'],
                    'local_church' => $r['local_church'],
                    'area'         => $r['area'],
                ];
            }

            json_out(['grouped' => array_values($grouped)]);
        }

        // Default GET — return all amendments + settings + stats
        json_out([
            'amendments' => all_amendments(),
            'settings'   => get_settings(),
            'stats'      => stats_snapshot(),
        ]);
        break;

    // ──────────────────────────────────────────────────────────────────────────
    case 'POST':
        $b      = read_json_body();
        $action = $b['action'] ?? 'create';

        // ── create ──────────────────────────────────────────────────────────
        if ($action === 'create') {
            $required = ['proposal_no', 'article_no', 'section', 'page_no', 'proposed_amendment'];
            foreach ($required as $f) {
                if (trim($b[$f] ?? '') === '') {
                    json_out(['error' => "Please fill in: {$f}"], 422);
                }
            }
            $maxOrder = (int)$pdo->query('SELECT COALESCE(MAX(display_order),0) m FROM amendments')->fetch()['m'];
            $stmt = $pdo->prepare('
                INSERT INTO amendments
                  (proposal_no, article_no, section, page_no, proposed_amendment,
                   rationale, original_text, summary, display_order, is_active)
                VALUES (?,?,?,?,?,?,?,?,?,1)
            ');
            $stmt->execute([
                trim($b['proposal_no']),
                trim($b['article_no']),
                trim($b['section']),
                trim($b['page_no']),
                trim($b['proposed_amendment']),
                trim($b['rationale']    ?? '') ?: null,
                trim($b['original_text'] ?? '') ?: null,
                trim($b['summary']      ?? ''),
                $maxOrder + 1,
            ]);
            json_out(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
        }

        // ── update ──────────────────────────────────────────────────────────
        if ($action === 'update') {
            $id = (int)($b['id'] ?? 0);
            if ($id <= 0) json_out(['error' => 'Missing amendment id'], 422);
            $stmt = $pdo->prepare('
                UPDATE amendments SET
                  proposal_no        = ?,
                  article_no         = ?,
                  section            = ?,
                  page_no            = ?,
                  proposed_amendment = ?,
                  rationale          = ?,
                  original_text      = ?,
                  summary            = ?
                WHERE id = ?
            ');
            $stmt->execute([
                trim($b['proposal_no']        ?? ''),
                trim($b['article_no']         ?? ''),
                trim($b['section']            ?? ''),
                trim($b['page_no']            ?? ''),
                trim($b['proposed_amendment'] ?? ''),
                trim($b['rationale']    ?? '') ?: null,
                trim($b['original_text'] ?? '') ?: null,
                trim($b['summary']      ?? ''),
                $id,
            ]);
            json_out(['success' => true]);
        }

        // ── toggle_active ────────────────────────────────────────────────────
        if ($action === 'toggle_active') {
            $id = (int)($b['id'] ?? 0);
            $stmt = $pdo->prepare('UPDATE amendments SET is_active = 1 - is_active WHERE id = ?');
            $stmt->execute([$id]);
            json_out(['success' => true]);
        }

        // ── reorder ──────────────────────────────────────────────────────────
        if ($action === 'reorder') {
            $order = $b['order'] ?? [];
            $stmt  = $pdo->prepare('UPDATE amendments SET display_order = ? WHERE id = ?');
            foreach ($order as $i => $id) {
                $stmt->execute([$i + 1, (int)$id]);
            }
            json_out(['success' => true]);
        }

        // ── delete ───────────────────────────────────────────────────────────
        if ($action === 'delete') {
            $id   = (int)($b['id'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM amendments WHERE id = ?');
            $stmt->execute([$id]);
            json_out(['success' => true]);
        }

        // ── update_settings ──────────────────────────────────────────────────
        if ($action === 'update_settings') {
            $name          = trim($b['convention_name'] ?? '');
            $votingOpen    = !empty($b['voting_open'])    ? 1 : 0;
            $resultsPublic = !empty($b['results_public']) ? 1 : 0;
            $stmt = $pdo->prepare(
                'UPDATE settings SET convention_name = ?, voting_open = ?, results_public = ? WHERE id = 1'
            );
            $stmt->execute([
                $name !== '' ? $name : 'Quadrennial Convention',
                $votingOpen,
                $resultsPublic,
            ]);
            json_out(['success' => true]);
        }

        json_out(['error' => 'Unknown action'], 400);
        break;

    // ──────────────────────────────────────────────────────────────────────────
    default:
        json_out(['error' => 'Method not allowed'], 405);
}
