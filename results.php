<?php require_once __DIR__ . '/includes/helpers.php';
$settings = get_settings();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Live Results · <?= htmlspecialchars($settings['convention_name']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<!-- Local fallbacks -->
<link href="assets/vendor/css/fraunces.css" rel="stylesheet">
<link href="assets/vendor/css/inter.css" rel="stylesheet">
<link href="assets/vendor/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="ypd-topbar">
  <div class="ypd-shell wide d-flex justify-content-between align-items-center py-0">
    <div class="brand">19th Episcopal District YPD<small>Live Amendment Results · <?= htmlspecialchars($settings['convention_name']) ?></small></div>
    <a href="index.php" class="btn btn-sm btn-outline-light">Delegate Check-In</a>
  </div>
</div>

<div class="ypd-shell wide">

  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
      <span class="live-dot"></span><span class="eyebrow">Live</span>
      <span class="text-muted ms-2" style="font-size:.85rem;" id="last-updated"></span>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
      <div class="stat-card">
        <div class="stat-value" id="stat-checked-in">—</div>
        <div class="stat-label">Members Checked In</div>
      </div>
    </div>
    <div class="col-6 col-md-4">
      <div class="stat-card">
        <div class="stat-value" id="stat-submitted">—</div>
        <div class="stat-label">Ballots Submitted</div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="stat-card">
        <div class="stat-value" id="stat-turnout">—</div>
        <div class="stat-label">Turnout Rate</div>
      </div>
    </div>
  </div>

  <div id="results-error" class="results-error d-none">
    <p class="mb-0">Unable to load results. <span id="error-detail"></span></p>
  </div>

  <div id="summary-charts" class="row g-3 mb-4">
    <div class="col-12 col-md-4">
      <div class="card-panel p-3 h-100 text-center">
        <div class="eyebrow mb-2">Voter Participation</div>
        <div style="position: relative; width: 240px; height: 240px; margin: 0 auto;">
          <canvas id="chart-participation" width="240" height="240"></canvas>
        </div>
        <div id="participation-legend" class="mt-2" style="font-size:.82rem;"></div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card-panel p-3 h-100 text-center">
        <div class="eyebrow mb-2">Overall Vote Distribution</div>
        <div style="position: relative; width: 240px; height: 240px; margin: 0 auto;">
          <canvas id="chart-vote-dist" width="240" height="240"></canvas>
        </div>
        <div id="vote-dist-legend" class="mt-2" style="font-size:.82rem;"></div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card-panel p-3 h-100">
        <div class="eyebrow mb-2 text-start">Turnout Progress</div>
        <div style="position: relative; width: 240px; height: 160px; margin: 0 auto;">
          <canvas id="chart-turnout-gauge" width="240" height="160"></canvas>
        </div>
        <div id="turnout-gauge-label" class="text-center mt-2" style="font-size:.82rem;"></div>
      </div>
    </div>
  </div>

  <div class="card-panel p-3 p-md-4 mb-4">
    <div class="eyebrow mb-3">Amendments Comparison</div>
    <div style="position: relative; width: 100%; height: 300px;">
      <canvas id="chart-comparison"></canvas>
    </div>
  </div>

  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <h2 class="display h5 mb-0">Results by Article</h2>
    <div id="article-filters" class="d-flex gap-2 flex-wrap"></div>
  </div>

  <div id="results-grid" class="row g-3"></div>
  <div id="empty-state" class="card-panel p-5 text-center d-none">
    <p class="mb-0 text-muted">No amendments are open for voting yet.</p>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js" integrity="sha384-bs/nf9FbdNouRbMiFcrcZfLXYPKiPaGVGplVbv7dLGECccEXDW+S3zjqSKR5ZEaD" crossorigin="anonymous"></script>
<!-- Local fallbacks -->
<script src="assets/vendor/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/js/chart.umd.min.js"></script>
<script src="assets/js/results.js"></script>
</body>
</html>
