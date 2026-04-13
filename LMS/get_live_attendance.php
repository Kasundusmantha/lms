<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'teacher'){
    exit("Unauthorized");
}

$selected_course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;
$today = date('Y-m-d');

if($selected_course_id){
    $attendance_list = $conn->query("
        SELECT u.name, u.email, a.status 
        FROM attendance a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.course_id='$selected_course_id' AND a.date='$today'
        ORDER BY a.id DESC
    ");

    if($attendance_list && $attendance_list->num_rows > 0){ 
        while($row = $attendance_list->fetch_assoc()){ ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($row['name']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small></td>
                <td><span class="badge bg-success">Present</span></td>
            </tr>
        <?php } 
    } else { ?>
        <tr><td colspan="2" class="text-center text-muted p-4">No students have scanned in yet.</td></tr>
    <?php }
}
?>
