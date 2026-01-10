<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Vote - Online Voting</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <header class="topbar small">
    <a href="index.html" class="brand">← Home</a>
    <h2>Cast Your Vote</h2>
  </header>

  <main class="container">
    <section class="card form-card">
      <form id="voteForm">
        <label>Your registered email
          <input type="email" name="email" required placeholder="you@example.com" />
        </label>

        <label>Election
          <select name="election" id="electionSelect" required>
            <option value="">Loading elections...</option>
          </select>
        </label>

        <label>Candidate
          <select name="candidate" id="candidateSelect" required>
            <option value="">Select an election first</option>
          </select>
        </label>

        <div class="form-actions">
          <button class="btn" type="submit">Submit Vote</button>
          <a class="btn btn-ghost" href="results.php">View Results</a>
        </div>

        <div id="voteMsg" class="msg" role="status"></div>
      </form>
    </section>
  </main>

  <script src="vote.js"></script>
</body>
</html>