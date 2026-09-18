document.addEventListener('DOMContentLoaded', function () {
  const token = sessionStorage.getItem('ypd_token');
  const name  = sessionStorage.getItem('ypd_name');
  const wrap  = document.getElementById('ballot-wrap');
  const notice = document.getElementById('no-token-notice');
  const nameEl = document.getElementById('voter-name');

  if (!token) {
    if (notice) notice.classList.remove('d-none');
    if (wrap)   wrap.classList.add('d-none');
    return;
  }
  if (nameEl && name) nameEl.textContent = name;

  // ── Pill tab switcher (Original Constitution / Rationale) ──────────────────
  document.querySelectorAll('.ctx-tabs').forEach(tabGroup => {
    const tabs   = Array.from(tabGroup.querySelectorAll('.ctx-tab'));
    const panels = Array.from(tabGroup.querySelectorAll('.ctx-panel'));

    function closeAll() {
      tabs.forEach(t => {
        t.setAttribute('aria-selected', 'false');
        t.classList.remove('ctx-tab--active');
      });
      panels.forEach(p => {
        p.classList.remove('ctx-panel--open');
        p.addEventListener('transitionend', function h() {
          if (!p.classList.contains('ctx-panel--open')) p.classList.add('d-none');
          p.removeEventListener('transitionend', h);
        });
      });
    }

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        const targetId = tab.getAttribute('data-tab');
        const panel    = tabGroup.querySelector('#ctx-body-' + targetId);
        const isActive = tab.getAttribute('aria-selected') === 'true';

        if (isActive) {
          // Toggle off — collapse
          closeAll();
        } else {
          // Switch to this tab
          closeAll();
          tab.setAttribute('aria-selected', 'true');
          tab.classList.add('ctx-tab--active');
          panel.classList.remove('d-none');
          requestAnimationFrame(() => panel.classList.add('ctx-panel--open'));
        }
      });
    });
  });

  const cards = Array.from(document.querySelectorAll('.ledger-card'));
  const votes    = {};  // amendmentId -> choice ('yes'|'no'|'abstain'|'comment')
  const comments = {};  // amendmentId -> comment text (only when choice === 'comment')
  const total    = cards.length;

  const progressFill  = document.getElementById('progress-fill');
  const progressLabel = document.getElementById('progress-label');
  const progressPct   = document.getElementById('progress-pct');

  function updateProgress() {
    const answered = Object.keys(votes).length;
    const pct = total > 0 ? Math.round((answered / total) * 100) : 0;
    progressFill.style.width = pct + '%';
    progressLabel.textContent = `${answered} of ${total} answered`;
    progressPct.textContent   = pct + '%';
  }

  cards.forEach(card => {
    const id           = card.getAttribute('data-amendment-id');
    const btns         = card.querySelectorAll('.vote-btn');
    const commentPanel = card.querySelector('.comment-panel');
    const textarea     = card.querySelector('.comment-textarea');
    const charSpan     = card.querySelector('.comment-chars');

    // Character counter for the textarea
    if (textarea && charSpan) {
      textarea.addEventListener('input', () => {
        const len = textarea.value.length;
        charSpan.textContent = len;
        // Live-save the comment text
        if (votes[id] === 'comment') {
          comments[id] = textarea.value.trim();
        }
        // Visual cue when nearing limit
        charSpan.closest('.comment-char-count')
          .classList.toggle('near-limit', len >= 1800);
      });
    }

    function showCommentPanel(show) {
      if (!commentPanel) return;
      if (show) {
        // Remove d-none first so the element is in the DOM, then on the next
        // animation frame add --open so the CSS transition actually fires.
        commentPanel.classList.remove('d-none');
        requestAnimationFrame(() => {
          commentPanel.classList.add('comment-panel--open');
          if (textarea) textarea.focus();
        });
      } else {
        commentPanel.classList.remove('comment-panel--open');
        // Listen specifically on max-height so we don't fire early on
        // opacity or margin-top finishing first.
        const onEnd = (e) => {
          if (e.propertyName !== 'max-height') return;
          commentPanel.classList.add('d-none');
          commentPanel.removeEventListener('transitionend', onEnd);
        };
        commentPanel.addEventListener('transitionend', onEnd);
        // Fallback: force-hide after transition duration if event never fires
        // (e.g. panel was already effectively closed, transition skipped).
        setTimeout(() => {
          if (!commentPanel.classList.contains('d-none')) {
            commentPanel.classList.add('d-none');
            commentPanel.removeEventListener('transitionend', onEnd);
          }
        }, 400);
        // Clear textarea and reset char counter / near-limit state
        if (textarea) {
          textarea.value = '';
          if (charSpan) charSpan.textContent = '0';
          charSpan?.closest('.comment-char-count')?.classList.remove('near-limit');
        }
        delete comments[id];
      }
    }

    function select(choice) {
      votes[id] = choice;
      btns.forEach(b =>
        b.classList.toggle('active', b.getAttribute('data-choice') === choice)
      );
      card.classList.add('is-answered');
      showCommentPanel(choice === 'comment');
      updateProgress();
    }

    btns.forEach(btn => {
      btn.addEventListener('click', () => select(btn.getAttribute('data-choice')));
      btn.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          select(btn.getAttribute('data-choice'));
        }
      });
    });
  });

  // ── Form submission ──────────────────────────────────────────────────────────
  const form      = document.getElementById('ballot-form');
  const alertBox  = document.getElementById('ballot-alert');
  const submitBtn = document.getElementById('submit-ballot');
  const spinner   = submitBtn.querySelector('.spinner-border');
  const btnText   = submitBtn.querySelector('.btn-text');

  function setLoading(loading) {
    submitBtn.disabled = loading;
    spinner.classList.toggle('d-none', !loading);
    btnText.textContent = loading ? 'Submitting…' : 'Submit Ballot';
  }

  function showError(msg, scrollTarget) {
    alertBox.textContent = msg;
    alertBox.classList.remove('d-none');
    if (scrollTarget) {
      scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    alertBox.classList.add('d-none');

    // 1. All amendments must have a vote
    if (Object.keys(votes).length < total) {
      const firstUnanswered = cards.find(
        c => !votes[c.getAttribute('data-amendment-id')]
      );
      showError(
        'Please cast a vote on every amendment before submitting.',
        firstUnanswered
      );
      return;
    }

    // 2. Any card marked "comment" must have non-empty text
    for (const card of cards) {
      const id = card.getAttribute('data-amendment-id');
      if (votes[id] === 'comment') {
        const textarea = card.querySelector('.comment-textarea');
        const text     = textarea ? textarea.value.trim() : '';
        if (!text) {
          showError(
            'You selected "Comment / Feedback" on one or more amendments — ' +
            'please enter your comment before submitting.',
            card
          );
          textarea && textarea.focus();
          return;
        }
        comments[id] = text;
      }
    }

    // 3. Build the payload
    // votes payload: { amendmentId: { choice, comment_text? } }
    const votesPayload = {};
    for (const [id, choice] of Object.entries(votes)) {
      votesPayload[id] = { choice };
      if (choice === 'comment' && comments[id]) {
        votesPayload[id].comment_text = comments[id];
      }
    }

    setLoading(true);
    try {
      const res  = await fetch('api/submit_ballot.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token, votes: votesPayload }),
      });
      const data = await res.json();

      if (!res.ok) {
        showError(data.error || 'Something went wrong submitting your ballot.');
        setLoading(false);
        return;
      }

      sessionStorage.removeItem('ypd_token');
      window.location.href = 'confirmation.php';
    } catch (err) {
      showError('We could not reach the server. Please try again.');
      setLoading(false);
    }
  });

  updateProgress();
});
