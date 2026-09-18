<?php require_once __DIR__ . '/includes/helpers.php';
$settings = get_settings();
$votingOpen = is_voting_open();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Delegate Check-In · <?= htmlspecialchars($settings['convention_name']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
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
  <div class="checkin-hero">
    <div class="eyebrow mb-2">Delegate Check-In</div>
    <h1 class="display">Welcome, Delegate.</h1>
    <p>Enter your details below to check in and receive your ballot for the proposed amendments to the Quadrennial.</p>
  </div>

  <?php if (!$votingOpen): ?>
    <div class="card-panel p-4 text-center">
      <strong>Check-in is currently closed.</strong>
      <p class="mb-0 mt-2 text-muted">Please see a convention official for assistance.</p>
    </div>
  <?php else: ?>
  <div class="card-panel p-4 p-md-5">
    <form id="checkin-form" novalidate>
      <div class="mb-3">
        <label class="form-label" for="full_name">Full Name</label>
        <input type="text" class="form-control" id="full_name" name="full_name" placeholder="e.g. Olebogeng Leketi" required>
        <div class="invalid-feedback" id="err-full_name"></div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="local_church">Local Church</label>
        <input type="text" class="form-control" id="local_church" name="local_church" placeholder="e.g. Lake Bethesda AME Church" required>
        <div class="invalid-feedback" id="err-local_church"></div>
      </div>
      <div class="mb-4">
        <label class="form-label" for="area">Area</label>
        <input type="text" class="form-control" id="area" name="area" placeholder="e.g. Area 10" required>
        <div class="invalid-feedback" id="err-area"></div>
      </div>

      <div id="checkin-alert" class="alert alert-danger d-none" role="alert"></div>

      <button type="submit" class="btn btn-forest btn-lg w-100" id="checkin-submit">
        <span class="btn-text">Check In &amp; Begin Voting</span>
        <span class="spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
      </button>
    </form>
  </div>
  <?php endif; ?>

  <p class="text-center text-muted mt-4" style="font-size:.82rem;">
    Designed &amp; built for the 19th Episcopal District YPD by Olebogeng Leketi, Historiographer/Statistician.
  </p>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<!-- Local fallback -->
<script src="assets/vendor/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/checkin.js"></script>
</body>
</html>
