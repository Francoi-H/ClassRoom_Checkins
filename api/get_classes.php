<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

try {
    if ($user_type === 'student') {
        $stmt = $conn->prepare("
            SELECT c.class_id, c.class_name, c.class_code, c.description
            FROM classes c
            INNER JOIN enrolls e ON c.class_id = e.class_id
            WHERE e.student_id = ?
            ORDER BY c.class_name
        ");
        $stmt->bind_param("i", $user_id);
    } else {
        $stmt = $conn->prepare("
            SELECT c.class_id, c.class_name, c.class_code, c.description
            FROM classes c
            INNER JOIN instructs i ON c.class_id = i.class_id
            WHERE i.instructor_id = ?
            ORDER BY c.class_name
        ");
        $stmt->bind_param("i", $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $classes = [];
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'classes' => $classes
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching classes: ' . $e->getMessage()
    ]);
}
?>