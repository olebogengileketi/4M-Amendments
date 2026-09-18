document.addEventListener('DOMContentLoaded', function () {
  const REFRESH_MS = 8000;
  const grid = document.getElementById('results-grid');
  const emptyState = document.getElementById('empty-state');
  const filtersEl = document.getElementById('article-filters');
  const lastUpdated = document.getElementById('last-updated');

  const charts = {};      // amendmentId -> Chart instance
  let summaryCharts = {}; // participation, voteDist, turnoutGauge, comparison
  let activeArticle = 'all';
  let knownArticles = [];

  const FOREST = '#16412F';
  const SLATE = '#3A4A44';
  const LIGHT = '#D8E4DD';
  const FOREST_MID = '#2D7A54';
  const FOREST_LIGHT = '#E7F2EB';

  function fmtTime(iso) {
    try {
      const d = new Date(iso);
      return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    } catch (e) { return ''; }
  }

  function renderStats(stats) {
    document.getElementById('stat-checked-in').textContent = stats.checked_in;
    document.getElementById('stat-submitted').textContent = stats.ballots_submitted;
    document.getElementById('stat-turnout').textContent = stats.turnout_rate + '%';
  }

  function doughnutCenterTextPlugin(color) {
    return {
      id: 'centerText',
      afterDraw(chart) {
        const { ctx, chartArea: { left, right, top, bottom } } = chart;
        const cx = (left + right) / 2;
        const cy = (top + bottom) / 2;
        const meta = chart.getDatasetMeta(0);
        const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.font = '600 1.6rem Fraunces, Georgia, serif';
        ctx.fillStyle = color || FOREST;
        ctx.fillText(total, cx, cy - 8);
        ctx.font = '600 .65rem Inter, sans-serif';
        ctx.fillStyle = SLATE;
        ctx.fillText('total', cx, cy + 14);
        ctx.restore();
      },
    };
  }

  function initSummaryCharts(stats, amendments) {
    const totalYes = amendments.reduce((s, a) => s + a.tally.yes, 0);
    const totalNo = amendments.reduce((s, a) => s + a.tally.no, 0);
    const totalAbstain = amendments.reduce((s, a) => s + a.tally.abstain, 0);
    const notSubmitted = Math.max(0, stats.checked_in - stats.ballots_submitted);

    summaryCharts.participation = new Chart(
      document.getElementById('chart-participation').getContext('2d'),
      {
        type: 'doughnut',
        data: {
          labels: ['Voted', 'Haven\'t Voted'],
          datasets: [{
            data: [stats.ballots_submitted, notSubmitted],
            backgroundColor: [FOREST_MID, LIGHT],
            borderWidth: 0,
            cutout: '68%',
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          animation: { duration: 300 },
          plugins: {
            legend: { display: false },
            tooltip: { enabled: true },
          },
        },
        plugins: [doughnutCenterTextPlugin(FOREST)],
      },
    );

    summaryCharts.voteDist = new Chart(
      document.getElementById('chart-vote-dist').getContext('2d'),
      {
        type: 'doughnut',
        data: {
          labels: ['Yes', 'No', 'Abstain'],
          datasets: [{
            data: [totalYes, totalNo, totalAbstain],
            backgroundColor: [FOREST, SLATE, LIGHT],
            borderWidth: 0,
            cutout: '68%',
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          animation: { duration: 300 },
          plugins: {
            legend: { display: false },
            tooltip: { enabled: true },
          },
        },
        plugins: [doughnutCenterTextPlugin(FOREST)],
      },
    );

    summaryCharts.turnoutGauge = new Chart(
      document.getElementById('chart-turnout-gauge').getContext('2d'),
      {
        type: 'doughnut',
        data: {
          labels: ['Turnout', 'Remaining'],
          datasets: [{
            data: [stats.turnout_rate, Math.max(0, 100 - stats.turnout_rate)],
            backgroundColor: [FOREST_MID, FOREST_LIGHT],
            borderWidth: 0,
            cutout: '78%',
            circumference: 180,
            rotation: 270,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          animation: { duration: 300 },
          plugins: {
            legend: { display: false },
            tooltip: { enabled: false },
          },
        },
        plugins: [{
          id: 'gaugeText',
          afterDraw(chart) {
            const { ctx, chartArea: { left, right, bottom } } = chart;
            const cx = (left + right) / 2;
            const cy = bottom - 4;
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'bottom';
            ctx.font = '600 1.8rem Fraunces, Georgia, serif';
            ctx.fillStyle = FOREST;
            ctx.fillText(stats.turnout_rate + '%', cx, cy);
            ctx.restore();
          },
        }],
      },
    );

    const labels = amendments.map(a => 'Prop. ' + a.proposal_no);
    summaryCharts.comparison = new Chart(
      document.getElementById('chart-comparison').getContext('2d'),
      {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Yes',
              data: amendments.map(a => a.tally.yes),
              backgroundColor: FOREST,
              borderRadius: 4,
            },
            {
              label: 'No',
              data: amendments.map(a => a.tally.no),
              backgroundColor: SLATE,
              borderRadius: 4,
            },
            {
              label: 'Abstain',
              data: amendments.map(a => a.tally.abstain),
              backgroundColor: LIGHT,
              borderRadius: 4,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          animation: { duration: 300 },
          plugins: {
            legend: {
              position: 'top',
              labels: { usePointStyle: true, pointStyle: 'rectRounded', padding: 16, font: { size: 12, weight: '600' } },
            },
            tooltip: { mode: 'index', intersect: false },
          },
          scales: {
            x: { stacked: true, grid: { display: false } },
            y: { stacked: true, beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#EFF4F1' } },
          },
        },
      },
    );

    updateSummaryLegends(stats, amendments);
  }

  function updateSummaryCharts(stats, amendments) {
    const totalYes = amendments.reduce((s, a) => s + a.tally.yes, 0);
    const totalNo = amendments.reduce((s, a) => s + a.tally.no, 0);
    const totalAbstain = amendments.reduce((s, a) => s + a.tally.abstain, 0);
    const notSubmitted = Math.max(0, stats.checked_in - stats.ballots_submitted);

    if (summaryCharts.participation) {
      summaryCharts.participation.data.datasets[0].data = [stats.ballots_submitted, notSubmitted];
      summaryCharts.participation.update();
    }
    if (summaryCharts.voteDist) {
      summaryCharts.voteDist.data.datasets[0].data = [totalYes, totalNo, totalAbstain];
      summaryCharts.voteDist.update();
    }
    if (summaryCharts.turnoutGauge) {
      summaryCharts.turnoutGauge.data.datasets[0].data = [stats.turnout_rate, Math.max(0, 100 - stats.turnout_rate)];
      summaryCharts.turnoutGauge.update();
    }
    if (summaryCharts.comparison) {
      const labels = amendments.map(a => 'Prop. ' + a.proposal_no);
      summaryCharts.comparison.data.labels = labels;
      summaryCharts.comparison.data.datasets[0].data = amendments.map(a => a.tally.yes);
      summaryCharts.comparison.data.datasets[1].data = amendments.map(a => a.tally.no);
      summaryCharts.comparison.data.datasets[2].data = amendments.map(a => a.tally.abstain);
      summaryCharts.comparison.update();
    }

    updateSummaryLegends(stats, amendments);
  }

  function updateSummaryLegends(stats, amendments) {
    const totalYes = amendments.reduce((s, a) => s + a.tally.yes, 0);
    const totalNo = amendments.reduce((s, a) => s + a.tally.no, 0);
    const totalAbstain = amendments.reduce((s, a) => s + a.tally.abstain, 0);
    const totalVotes = totalYes + totalNo + totalAbstain;
    const notSubmitted = Math.max(0, stats.checked_in - stats.ballots_submitted);

    const pPct = stats.checked_in > 0 ? Math.round((stats.ballots_submitted / stats.checked_in) * 100) : 0;
    document.getElementById('participation-legend').innerHTML =
      `<span style="color:${FOREST_MID}; font-weight:700;">${stats.ballots_submitted} voted</span> &middot; ` +
      `<span style="color:${SLATE};">${notSubmitted} remaining</span>`;

    const voteLines = totalVotes > 0 ? [
      `<span style="color:${FOREST}; font-weight:700;">Yes ${totalYes} (${Math.round(totalYes / totalVotes * 100)}%)</span>`,
      `<span style="color:${SLATE}; font-weight:700;">No ${totalNo} (${Math.round(totalNo / totalVotes * 100)}%)</span>`,
      `<span style="color:#8A9A93;">Abstain ${totalAbstain} (${Math.round(totalAbstain / totalVotes * 100)}%)</span>`,
    ].join('<br>') : '<span class="text-muted">No votes yet</span>';
    document.getElementById('vote-dist-legend').innerHTML = voteLines;

    document.getElementById('turnout-gauge-label').innerHTML =
      `<span style="font-weight:600; color:${FOREST};">${stats.ballots_submitted}</span> of <span style="font-weight:600;">${stats.checked_in}</span> checked-in members voted`;
  }

  function renderFilters(amendments) {
    const articles = Array.from(new Set(amendments.map(a => a.article_no)));
    const changed = articles.join('|') !== knownArticles.join('|');
    if (!changed) return;
    knownArticles = articles;

    filtersEl.innerHTML = '';
    const allChip = document.createElement('button');
    allChip.type = 'button';
    allChip.className = 'article-filter-chip' + (activeArticle === 'all' ? ' active' : '');
    allChip.textContent = 'All Articles';
    allChip.addEventListener('click', () => { activeArticle = 'all'; applyFilter(); });
    filtersEl.appendChild(allChip);

    articles.forEach(article => {
      const chip = document.createElement('button');
      chip.type = 'button';
      chip.className = 'article-filter-chip' + (activeArticle === article ? ' active' : '');
      chip.textContent = article;
      chip.dataset.article = article;
      chip.addEventListener('click', () => { activeArticle = article; applyFilter(); });
      filtersEl.appendChild(chip);
    });
  }

  function applyFilter() {
    Array.from(filtersEl.children).forEach(chip => {
      const val = chip.dataset.article || 'all';
      chip.classList.toggle('active', val === activeArticle);
    });
    Array.from(grid.children).forEach(card => {
      const show = activeArticle === 'all' || card.dataset.article === activeArticle;
      card.classList.toggle('d-none', !show);
    });
  }

  function ensureCard(a) {
    let col = grid.querySelector(`[data-amendment-id="${a.id}"]`);
    if (col) return col;

    col = document.createElement('div');
    col.className = 'col-12 col-lg-6 result-card';
    col.dataset.amendmentId = a.id;
    col.dataset.article = a.article_no;
    col.innerHTML = `
      <div class="card-panel p-3 p-md-4 h-100">
        <div class="d-flex justify-content-between align-items-start mb-1">
          <div>
            <div class="fw-bold" style="font-family:var(--font-display); font-size:1.15rem; color:var(--forest-700);">
              Proposal No. ${escapeHtml(a.proposal_no)}
            </div>
            <div class="text-muted" style="font-size:.78rem; text-transform:uppercase; letter-spacing:.04em;">
              ${escapeHtml(a.article_no)} · ${escapeHtml(a.section)} · Page ${escapeHtml(a.page_no)}
            </div>
          </div>
          <span class="badge rounded-pill" style="background:var(--forest-100); color:var(--forest-700);">
            ${a.total} vote${a.total === 1 ? '' : 's'}
          </span>
        </div>
        ${a.summary ? `<div class="text-muted mb-2" style="font-size:.9rem;">${escapeHtml(a.summary)}</div>` : ''}
        <div style="position: relative; width: 100%; height: 120px;">
          <canvas width="600" height="120"></canvas>
        </div>
      </div>`;
    grid.appendChild(col);

    const ctx = col.querySelector('canvas').getContext('2d');
    charts[a.id] = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Yes', 'No', 'Abstain'],
        datasets: [{
          data: [a.tally.yes, a.tally.no, a.tally.abstain],
          backgroundColor: [FOREST, SLATE, LIGHT],
          borderRadius: 6,
          maxBarThickness: 46,
        }],
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 300 },
        plugins: { legend: { display: false }, tooltip: { enabled: true } },
        scales: {
          x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#EFF4F1' } },
          y: { grid: { display: false } },
        },
      },
    });
    return col;
  }

  function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
  }

  function updateCard(col, a) {
    col.querySelector('.badge').textContent = `${a.total} vote${a.total === 1 ? '' : 's'}`;
    const chart = charts[a.id];
    if (chart) {
      chart.data.datasets[0].data = [a.tally.yes, a.tally.no, a.tally.abstain];
      chart.update();
    }
  }

  async function refresh() {
    try {
      const res = await fetch('api/results_data.php', { cache: 'no-store' });
      if (!res.ok) {
        let msg = 'The server returned an error (' + res.status + ').';
        try {
          const err = await res.json();
          if (err.error) msg = err.error;
        } catch (_) {}
        document.getElementById('results-error').classList.remove('d-none');
        document.getElementById('error-detail').textContent = msg;
        document.getElementById('summary-charts').classList.add('d-none');
        return;
      }
      const data = await res.json();

      document.getElementById('results-error').classList.add('d-none');
      document.getElementById('summary-charts').classList.remove('d-none');

      renderStats(data.stats);
      lastUpdated.textContent = 'Updated ' + fmtTime(data.generated_at);

      if (!summaryCharts.participation) {
        initSummaryCharts(data.stats, data.amendments);
      } else {
        updateSummaryCharts(data.stats, data.amendments);
      }

      if (!data.amendments.length) {
        emptyState.classList.remove('d-none');
        grid.classList.add('d-none');
        return;
      }
      emptyState.classList.add('d-none');
      grid.classList.remove('d-none');

      renderFilters(data.amendments);
      data.amendments.forEach(a => {
        const col = ensureCard(a);
        updateCard(col, a);
      });
      applyFilter();
    } catch (err) {
      // Silent error handling - charts will retry on next interval
    }
  }

  refresh();
  setInterval(refresh, REFRESH_MS);
});
