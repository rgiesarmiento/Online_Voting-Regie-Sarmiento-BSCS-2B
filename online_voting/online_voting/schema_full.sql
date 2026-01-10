CREATE DATABASE IF NOT EXISTS online_voting
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;
USE online_voting;

-- Voters table
CREATE TABLE IF NOT EXISTS voters (
  voter_id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(200) NOT NULL,
  email VARCHAR(200) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Elections table
CREATE TABLE IF NOT EXISTS elections (
  election_id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  starts_at DATETIME NULL,
  ends_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Candidates table (each candidate belongs to an election)
CREATE TABLE IF NOT EXISTS candidates (
  candidate_id INT AUTO_INCREMENT PRIMARY KEY,
  election_id INT NOT NULL,
  name VARCHAR(200) NOT NULL,
  party VARCHAR(100) NULL,
  bio TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_candidates_election FOREIGN KEY (election_id)
    REFERENCES elections(election_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Votes table (links voter -> election -> candidate)
CREATE TABLE IF NOT EXISTS votes (
  vote_id INT AUTO_INCREMENT PRIMARY KEY,
  voter_id INT NOT NULL,
  election_id INT NOT NULL,
  candidate_id INT NOT NULL,
  voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_votes_voter FOREIGN KEY (voter_id)
    REFERENCES voters(voter_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_votes_election FOREIGN KEY (election_id)
    REFERENCES elections(election_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_votes_candidate FOREIGN KEY (candidate_id)
    REFERENCES candidates(candidate_id) ON DELETE CASCADE ON UPDATE CASCADE,
  -- Prevent a voter from voting more than once per election
  UNIQUE KEY unique_vote_per_election (voter_id, election_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample data
-- Use INSERT IGNORE for voters and votes so repeated imports won't error due to UNIQUE constraints.
INSERT IGNORE INTO voters (full_name, email) VALUES
  ('Camille Million', 'camille@example.com'),
  ('Juan Dela Cruz', 'juan@example.com'),
  ('Alyssa Reyes', 'alyssa@example.com');

-- Make sample elections open immediately and leave them open by using NULL for starts_at and ends_at
INSERT INTO elections (title, description, starts_at, ends_at) VALUES
  ('Student Council Election 2026', 'Election for student council president', NULL, NULL),
  ('Club President 2026', 'Club officer election', NULL, NULL);

INSERT INTO candidates (election_id, name, party, bio) VALUES
  (1, 'Alex Santos', 'Unity', 'Senior; outgoing VP; platform: transparency'),
  (1, 'Bea Cruz', 'Progress', 'Junior; community organizer; platform: events and outreach'),
  (2, 'Carlos Tan', 'Independent', 'Club member; focus on membership growth');

-- Optional example votes (may be ignored on re-import due to UNIQUE constraint)
INSERT IGNORE INTO votes (voter_id, election_id, candidate_id) VALUES
  (1, 1, 1),
  (2, 1, 2);

-- Helpful example queries (run in phpMyAdmin SQL tab)
-- SELECT * FROM voters;
-- SELECT * FROM elections;
-- SELECT * FROM candidates WHERE election_id = 1;
-- Get vote counts per candidate for a specific election:
-- SELECT c.candidate_id, c.name, c.party, COUNT(v.vote_id) AS votes
-- FROM candidates c
-- LEFT JOIN votes v ON c.candidate_id = v.candidate_id
-- WHERE c.election_id = 1
-- GROUP BY c.candidate_id, c.name, c.party
-- ORDER BY votes DESC;