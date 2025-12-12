<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!is_logged_in() || !is_student()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$student_id = $_SESSION['user_id'];

if ($class_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid class ID']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT 
            cs.session_date,
            cs.session_time,
            a.check_in_time,
            a.status
        FROM attendance a
        INNER JOIN class_sessions cs ON a.session_id = cs.session_id
        WHERE a.student_id = ?
        AND cs.class_id = ?
        ORDER BY cs.session_date DESC, cs.session_time DESC
        LIMIT 10
    ");
    
    $stmt->bind_param("ii", $student_id, $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = [
            'session_date' => date('M j, Y', strtotime($row['session_date'])),
            'check_in_time' => date('g:i A', strtotime($row['check_in_time'])),
            'status' => ucfirst($row['status'])
        ];
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'history' => $history
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching history'
    ]);
}
?>