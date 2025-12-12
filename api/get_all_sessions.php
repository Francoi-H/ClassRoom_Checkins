<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!is_logged_in() || !is_instructor()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$instructor_id = $_SESSION['user_id'];

if ($class_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid class ID']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT instructs_id FROM instructs WHERE instructor_id = ? AND class_id = ?");
    $stmt->bind_param("ii", $instructor_id, $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    $stmt->close();
    
    $stmt = $conn->prepare("
        SELECT 
            session_id,
            session_date,
            session_time,
            approved_location,
            is_active
        FROM class_sessions
        WHERE class_id = ?
        ORDER BY session_date DESC, session_time DESC
    ");
    
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sessions = [];
    while ($row = $result->fetch_assoc()) {
        $sessions[] = [
            'session_id' => $row['session_id'],
            'session_date' => date('M j, Y', strtotime($row['session_date'])),
            'session_time' => date('g:i A', strtotime($row['session_time'])),
            'approved_location' => $row['approved_location'],
            'is_active' => $row['is_active']
        ];
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'sessions' => $sessions
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching sessions'
    ]);
}
?>