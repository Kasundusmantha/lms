<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'student'){
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user']['id'];

// Filter Logic
$course_filter = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;
$where_clause = "WHERE a.user_id = '$user_id'";
if($course_filter) $where_clause .= " AND a.course_id = '$course_filter'";

// Fetch Personal Attendance
$attendance_query = $conn->query("
    SELECT a.*, c.course_name 
    FROM attendance a 
    JOIN courses c ON a.course_id = c.id 
    $where_clause 
    ORDER BY a.date DESC, a.created_at DESC
");

// Fetch Enrolled Courses for filter
$my_courses = $conn->query("
    SELECT c.id, c.course_name 
    FROM courses c 
    JOIN enrollments e ON c.id = e.course_id 
    WHERE e.student_id = '$user_id'
");

$total_present = $attendance_query->num_rows;
?>
<?php
include 'components/header.php';
include 'components/sidebar.php';
?>

<!-- MAIN CONTENT -->
<div class="main-astra w-100">
    <div class="header-astra mb-4 animate-up">
        <h4 class="fw-bold mb-0">📅 Attendance Monitoring</h4>
    </div>

<div class="container-fluid animate-up" style="animation-delay: 0.1s;">

    <div class="d-flex justify-content-between align-items-center mb-5">
        <h2 class="fw-bold mb-0">🕒 Personal Verification History</h2>
    </div>
<?php echo $message ?? ''; ?>

    <div class="row g-4">
        <!-- Attendance Stats -->
        <div class="col-md-4">
            <div class="card-astra p-5 text-center shadow-lg mb-4">
                <div class="fs-1 mb-2">✅</div>
                <div class="text-uppercase fw-bold opacity-75 mb-1" style="font-size: 10px; letter-spacing: 1px;">Engagement Pulse</div>
                <h1 class="mb-0 fw-bold" style="font-size: 48px;"><?php echo $total_present; ?></h1>
                <small class="opacity-50">Verified Check-ins</small>
            </div>
            
            <div class="card-astra p-5 shadow-lg">
                <h6 class="fw-bold mb-4 opacity-75">Scope Filter</h6>
                <form method="get">
                    <select name="course_id" class="form-control-astra w-100" onchange="this.form.submit()" style="height: 52px;">
                        <option value="">-- All Enrolled Modules --</option>
                        <?php 
                        $my_courses->data_seek(0);
                        while($c = $my_courses->fetch_assoc()){ ?>
                            <option value="<?php echo $c['id']; ?>" <?php if($course_filter == $c['id']) echo "selected"; ?>>
                                <?php echo htmlspecialchars($c['course_name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </form>
            </div>
        </div>

        <!-- Attendance List -->
        <div class="col-md-8">
            <div class="card-astra p-5 shadow-lg">
                <h5 class="fw-bold mb-4">Verification Registry</h5>
                <div class="table-responsive">
                    <table class="table-astra">
                        <thead>
                            <tr>
                                <th>Temporal Stamp</th>
                                <th>Academic Module</th>
                                <th class="text-end">Status Token</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($attendance_query->num_rows > 0){ while($row = $attendance_query->fetch_assoc()){ ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-indigo"><?php echo date('D, M d, Y', strtotime($row['date'])); ?></div>
                                        <div class="small opacity-50 mt-1">⏰ Checked-in: <?php echo date('h:i A', strtotime($row['created_at'])); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge-astra badge-indigo" style="font-size: 10px;"><?php echo htmlspecialchars($row['course_name']); ?></span>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge-astra badge-success">PROFILE PRESENT</span>
                                    </td>
                                </tr>
                            <?php }} else { ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted p-5 opacity-50">
                                        <div class="fs-1 mb-3">📄</div>
                                        No telemetry found for the selected scope.
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div> <!-- End Container -->
</div> <!-- End Main -->
</div> <!-- End Wrapper -->
</body>
</html>
