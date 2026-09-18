<?php require_once __DIR__ . '/includes/helpers.php';
$settings = get_settings();
$amendments = active_amendments();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ballot · <?= htmlspecialchars($settings['convention_name']) ?></title>
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
    <div class="text-white" style="font-size:.85rem;" id="voter-name"></div>
  </div>
</div>

<div class="ypd-shell">
  <div id="no-token-notice" class="alert alert-warning d-none">
    We couldn't find your check-in. <a href="index.php">Please check in first</a>.
  </div>

  <?php if (empty($amendments)): ?>
    <div class="card-panel p-5 text-center">
      <h2 class="display h4">No amendments are open for voting yet.</h2>
      <p class="text-muted mb-0">Please check back once the convention floor opens balloting.</p>
    </div>
  <?php else: ?>

  <div id="ballot-wrap">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <span class="progress-label" id="progress-label">0 of <?= count($amendments) ?> answered</span>
      <span class="progress-label" id="progress-pct">0%</span>
    </div>
    <div class="ballot-progress-track mb-4">
      <div class="ballot-progress-fill" id="progress-fill" style="width:0%"></div>
    </div>

    <form id="ballot-form">
      <?php foreach ($amendments as $a): ?>
      <div class="ledger-card" data-amendment-id="<?= (int)$a['id'] ?>">

        <?php if (!empty($a['summary'])): ?>
          <span class="summary-tag"><?= htmlspecialchars($a['summary']) ?></span>
        <?php endif; ?>

        <div class="proposal-no">Proposal No. <?= htmlspecialchars($a['proposal_no']) ?></div>
        <div class="citation-line">
          <span><?= htmlspecialchars($a['article_no']) ?></span>
          <span><?= htmlspecialchars($a['section']) ?></span>
          <span>Page <?= htmlspecialchars($a['page_no']) ?></span>
        </div>

        <div class="amendment-text"><?= nl2br(htmlspecialchars($a['proposed_amendment'])) ?></div>

        <?php
          $hasRationale    = !empty(trim($a['rationale'] ?? ''));
          $hasOriginalText = !empty(trim($a['original_text'] ?? ''));
        ?>

        <?php if ($hasRationale || $hasOriginalText): ?>
        <div class="ctx-tabs mt-3" data-amendment-id="<?= (int)$a['id'] ?>">

          <div class="ctx-tab-bar" role="tablist">
            <?php if ($hasOriginalText): ?>
            <button class="ctx-tab" role="tab" data-tab="orig-<?= (int)$a['id'] ?>"
                    aria-selected="false" aria-controls="ctx-body-orig-<?= (int)$a['id'] ?>">
              Original Constitution
            </button>
            <?php endif; ?>
            <?php if ($hasRationale): ?>
            <button class="ctx-tab" role="tab" data-tab="rationale-<?= (int)$a['id'] ?>"
                    aria-selected="false" aria-controls="ctx-body-rationale-<?= (int)$a['id'] ?>">
              Rationale
            </button>
            <?php endif; ?>
          </div>

          <?php if ($hasOriginalText): ?>
          <div class="ctx-panel d-none" id="ctx-body-orig-<?= (int)$a['id'] ?>" role="tabpanel">
            <div class="ctx-panel-inner">
              <div class="ctx-panel-label">Original Constitution</div>
              <?= nl2br(htmlspecialchars($a['original_text'])) ?>
            </div>
          </div>
          <?php endif; ?>
          <?php if ($hasRationale): ?>
          <div class="ctx-panel d-none" id="ctx-body-rationale-<?= (int)$a['id'] ?>" role="tabpanel">
            <div class="ctx-panel-inner">
              <div class="ctx-panel-label">Rationale</div>
              <?= nl2br(htmlspecialchars($a['rationale'])) ?>
            </div>
          </div>
          <?php endif; ?>

        </div>
        <?php endif; ?>

        <div class="vote-group" role="group" aria-label="Vote on Proposal <?= htmlspecialchars($a['proposal_no']) ?>">
          <div class="vote-btn" data-choice="yes" tabindex="0" role="button">Yes</div>
          <div class="vote-btn" data-choice="no" tabindex="0" role="button">No</div>
          <div class="vote-btn" data-choice="abstain" tabindex="0" role="button">Abstain</div>
          <div class="vote-btn vote-btn--comment" data-choice="comment" tabindex="0" role="button">
             Comment
          </div>
        </div>

        <!-- Comment / Feedback panel — shown only when "Comment" is selected -->
        <div class="comment-panel d-none" aria-live="polite">
          <div class="comment-warning" role="alert">
            <span class="comment-warning-icon">️</span>
            <div>
              <strong>Please be intentional.</strong>
              Use this field for genuine amendments, corrections, additions, or constructive feedback only.
              Frivolous or vague entries will not be considered by the committee.
            </div>
          </div>
          <label class="form-label mt-3" for="comment-<?= (int)$a['id'] ?>">
            Your Comment / Feedback
            <span class="text-muted fw-normal" style="font-size:.8rem;">(required)</span>
          </label>
          <textarea
            id="comment-<?= (int)$a['id'] ?>"
            class="form-control comment-textarea"
            rows="4"
            maxlength="2000"
            placeholder="Describe your proposed adjustment, correction, addition, or feedback…"
          ></textarea>
          <div class="comment-char-count"><span class="comment-chars">0</span> / 2000 characters</div>
        </div>

      </div><!-- /.ledger-card -->
      <?php endforeach; ?>

      <div id="ballot-alert" class="alert alert-danger d-none" role="alert"></div>

      <button type="submit" class="btn btn-forest btn-lg w-100 mt-2" id="submit-ballot">
        <span class="btn-text">Submit Ballot</span>
        <span class="spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
      </button>
      <p class="text-center text-muted mt-3" style="font-size:.82rem;">
        You must vote on every amendment before submitting. Your ballot cannot be changed once submitted.
      </p>
    </form>
  </div>
  <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<!-- Local fallback -->
<script src="assets/vendor/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/ballot.js"></script>
</body>
</html>
