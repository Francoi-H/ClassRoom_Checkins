<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!is_logged_in() || !is_instructor()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$instructor_id = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM instructs WHERE instructor_id = ?");
    $stmt->bind_param("i", $instructor_id);
    $stmt->execute();
    $total_classes = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT e.student_id) as total
        FROM enrolls e
        INNER JOIN instructs i ON e.class_id = i.class_id
        WHERE i.instructor_id = ?
    ");
    $stmt->bind_param("i", $instructor_id);
    $stmt->execute();
    $total_students = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total
        FROM class_sessions cs
        INNER JOIN instructs i ON cs.class_id = i.class_id
        WHERE i.instructor_id = ? 
        AND cs.session_date = CURDATE()
        AND cs.is_active = 1
    ");
    $stmt->bind_param("i", $instructor_id);
    $stmt->execute();
    $active_sessions = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_classes' => $total_classes,
            'total_students' => $total_students,
            'active_sessions' => $active_sessions
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching stats'
    ]);
}
?>