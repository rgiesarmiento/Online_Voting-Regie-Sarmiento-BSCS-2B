// results.js - results page behavior
document.addEventListener('DOMContentLoaded', () => {
  loadElections();
  document.getElementById('viewResults').addEventListener('click', showResults);
});

async function loadElections() {
  const sel = document.getElementById('resultsElection');
  sel.innerHTML = '<option>Loading...</option>';
  try {
    const res = await fetch('api.php?action=list_elections');
    const list = await res.json();
    if (!Array.isArray(list) || !list.length) { sel.innerHTML = '<option>No elections</option>'; return; }
    sel.innerHTML = '<option value="">Select an election</option>';
    list.forEach(e => {
      const opt = document.createElement('option'); opt.value = e.election_id; opt.textContent = e.title; sel.appendChild(opt);
    });
  } catch (err) {
    sel.innerHTML = '<option>Failed to load</option>';
  }
}

async function showResults() {
  const sel = document.getElementById('resultsElection');
  const eid = sel.value;
  const container = document.getElementById('resultsContainer');
  if (!eid) { container.innerHTML = '<div class="msg error">Select an election first.</div>'; return; }
  container.innerHTML = 'Loading...';
  try {
    const res = await fetch(`api.php?action=results&election_id=${encodeURIComponent(eid)}`);
    const rows = await res.json();
    if (!Array.isArray(rows) || !rows.length) { container.innerHTML = '<div class="msg">No candidates or votes yet.</div>'; return; }
    let html = `<table class="results-table"><thead><tr><th>Candidate</th><th>Party</th><th>Votes</th></tr></thead><tbody>`;
    rows.forEach(r => {
      html += `<tr><td>${escapeHtml(r.name)}</td><td>${escapeHtml(r.party || '')}</td><td>${r.votes}</td></tr>`;
    });
    html += '</tbody></table>';
    container.innerHTML = html;
  } catch (err) {
    container.innerHTML = '<div class="msg error">Failed to load results.</div>';
  }
}

function escapeHtml(s){ if(!s) return ''; return s.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }