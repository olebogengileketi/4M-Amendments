document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('checkin-form');
  if (!form) return;

  const alertBox = document.getElementById('checkin-alert');
  const submitBtn = document.getElementById('checkin-submit');
  const spinner = submitBtn.querySelector('.spinner-border');
  const btnText = submitBtn.querySelector('.btn-text');

  function setLoading(loading) {
    submitBtn.disabled = loading;
    spinner.classList.toggle('d-none', !loading);
    btnText.textContent = loading ? 'Checking in…' : 'Check In & Begin Voting';
  }

  function clearErrors() {
    alertBox.classList.add('d-none');
    alertBox.textContent = '';
    ['full_name', 'local_church', 'area'].forEach(id => {
      document.getElementById(id).classList.remove('is-invalid');
    });
  }

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    clearErrors();

    const full_name = document.getElementById('full_name').value.trim();
    const local_church = document.getElementById('local_church').value.trim();
    const area = document.getElementById('area').value.trim();

    let hasError = false;
    if (!full_name) { document.getElementById('full_name').classList.add('is-invalid'); hasError = true; }
    if (!local_church) { document.getElementById('local_church').classList.add('is-invalid'); hasError = true; }
    if (!area) { document.getElementById('area').classList.add('is-invalid'); hasError = true; }
    if (hasError) return;

    setLoading(true);
    try {
      const res = await fetch('api/checkin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ full_name, local_church, area }),
      });
      const data = await res.json();

      if (!res.ok) {
        alertBox.textContent = data.error || 'Something went wrong. Please try again.';
        alertBox.classList.remove('d-none');
        setLoading(false);
        return;
      }

      sessionStorage.setItem('ypd_token', data.token);
      sessionStorage.setItem('ypd_name', data.full_name);
      window.location.href = 'ballot.php';
    } catch (err) {
      alertBox.textContent = 'We could not reach the server. Please check your connection and try again.';
      alertBox.classList.remove('d-none');
      setLoading(false);
    }
  });
});
