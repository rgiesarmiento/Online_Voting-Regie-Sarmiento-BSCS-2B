// vote.js - voting page behavior
document.addEventListener('DOMContentLoaded', () => {
  loadElections();
  document.getElementById('electionSelect').addEventListener('change', onElectionChange);
  document.getElementById('voteForm').addEventListener('submit', onVote);
});

async function loadElections() {
  const sel = document.getElementById('electionSelect');
  sel.innerHTML = '<option value="">Loading...</option>';
  try {
    const res = await fetch('api.php?action=list_elections');
    const list = await res.json();
    if (!Array.isArray(list) || !list.length) {
      sel.innerHTML = '<option value="">No elections</option>'; return;
    }
    sel.innerHTML = '<option value="">Select an election</option>';
    list.forEach(e => {
      const opt = document.createElement('option');
      opt.value = e.election_id;
      opt.textContent = e.title;
      sel.appendChild(opt);
    });
  } catch (err) {
    sel.innerHTML = '<option value="">Failed to load</option>';
  }
}

async function onElectionChange(e) {
  const eid = e.target.value;
  const cand = document.getElementById('candidateSelect');
  cand.innerHTML = '<option>Loading...</option>';
  if (!eid) { cand.innerHTML = '<option value="">Select an election first</option>'; return; }
  try {
    const res = await fetch(`api.php?action=list_candidates&election_id=${encodeURIComponent(eid)}`);
    const list = await res.json();
    if (!Array.isArray(list) || !list.length) {
      cand.innerHTML = '<option value="">No candidates</option>'; return;
    }
    cand.innerHTML = '<option value="">Select a candidate</option>';
    list.forEach(c => {
      const opt = document.createElement('option');
      opt.value = c.candidate_id;
      opt.textContent = `${c.name}${c.party ? ' ('+c.party+')' : ''}`;
      cand.appendChild(opt);
    });
  } catch (err) {
    cand.innerHTML = '<option value="">Failed to load</option>';
  }
}

async function onVote(e) {
  e.preventDefault();
  const msg = document.getElementById('voteMsg');
  msg.textContent = 'Submitting...'; msg.className='msg';
  const f = e.target;
  const email = f.email.value.trim();
  const election_id = Number(f.election.value);
  const candidate_id = Number(f.candidate.value);
  if (!validateEmail(email) || !election_id || !candidate_id) {
    msg.textContent = 'Please provide your registered email, election, and candidate.'; msg.className='msg error'; return;
  }
  try {
    const res = await fetch('api.php?action=cast_vote', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({email, election_id, candidate_id})
    });
    const j = await res.json();
    if (res.ok) {
      msg.textContent = 'Vote cast successfully — thank you!'; msg.className='msg';
      f.reset();
      document.getElementById('candidateSelect').innerHTML = '<option value="">Select an election first</option>';
    } else if (res.status === 409) {
      msg.textContent = j.error || 'You have already voted in this election.'; msg.className='msg error';
    } else {
      msg.textContent = j.error || 'Failed to cast vote.'; msg.className='msg error';
    }
  } catch (err) {
    msg.textContent = 'Network error — try again.'; msg.className='msg error';
  }
}

function validateEmail(e){ return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e); }