<?php
session_start();
include 'config.php';

// If user scans QR but isn't logged in, log them in and redirect them back here
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'student'){
    // Store where they were trying to go so they can bounce back after login
    // Note: The login.php would need minor tweaking to respect a redirect, but for typical use cases, 
    // we'll just forcefully alert them to log in as a student first.
    echo "<script>alert('Please log into your Student account first, then rescan the QR Code!'); window.location.href='index.php';</script>";
    exit();
}

$student_id = $_SESSION['user']['id'];
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$scanned_date = isset($_GET['d']) ? $_GET['d'] : '';
$today = date('Y-m-d');

$status_msg = "";
$status_type = "warning";
$icon = "⚠️";

if($course_id > 0 && $scanned_date == $today) {
    // 1. Verify student is actually enrolled in this course
    $enroll_check = $conn->query("SELECT * FROM enrollments WHERE student_id='$student_id' AND course_id='$course_id'");
    
    if($enroll_check->num_rows == 0) {
        $status_msg = "You are not enrolled in this course!";
        $status_type = "danger";
        $icon = "🚫";
    } else {
        // 2. Try to insert attendance (Standardized columns: user_id, date)
        try {
            $stmt = $conn->prepare("INSERT INTO attendance (user_id, course_id, date, status) VALUES (?, ?, ?, 'Present')");
            $stmt->bind_param("iis", $student_id, $course_id, $today);
            $stmt->execute();
            
            // 3. Reward Gamification XP
            $conn->query("UPDATE users SET xp = xp + 10 WHERE id='$student_id'");
            $_SESSION['user']['xp'] = isset($_SESSION['user']['xp']) ? $_SESSION['user']['xp'] + 10 : 10;
            
            $status_msg = "Attendance Recorded Successfully! <br><span class='text-success fw-bold'>⭐ +10 XP Earned!</span>";
            $status_type = "success";
            $icon = "✅";
        } catch (Exception $e) {
            // Error code 1062 is standard for Duplicate entry in MySQL
            if($conn->errno == 1062) {
                $status_msg = "You have already marked your attendance for today!";
                $status_type = "info";
                $icon = "👌";
            } else {
                $status_msg = "An error occurred: " . str_replace("'", "\'", $e->getMessage());
                $status_type = "danger";
                $icon = "❌";
            }
        }
    }
} else {
    $status_msg = "Invalid or expired Attendance QR Code.";
    $status_type = "danger";
    $icon = "⏰";
}

// WhatsApp Confirmation Data (Safe Check)
$wa_link = "";
if($status_type == "success") {
    // Check if parent_phone column exists before querying
    $check_col = $conn->query("SHOW COLUMNS FROM users LIKE 'parent_phone'");
    if($check_col->num_rows > 0) {
        $uData = $conn->query("SELECT parent_phone FROM users WHERE id='$student_id'")->fetch_assoc();
        $cData = $conn->query("SELECT course_name FROM courses WHERE id='$course_id'")->fetch_assoc();
        
        if(!empty($uData['parent_phone'])) {
            $parent_no = $uData['parent_phone'];
            if(!str_starts_with($parent_no, "+") && !str_starts_with($parent_no, $country_code)) {
                $parent_no = $country_code . ltrim($parent_no, '0');
            }
            $msg = "Hello, I have successfully arrived and marked my attendance for " . $cData['course_name'] . " today (" . date('h:i A') . "). ✅";
            $wa_link = "https://wa.me/" . preg_replace('/[^0-9]/', '', $parent_no) . "?text=" . urlencode($msg);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Mark Attendance</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body class="p-4" style="background:#f4f6f9; font-family:'Inter'; height:100vh; display:flex; align-items:center;">

<div class="container text-center">
    <div class="card p-5 shadow-lg mx-auto" style="max-width:500px; border-radius:20px; border:none;">
        <div style="font-size:70px; margin-bottom:20px;"><?php echo $icon; ?></div>
        
        <div class="alert alert-<?php echo $status_type; ?> fs-5 mb-4 p-4 text-center">
            <?php echo $status_msg; ?>
        </div>

        <?php if(!empty($wa_link)){ ?>
            <a href="<?php echo $wa_link; ?>" target="_blank" class="btn btn-success btn-lg w-100 fw-bold shadow-sm mb-3" style="background: #25D366; border:none;">
                📲 WhatsApp Confirmation to Parent
            </a>
        <?php } ?>
        
        <a href="dashboard.php" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm" style="background: linear-gradient(90deg, #4e54c8, #8f94fb); border:none;">
            Return to Dashboard
        </a>
    </div>
</div>

</body>
</html>
