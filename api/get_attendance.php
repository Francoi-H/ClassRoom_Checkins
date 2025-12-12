<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!is_logged_in() || !is_instructor()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;
$instructor_id = $_SESSION['user_id'];

if ($session_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT cs.class_id
        FROM class_sessions cs
        INNER JOIN instructs i ON cs.class_id = i.class_id
        WHERE cs.session_id = ? AND i.instructor_id = ?
    ");
    $stmt->bind_param("ii", $session_id, $instructor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $class_data = $result->fetch_assoc();
    $class_id = $class_data['class_id'];
    $stmt->close();
    
    $stmt = $conn->prepare("
        SELECT 
            u.user_id,
            u.first_name,
            u.last_name,
            u.email,
            a.attendance_id,
            a.check_in_time,
            a.status
        FROM users u
        INNER JOIN enrolls e ON u.user_id = e.student_id
        LEFT JOIN attendance a ON u.user_id = a.student_id AND a.session_id = ?
        WHERE e.class_id = ?
        ORDER BY u.last_name, u.first_name
    ");
    
    $stmt->bind_param("ii", $session_id, $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $attendance = [];
    $present_count = 0;
    $absent_count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $status = $row['status'] ?? 'absent';
        
        if ($status === 'present' || $status === 'late') {
            $present_count++;
        } else {
            $absent_count++;
        }
        
        $attendance[] = [
            'user_id' => $row['user_id'],
            'student_name' => $row['first_name'] . ' ' . $row['last_name'],
            'email' => $row['email'],
            'attendance_id' => $row['attendance_id'],
            'check_in_time' => $row['check_in_time'] ? date('g:i A', strtotime($row['check_in_time'])) : null,
            'status' => $status
        ];
    }
    
    $stmt->close();
    
    $total = $present_count + $absent_count;
    $rate = $total > 0 ? round(($present_count / $total) * 100) : 0;
    
    echo json_encode([
        'success' => true,
        'attendance' => $attendance,
        'stats' => [
            'present' => $present_count,
            'absent' => $absent_count,
            'rate' => $rate
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching attendance'
    ]);
}
?>