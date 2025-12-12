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
            u.user_id,
            u.first_name,
            u.last_name,
            u.email,
            e.enrollment_date
        FROM users u
        INNER JOIN enrolls e ON u.user_id = e.student_id
        WHERE e.class_id = ?
        ORDER BY u.last_name, u.first_name
    ");
    
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = [
            'user_id' => $row['user_id'],
            'name' => $row['first_name'] . ' ' . $row['last_name'],
            'email' => $row['email'],
            'enrollment_date' => date('M j, Y', strtotime($row['enrollment_date']))
        ];
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'students' => $students
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching students'
    ]);
}
?>