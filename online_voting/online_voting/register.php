<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Register - Online Voting</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <header class="topbar small">
    <a href="index.html" class="brand">← Home</a>
    <h2>Register as Voter</h2>
  </header>

  <main class="container">
    <section class="card form-card">
      <form id="registerForm">
        <label>Full name
          <input type="text" name="full_name" required placeholder="e.g., Juan Dela Cruz" />
        </label>
        <label>Email
          <input type="email" name="email" required placeholder="your.email@example.com" />
        </label>
        <div class="form-actions">
          <button class="btn" type="submit">Register</button>
          <a class="btn btn-ghost" href="vote.php">Go Vote</a>
        </div>
        <div id="regMsg" class="msg" role="status"></div>
      </form>
    </section>
  </main>

  <script src="register.js"></script>
</body>
</html>