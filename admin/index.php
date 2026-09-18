<?php require_once __DIR__ . '/../includes/helpers.php';
$settings = get_settings();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin · <?= htmlspecialchars($settings['convention_name']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
<!-- Local fallbacks -->
<link href="../assets/vendor/css/fraunces.css" rel="stylesheet">
<link href="../assets/vendor/css/inter.css" rel="stylesheet">
<link href="../assets/vendor/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="ypd-topbar admin-topbar">
  <div class="ypd-shell wide d-flex justify-content-between align-items-center py-0">
    <div class="brand">19th Episcopal District YPD<small>Admin · Manage Amendments</small></div>
    <div class="d-flex gap-2">
      <a href="../results.php" class="btn btn-sm btn-outline-light">Results</a>
      <a href="../index.php" class="btn btn-sm btn-outline-light">Check-In</a>
    </div>
  </div>
</div>

<div class="ypd-shell wide">

  <!-- ── Stats row ── -->
  <div class="row g-3 mb-4" id="admin-stats">
    <div class="col-6 col-md-3">
      <div class="stat-card"><div class="stat-value" id="a-stat-checked-in">—</div><div class="stat-label">Checked In</div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card"><div class="stat-value" id="a-stat-submitted">—</div><div class="stat-label">Ballots Submitted</div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card"><div class="stat-value" id="a-stat-turnout">—</div><div class="stat-label">Turnout Rate</div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card"><div class="stat-value" id="a-stat-active">—</div><div class="stat-label">Active Amendments</div></div>
    </div>
  </div>

  <!-- ── Convention Settings ── -->
  <div class="card-panel p-3 p-md-4 mb-4">
    <h2 class="display h6 mb-3">Convention Settings</h2>
    <form id="settings-form" class="row g-3 align-items-end">
      <div class="col-12 col-md-5">
        <label class="form-label">Convention Name</label>
        <input type="text" class="form-control" id="s-convention-name">
      </div>
      <div class="col-6 col-md-3">
        <div class="form-check form-switch pt-4">
          <input class="form-check-input" type="checkbox" id="s-voting-open">
          <label class="form-check-label fw-semibold" for="s-voting-open">Voting Open</label>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="form-check form-switch pt-4">
          <input class="form-check-input" type="checkbox" id="s-results-public">
          <label class="form-check-label fw-semibold" for="s-results-public">Results Public</label>
        </div>
      </div>
      <div class="col-12 col-md-1">
        <button type="submit" class="btn btn-forest w-100">Save</button>
      </div>
    </form>
    <div id="settings-alert" class="alert alert-success d-none mt-3 mb-0 py-2"></div>
  </div>

  <!-- ── Amendments table ── -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="display h6 mb-0">Amendments</h2>
    <button class="btn btn-forest" id="btn-new-amendment" data-bs-toggle="modal" data-bs-target="#amendmentModal">
      + New Amendment
    </button>
  </div>

  <div class="card-panel p-0 mb-2">
    <div class="table-responsive">
      <table class="table table-amendments mb-0 align-middle">
        <thead>
          <tr>
            <th style="width:40px;"></th>
            <th>Proposal No.</th>
            <th>Article</th>
            <th>Section</th>
            <th>Page</th>
            <th>Summary</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody id="amendments-tbody">
          <tr><td colspan="8" class="text-center text-muted py-4">Loading…</td></tr>
        </tbody>
      </table>
    </div>
  </div>
  <p class="text-muted mb-5" style="font-size:.8rem;">
    Drag rows using the ⠿ handle to reorder how amendments appear on the ballot and results page.
  </p>

  <!-- ── Comments Received ── -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="display h6 mb-0">💬 Comments Received from Delegates</h2>
    <button class="btn btn-sm btn-outline-forest" id="btn-refresh-comments">↺ Refresh</button>
  </div>
  <div id="comments-section">
    <div class="text-muted py-3 text-center" style="font-size:.9rem;">Loading comments…</div>
  </div>

</div><!-- /.ypd-shell -->

<!-- ── Amendment Modal ── -->
<div class="modal fade" id="amendmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="amendmentModalTitle">New Amendment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="amendment-form" novalidate>
          <input type="hidden" id="f-id">

          <!-- ── Core identifiers ── -->
          <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
              <label class="form-label">Proposal No. <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="f-proposal-no" required>
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label">Article No. <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="f-article-no" required>
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label">Section <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="f-section" required>
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label">Page No. <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="f-page-no" required>
            </div>
            <div class="col-12">
              <label class="form-label">Short Summary <span class="text-muted fw-normal">(shown on ballot &amp; results)</span></label>
              <input type="text" class="form-control" id="f-summary" maxlength="255">
            </div>
          </div>

          <hr class="my-3">

          <!-- ── Amendment content ── -->
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Proposed Amendment <span class="text-danger">*</span></label>
              <textarea class="form-control" id="f-proposed-amendment" rows="4" required
                placeholder="The full text of the proposed amendment…"></textarea>
            </div>

            <div class="col-12">
              <label class="form-label">
                📜 Original Constitution Text
                <span class="text-muted fw-normal" style="font-size:.8rem;">(optional — shown as collapsible panel on ballot)</span>
              </label>
              <textarea class="form-control" id="f-original-text" rows="3"
                placeholder="The existing text this amendment proposes to change…"></textarea>
            </div>

            <div class="col-12">
              <label class="form-label">
                💡 Rationale
                <span class="text-muted fw-normal" style="font-size:.8rem;">(optional — shown as collapsible panel on ballot)</span>
              </label>
              <textarea class="form-control" id="f-rationale" rows="3"
                placeholder="Why is this amendment being proposed? What problem does it solve?"></textarea>
            </div>
          </div>

          <div id="amendment-form-alert" class="alert alert-danger d-none mt-3 py-2"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-forest" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-forest" id="btn-save-amendment">Save Amendment</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<!-- Local fallback -->
<script src="../assets/vendor/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/admin.js"></script>
</body>
</html>
