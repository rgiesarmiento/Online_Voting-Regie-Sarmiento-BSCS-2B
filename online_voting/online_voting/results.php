<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Results - Online Voting</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <header class="topbar small">
    <a href="index.html" class="brand">← Home</a>
    <h2>Election Results</h2>
  </header>

  <main class="container">
    <section class="card">
      <label>Select Election
        <select id="resultsElection">
          <option value="">Loading elections...</option>
        </select>
      </label>
      <div style="margin-top:.8rem">
        <button id="viewResults" class="btn">Show Results</button>
      </div>

      <div id="resultsContainer" class="results" style="margin-top:1rem"></div>
    </section>
  </main>

  <script src="results.js"></script>
</body>
</html>