// register.js - register page behavior
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('registerForm');
  const msg = document.getElementById('regMsg');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    msg.textContent = 'Registering...';
    msg.className = 'msg';
    const fd = new FormData(form);
    // Use FormData directly so PHP can read values in $_POST
    if (!fd.get('full_name').trim() || !validateEmail(fd.get('email').trim())) {
      msg.textContent = 'Please enter a valid name and email.';
      msg.className = 'msg error';
      return;
    }
    try {
      const res = await fetch('api.php?action=register_voter', {
        method: 'POST',
        body: fd
      });
      const j = await res.json();
      if (res.ok) {
        msg.textContent = 'Registered successfully. You may now vote.';
        msg.className = 'msg';
        form.reset();
      } else {
        msg.textContent = j.error || j.note || 'Registration failed';
        msg.className = 'msg error';
      }
    } catch (err) {
      msg.textContent = 'Network error — try again.';
      msg.className = 'msg error';
    }
  });
});

function validateEmail(e){ return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e); }