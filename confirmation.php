<?php require_once __DIR__ . '/includes/helpers.php';
$settings = get_settings();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ballot Submitted · <?= htmlspecialchars($settings['convention_name']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
<!-- Local fallbacks -->
<link href="assets/vendor/css/fraunces.css" rel="stylesheet">
<link href="assets/vendor/css/inter.css" rel="stylesheet">
<link href="assets/vendor/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="ypd-topbar">
  <div class="ypd-shell d-flex justify-content-between align-items-center py-0">
    <div class="brand">19th Episcopal District YPD<small>Amendment Voting · <?= htmlspecialchars($settings['convention_name']) ?></small></div>
    <a href="results.php" class="btn btn-sm btn-outline-light">View Results</a>
  </div>
</div>

<div class="ypd-shell" style="max-width:560px;">
  <div class="card-panel p-5 text-center">
    <div class="confirm-seal">
      <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
    </div>
    <h1 class="display h3">Ballot submitted.</h1>
    <p class="text-muted mt-2 mb-4">
      Thank you for casting your vote on the proposed amendments. Your ballot has been recorded and cannot be changed.
    </p>
    <a href="results.php" class="btn btn-forest">View Live Results</a>
  </div>
  <p class="text-center text-muted mt-4" style="font-size:.82rem;">
    Designed &amp; built for the 19th Episcopal District YPD by Olebogeng Leketi, Historiographer/Statistician.
  </p>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<!-- Local fallback -->
<script src="assets/vendor/js/bootstrap.bundle.min.js"></script>
</body>
</html>
