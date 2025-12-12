<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!is_logged_in() || !is_student()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$user_lat = isset($_GET['latitude']) ? floatval($_GET['latitude']) : 0;
$user_lng = isset($_GET['longitude']) ? floatval($_GET['longitude']) : 0;
$student_id = $_SESSION['user_id'];

if ($class_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid class ID']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT 
            cs.session_id,
            cs.session_date,
            cs.session_time,
            cs.approved_location,
            cs.latitude,
            cs.longitude,
            cs.location_radius,
            a.attendance_id
        FROM class_sessions cs
        LEFT JOIN attendance a ON cs.session_id = a.session_id AND a.student_id = ?
        WHERE cs.class_id = ?
        AND cs.session_date = CURDATE()
        AND cs.is_active = 1
        ORDER BY cs.session_time
    ");
    
    $stmt->bind_param("ii", $student_id, $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sessions = [];
    while ($row = $result->fetch_assoc()) {
        $distance = null;
        if ($row['latitude'] && $row['longitude'] && $user_lat && $user_lng) {
            $earth_radius = 6371000; 
            
            $lat1 = deg2rad($user_lat);
            $lat2 = deg2rad($row['latitude']);
            $lng1 = deg2rad($user_lng);
            $lng2 = deg2rad($row['longitude']);
            
            $dlat = $lat2 - $lat1;
            $dlng = $lng2 - $lng1;
            
            $a = sin($dlat/2) * sin($dlat/2) + 
                 cos($lat1) * cos($lat2) * 
                 sin($dlng/2) * sin($dlng/2);
            $c = 2 * atan2(sqrt($a), sqrt(1-$a));
            
            $distance = $earth_radius * $c; 
        }
        
        $in_range = false;
        if ($distance !== null && $row['location_radius']) {
            $in_range = $distance <= $row['location_radius'];
        }
        
        $sessions[] = [
            'session_id' => $row['session_id'],
            'session_date' => $row['session_date'],
            'session_time' => date('g:i A', strtotime($row['session_time'])),
            'approved_location' => $row['approved_location'],
            'distance' => $distance,
            'in_range' => $in_range,
            'already_checked_in' => !is_null($row['attendance_id'])
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