<?php
require_once __DIR__ . '/../includes/helpers.php';

$settings = get_settings();
if (!(bool)($settings['results_public'] ?? 1)) {
    json_out(['error' => 'Results are not currently public.'], 403);
}

json_out([
    'stats' => stats_snapshot(),
    'amendments' => results_by_amendment(),
    'convention_name' => $settings['convention_name'],
    'generated_at' => date('c'),
]);
