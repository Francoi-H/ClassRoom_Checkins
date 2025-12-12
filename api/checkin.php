<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

if (!is_logged_in() || !is_student()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$raw = file_get_contents('php://input');
$json = json_decode($raw, true);

$session_id = (int)($json['session_id'] ?? $_POST['session_id'] ?? 0);
$lat = $json['latitude'] ?? $json['lat'] ?? $_POST['latitude'] ?? $_POST['lat'] ?? null;
$lng = $json['longitude'] ?? $json['lng'] ?? $_POST['longitude'] ?? $_POST['lng'] ?? null;

if ($session_id <= 0 || $lat === null || $lng === null) {
    echo json_encode(['success' => false, 'message' => 'Missing session_id/latitude/longitude']);
    exit;
}

$student_id = (int)$_SESSION['user_id'];
$lat = (float)$lat;
$lng = (float)$lng;

try {
    $stmt = $conn->prepare("SELECT class_id, is_active, approved_location FROM class_sessions WHERE session_id = ? LIMIT 1");
    $stmt->bind_param("i", $session_id);
    $stmt->execute();
    $session = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Session not found']);
        exit;
    }

    if ((int)$session['is_active'] !== 1) {
        echo json_encode(['success' => false, 'message' => 'Check-in closed for this session']);
        exit;
    }

    $class_id = (int)$session['class_id'];
    $stmt = $conn->prepare("SELECT 1 FROM enrolls WHERE student_id = ? AND class_id = ? LIMIT 1");
    $stmt->bind_param("ii", $student_id, $class_id);
    $stmt->execute();
    $enrolled = $stmt->get_result()->fetch_row();
    $stmt->close();

    if (!$enrolled) {
        echo json_encode(['success' => false, 'message' => 'You are not enrolled in this class']);
        exit;
    }

    $check_in_location = $session['approved_location'];

    $stmt = $conn->prepare("
        INSERT INTO attendance (session_id, student_id, check_in_location, latitude, longitude, status)
        VALUES (?, ?, ?, ?, ?, 'present')
    ");
    $stmt->bind_param("iisdd", $session_id, $student_id, $check_in_location, $lat, $lng);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Checked in successfully']);
    } else {
        if ($conn->errno == 1062) {
            echo json_encode(['success' => false, 'message' => 'Already checked in for this session']);
        } else {
            echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
        }
    }

    $stmt->close();
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
