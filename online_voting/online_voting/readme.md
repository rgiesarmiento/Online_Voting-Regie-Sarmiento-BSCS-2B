# Online Voting — Guide & Design

## Introduction
The Online Voting system is a small PHP + MySQL web application designed to let users register as voters, cast votes in elections, and view election results. It is intended for simple class- or club-level elections and focuses on a minimal, pragmatic API (JSON in / JSON out), a lightweight frontend (vanilla JS + HTML), and database-enforced integrity rules to prevent double-voting.

Key goals:
- Allow voters to register with name + email.
- Allow voters to cast one vote per election.
- Provide a results view that aggregates votes per candidate.
- Keep the API and frontend simple for easy deployment and learning.

## Database Design

ER diagram (Mermaid)

```mermaid
erDiagram
    VOTERS ||--o{ VOTES : casts
    ELECTIONS ||--o{ CANDIDATES : contains
    ELECTIONS ||--o{ VOTES : has
    CANDIDATES ||--o{ VOTES : receives

    VOTERS {
      int voter_id PK
      varchar full_name
      varchar email UNIQUE
      timestamp created_at
    }
    ELECTIONS {
      int election_id PK
      varchar title
      text description
      datetime starts_at NULL
      datetime ends_at NULL
      timestamp created_at
    }
    CANDIDATES {
      int candidate_id PK
      int election_id FK
      varchar name
      varchar party
      text bio
      timestamp created_at
    }
    VOTES {
      int vote_id PK
      int voter_id FK
      int election_id FK
      int candidate_id FK
      timestamp voted_at
    }
```

Table descriptions and important constraints
- voters
  - voter_id (INT, PK, AUTO_INCREMENT)
  - full_name (VARCHAR(200), NOT NULL)
  - email (VARCHAR(200), NOT NULL, UNIQUE)
  - created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
  - Notes: email uniquely identifies a voter in this demo; used to look up voter when casting a vote.

- elections
  - election_id (INT, PK, AUTO_INCREMENT)
  - title (VARCHAR(200), NOT NULL)
  - description (TEXT, NULL)
  - starts_at, ends_at (DATETIME, NULL) — optional scheduling; null means always open
  - created_at (TIMESTAMP)

- candidates
  - candidate_id (INT, PK, AUTO_INCREMENT)
  - election_id (INT, FK → elections.election_id, ON DELETE CASCADE)
  - name (VARCHAR(200), NOT NULL)
  - party (VARCHAR(100), NULL)
  - bio (TEXT, NULL)
  - created_at (TIMESTAMP)
  - Notes: candidates belong to an election; deleting an election removes its candidates.

- votes
  - vote_id (INT, PK, AUTO_INCREMENT)
  - voter_id (INT, FK → voters.voter_id, ON DELETE CASCADE)
  - election_id (INT, FK → elections.election_id, ON DELETE CASCADE)
  - candidate_id (INT, FK → candidates.candidate_id, ON DELETE CASCADE)
  - voted_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
  - UNIQUE KEY unique_vote_per_election (voter_id, election_id)
  - Notes: the unique index prevents a voter from voting more than once in the same election. Foreign keys enforce referential integrity and chosen cascade behaviors simplify cleanup for demo use.

Design rationale and relationships
- Voters cast votes; votes are tied to both election and candidate, enabling quick aggregation per candidate per election.
- Candidates are scoped to a single election. If the election is removed, candidates and associated votes are removed (ON DELETE CASCADE) — this is acceptable for a demo but may need more careful handling in production.
- The unique (voter_id, election_id) constraint enforces the "one vote per election per voter" rule at the database level, which is more reliable than application-only checks.

## Web Interface

Key pages
- index.php
  - Landing page linking to Register, Vote, and Results pages.

- register.php (+ register.js)
  - Allows a user to register a voter record (full_name + email).
  - Frontend sends FormData to api.php?action=register_voter (alias to create_voter).
  - Server returns the voter_id (or existing voter if the email already exists).

- vote.php (+ vote.js)
  - Lets a voter pick an election and candidate and submit a vote using their registered email.
  - Frontend fetches elections and candidate lists from the API and posts votes as JSON to api.php?action=cast_vote.
  - Handles errors such as "voter not registered", "election not open", and duplicate voting (HTTP 409).

- results.php (+ results.js)
  - Select an election and view aggregated vote counts per candidate.
  - Uses api.php?action=results&election_id=...

Backend API (online_voting/api.php)
- Single entry JSON API with actions:
  - Voters: list_voters (GET), create_voter/register_voter (POST), update_voter (POST), delete_voter (POST)
  - Elections: list_elections (GET), create_election (POST), update_election (POST), delete_election (POST)
  - Candidates: list_candidates (GET) [optional election_id], create_candidate (POST), update_candidate (POST), delete_candidate (POST)
  - Votes: cast_vote (POST) — validates voter by email, checks election window, inserts vote (unique constraint prevents duplicates)
  - Results: results (GET) — aggregated counts per candidate (LEFT JOIN votes)

Frontend behavior highlights
- Lightweight vanilla JS; pages fetch data from the API and update the DOM.
- register.js uses FormData to send values so PHP can read via $_POST.
- vote.js and results.js primarily use application/json requests/responses.
- Basic client-side validation (email format, required fields) for a better UX.

Deployment / developer notes
- DB connection is managed in db.php (MySQLi). Default config assumes localhost, root, empty password; update for your environment.
- schema_full.sql contains the schema and sample data (use to create the database and seed examples).
- CORS and simple OPTIONS handling are implemented in api.php for easy local development across hosts.

## Challenges and Learning

What was challenging
- Date/time handling: browser input type="datetime-local" produces a local timezone string (YYYY-MM-DDTHH:MM). Normalizing that reliably between frontend and backend is subtle and can lead to off-by-one errors if timezones aren't handled deliberately.
- Concurrency & constraints: preventing double-voting is enforced using a UNIQUE database constraint (voter_id, election_id). Handling SQL errors gracefully on insert (duplicate key error) and returning an appropriate HTTP status (409 Conflict) was necessary for a clear UX.
- Choosing foreign key behaviors: ON DELETE CASCADE on votes and candidates is convenient for demos but may not be appropriate for production systems where auditability and retention are required.

Key insights
- Let the database enforce integrity (FKs and unique constraints). Relying only on application checks is error-prone under concurrent access.
- Use prepared statements to avoid SQL injection and to keep responses predictable.
- Keep the API contract simple and consistent: returning JSON for success and errors with appropriate HTTP status codes makes frontend code much easier to implement and test.
- For development convenience, simple CORS (Access-Control-Allow-Origin: *) and permissive preflight handling are useful — but they must be tightened before a production deployment.

## Quick setup
1. Create/import the database:
   - mysql -u root -p < online_voting/schema_full.sql
2. Configure DB credentials in online_voting/db.php if different from defaults.
3. Run a local PHP server (from the online_voting directory):
   - php -S localhost:8000
4. Open http://localhost:8000/ in your browser.

## Security & production notes (brief)
- The demo uses permissive CORS and returns DB errors — both acceptable for learning but not for production.
- In production:
  - Use HTTPS.
  - Lock down CORS and origin checks.
  - Avoid returning raw DB error messages to clients.
  - Add authentication/authorization for admin operations (creating elections/candidates).
  - Consider stronger voter identity verification than email alone and stronger audit logging for votes.