<?php
// voting_api.php - JSON API for Online Voting (voters, elections, candidates, votes)
// Updated: added robust request body parsing (accept JSON, form-data, urlencoded),
// fixed alias handling for register_voter, and lightweight CORS/OPTIONS support.
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// simple preflight handling
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

function json_response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

/**
 * Get request data in a robust way:
 * - If Content-Type is application/json -> decode JSON body
 * - Else if $_POST populated -> use $_POST (form-data or urlencoded)
 * - Else attempt to parse raw input as urlencoded
 */
function get_request_data() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    // If PHP already populated $_POST (form submission or multipart/form-data)
    if (!empty($_POST)) {
        // normalize values (trim strings)
        $out = [];
        foreach ($_POST as $k => $v) {
            if (is_string($v)) $out[$k] = trim($v);
            else $out[$k] = $v;
        }
        return $out;
    }

    // Fall back: parse raw input (application/x-www-form-urlencoded)
    $raw = file_get_contents('php://input');
    if ($raw) {
        parse_str($raw, $data);
        return is_array($data) ? $data : [];
    }

    return [];
}

try {
    // Support alias: allow clients to call action=register_voter (map to create_voter)
    if ($action === 'register_voter') {
        $action = 'create_voter';
    }

    // --- VOTERS ---
    if ($action === 'list_voters' && $method === 'GET') {
        $res = $GLOBALS['mysqli']->query("SELECT voter_id, full_name, email, created_at FROM voters ORDER BY created_at DESC");
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        json_response($rows);
    }

    if ($action === 'create_voter' && $method === 'POST') {
        $data = get_request_data();
        $name = trim($data['full_name'] ?? '');
        $email = trim($data['email'] ?? '');
        if (!$name || !$email) json_response(['error' => 'full_name and email required'], 400);

        $stmt = $GLOBALS['mysqli']->prepare("INSERT INTO voters (full_name, email) VALUES (?, ?)");
        $stmt->bind_param('ss', $name, $email);
        if ($stmt->execute()) {
            json_response(['success' => true, 'voter_id' => $GLOBALS['mysqli']->insert_id]);
        } else {
            // duplicate key handling (unique email)
            $errno = $stmt->errno ?: $GLOBALS['mysqli']->errno;
            if ($errno === 1062) {
                // return existing
                $q = $GLOBALS['mysqli']->prepare("SELECT voter_id FROM voters WHERE email = ?");
                $q->bind_param('s', $email);
                $q->execute();
                $r = $q->get_result()->fetch_assoc();
                json_response(['success' => true, 'voter_id' => $r['voter_id'], 'note' => 'already_registered']);
            }
            json_response(['error' => $stmt->error ?: $GLOBALS['mysqli']->error], 500);
        }
    }

    if ($action === 'update_voter' && $method === 'POST') {
        $data = get_request_data();
        $id = intval($data['voter_id'] ?? 0);
        $name = trim($data['full_name'] ?? '');
        $email = trim($data['email'] ?? '');
        if (!$id || !$name || !$email) json_response(['error' => 'voter_id, full_name and email required'], 400);
        $stmt = $GLOBALS['mysqli']->prepare("UPDATE voters SET full_name=?, email=? WHERE voter_id=?");
        $stmt->bind_param('ssi', $name, $email, $id);
        if ($stmt->execute()) json_response(['success' => true]);
        json_response(['error' => $stmt->error], 500);
    }

    if ($action === 'delete_voter' && $method === 'POST') {
        $data = get_request_data();
        $id = intval($data['voter_id'] ?? 0);
        if (!$id) json_response(['error' => 'voter_id required'], 400);
        $stmt = $GLOBALS['mysqli']->prepare("DELETE FROM voters WHERE voter_id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) json_response(['success' => true]);
        json_response(['error' => $stmt->error], 500);
    }

    // --- ELECTIONS ---
    if ($action === 'list_elections' && $method === 'GET') {
        $res = $GLOBALS['mysqli']->query("SELECT election_id, title, description, starts_at, ends_at, created_at FROM elections ORDER BY created_at DESC");
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        json_response($rows);
    }

    if ($action === 'create_election' && $method === 'POST') {
        $data = get_request_data();
        $title = trim($data['title'] ?? '');
        $desc = $data['description'] ?? null;
        $starts = $data['starts_at'] ?? null;
        $ends = $data['ends_at'] ?? null;
        if (!$title) json_response(['error' => 'title required'], 400);
        $stmt = $GLOBALS['mysqli']->prepare("INSERT INTO elections (title, description, starts_at, ends_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $title, $desc, $starts, $ends);
        if ($stmt->execute()) json_response(['success' => true, 'election_id' => $GLOBALS['mysqli']->insert_id]);
        json_response(['error' => $stmt->error], 500);
    }

    if ($action === 'update_election' && $method === 'POST') {
        $data = get_request_data();
        $id = intval($data['election_id'] ?? 0);
        $title = trim($data['title'] ?? '');
        $desc = $data['description'] ?? null;
        $starts = $data['starts_at'] ?? null;
        $ends = $data['ends_at'] ?? null;
        if (!$id || !$title) json_response(['error' => 'election_id and title required'], 400);
        $stmt = $GLOBALS['mysqli']->prepare("UPDATE elections SET title=?, description=?, starts_at=?, ends_at=? WHERE election_id=?");
        $stmt->bind_param('ssssi', $title, $desc, $starts, $ends, $id);
        if ($stmt->execute()) json_response(['success' => true]);
        json_response(['error' => $stmt->error], 500);
    }

    if ($action === 'delete_election' && $method === 'POST') {
        $data = get_request_data();
        $id = intval($data['election_id'] ?? 0);
        if (!$id) json_response(['error' => 'election_id required'], 400);
        $stmt = $GLOBALS['mysqli']->prepare("DELETE FROM elections WHERE election_id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) json_response(['success' => true]);
        json_response(['error' => $stmt->error], 500);
    }

    // --- CANDIDATES ---
    if ($action === 'list_candidates' && $method === 'GET') {
        $eid = intval($_GET['election_id'] ?? 0);
        if ($eid) {
            $stmt = $GLOBALS['mysqli']->prepare("SELECT candidate_id, election_id, name, party, bio, created_at FROM candidates WHERE election_id = ? ORDER BY created_at DESC");
            $stmt->bind_param('i', $eid);
            $stmt->execute();
            json_response($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        } else {
            $res = $GLOBALS['mysqli']->query("SELECT candidate_id, election_id, name, party, bio, created_at FROM candidates ORDER BY created_at DESC");
            json_response($res->fetch_all(MYSQLI_ASSOC));
        }
    }

    if ($action === 'create_candidate' && $method === 'POST') {
        $data = get_request_data();
        $eid = intval($data['election_id'] ?? 0);
        $name = trim($data['name'] ?? '');
        $party = $data['party'] ?? null;
        $bio = $data['bio'] ?? null;
        if (!$eid || !$name) json_response(['error' => 'election_id and name required'], 400);
        $stmt = $GLOBALS['mysqli']->prepare("INSERT INTO candidates (election_id, name, party, bio) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('isss', $eid, $name, $party, $bio);
        if ($stmt->execute()) json_response(['success' => true, 'candidate_id' => $GLOBALS['mysqli']->insert_id]);
        json_response(['error' => $stmt->error], 500);
    }

    if ($action === 'update_candidate' && $method === 'POST') {
        $data = get_request_data();
        $id = intval($data['candidate_id'] ?? 0);
        $name = trim($data['name'] ?? '');
        $party = $data['party'] ?? null;
        $bio = $data['bio'] ?? null;
        if (!$id || !$name) json_response(['error' => 'candidate_id and name required'], 400);
        $stmt = $GLOBALS['mysqli']->prepare("UPDATE candidates SET name=?, party=?, bio=? WHERE candidate_id=?");
        $stmt->bind_param('sssi', $name, $party, $bio, $id);
        if ($stmt->execute()) json_response(['success' => true]);
        json_response(['error' => $stmt->error], 500);
    }

    if ($action === 'delete_candidate' && $method === 'POST') {
        $data = get_request_data();
        $id = intval($data['candidate_id'] ?? 0);
        if (!$id) json_response(['error' => 'candidate_id required'], 400);
        $stmt = $GLOBALS['mysqli']->prepare("DELETE FROM candidates WHERE candidate_id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) json_response(['success' => true]);
        json_response(['error' => $stmt->error], 500);
    }

    // --- CAST VOTE / RESULTS ---
    if ($action === 'cast_vote' && $method === 'POST') {
        $data = get_request_data();
        $email = trim($data['email'] ?? '');
        $election_id = intval($data['election_id'] ?? 0);
        $candidate_id = intval($data['candidate_id'] ?? 0);
        if (!$email || !$election_id || !$candidate_id) json_response(['error' => 'email, election_id and candidate_id required'], 400);

        // Find voter
        $stmt = $GLOBALS['mysqli']->prepare("SELECT voter_id FROM voters WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $v = $stmt->get_result()->fetch_assoc();
        if (!$v) json_response(['error' => 'voter not registered'], 400);
        $voter_id = intval($v['voter_id']);

        // Check election is active (optional; if null allowed)
        $stmt2 = $GLOBALS['mysqli']->prepare("SELECT starts_at, ends_at FROM elections WHERE election_id = ?");
        $stmt2->bind_param('i', $election_id);
        $stmt2->execute();
        $e = $stmt2->get_result()->fetch_assoc();
        if (!$e) json_response(['error' => 'invalid election'], 400);
        $now = date('Y-m-d H:i:s');
        if (($e['starts_at'] && $now < $e['starts_at']) || ($e['ends_at'] && $now > $e['ends_at'])) {
            json_response(['error' => 'election not open'], 400);
        }

        // Insert vote
        $ins = $GLOBALS['mysqli']->prepare("INSERT INTO votes (voter_id, election_id, candidate_id) VALUES (?, ?, ?)");
        $ins->bind_param('iii', $voter_id, $election_id, $candidate_id);
        if ($ins->execute()) json_response(['success' => true, 'vote_id' => $GLOBALS['mysqli']->insert_id]);
        $errno = $ins->errno ?: $GLOBALS['mysqli']->errno;
        if ($errno === 1062) json_response(['error' => 'voter has already voted in this election'], 409);
        json_response(['error' => $ins->error], 500);
    }

    if ($action === 'results' && $method === 'GET') {
        $eid = intval($_GET['election_id'] ?? 0);
        if (!$eid) json_response(['error' => 'election_id required'], 400);
        $stmt = $GLOBALS['mysqli']->prepare("
            SELECT c.candidate_id, c.name, c.party, COUNT(v.vote_id) AS votes
            FROM candidates c
            LEFT JOIN votes v ON c.candidate_id = v.candidate_id
            WHERE c.election_id = ?
            GROUP BY c.candidate_id, c.name, c.party
            ORDER BY votes DESC, c.name
        ");
        $stmt->bind_param('i', $eid);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        json_response($rows);
    }

    json_response(['error' => 'Invalid action or method'], 400);
} catch (Exception $e) {
    json_response(['error' => $e->getMessage()], 500);
}
?>