<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user']) || ($_SESSION['user']['role'] != 'admin' && $_SESSION['user']['role'] != 'teacher')){
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user']['id'];
$role = $_SESSION['user']['role'];

// Filter Logic
$course_filter = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;
$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$name_filter = isset($_GET['student_name']) ? $_GET['student_name'] : '';

$where_clause = "WHERE a.date = '$date_filter'";
if($course_filter) {
    $where_clause .= " AND a.course_id = '$course_filter'";
} elseif($role == 'teacher') {
    // If no course selected, teacher should only see their own courses' history
    $where_clause .= " AND a.course_id IN (SELECT id FROM courses WHERE teacher_id = '$user_id')";
}

if(!empty($name_filter)) {
    $name_filter_safe = $conn->real_escape_string($name_filter);
    $where_clause .= " AND u.name LIKE '%$name_filter_safe%'";
}

// Fetch Attendance Records
$records = $conn->query("
    SELECT a.*, u.name as student_name, u.email as student_email, c.course_name 
    FROM attendance a 
    JOIN users u ON a.user_id = u.id 
    JOIN courses c ON a.course_id = c.id 
    $where_clause 
    ORDER BY c.course_name ASC, u.name ASC
");

// Fetch Courses for Filter
if($role == 'admin'){
    $courses = $conn->query("SELECT * FROM courses ORDER BY course_name ASC");
} else {
    $courses = $conn->query("SELECT * FROM courses WHERE teacher_id = '$user_id' ORDER BY course_name ASC");
}
?>
<?php
include 'components/header.php';
include 'components/sidebar.php';
?>

<!-- MAIN CONTENT -->
<div class="main-astra w-100">
    <div class="header-astra mb-4 animate-up no-print">
        <h4 class="fw-bold mb-0">📊 Attendance Analytical Reports</h4>
    </div>

<div class="container-fluid animate-up" style="animation-delay: 0.1s;">
    <div class="d-flex justify-content-between align-items-center mb-5 no-print">
        <h2 class="fw-bold mb-0">📈 Historical Verification Log</h2>
        <button onclick="window.print()" class="btn-astra py-2 px-4 shadow-sm" style="font-size: 12px; background: var(--bg-card); color: var(--text-main); border: 1px solid var(--border-color);">
            🖨️ Generate Physical Report
        </button>
    </div>

    <!-- Filters -->
    <div class="card-astra p-5 mb-5 shadow-lg no-print">
        <h6 class="fw-bold mb-4 opacity-75">Intelligence Filters</h6>
        <form method="get" class="row g-4">
            <div class="col-md-4">
                <label class="form-label small fw-bold opacity-50">Module Scope</label>
                <select name="course_id" class="form-control-astra w-100" onchange="this.form.submit()" style="height: 52px;">
                    <option value="">-- All Active Modules --</option>
                    <?php while($c = $courses->fetch_assoc()){ ?>
                        <option value="<?php echo $c['id']; ?>" <?php if($course_filter == $c['id']) echo "selected"; ?>>
                            <?php echo h($c['course_name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold opacity-50">Temporal Marker (Date)</label>
                <input type="date" name="date" class="form-control-astra w-100" value="<?php echo $date_filter; ?>" onchange="this.form.submit()" style="height: 52px;">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold opacity-50">Profile Search</label>
                <div class="d-flex gap-2">
                    <input type="text" name="student_name" class="form-control-astra w-100" placeholder="e.g. Alexander Pierce" value="<?php echo h($name_filter); ?>" style="height: 52px;">
                    <button class="btn-astra px-4 shadow-sm" type="submit">🔍</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Results Table -->
    <div class="card-astra p-5 shadow-lg">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0">Verified Presence Registry</h5>
            <span class="badge-astra badge-indigo" style="font-size: 11px;"><?php echo $records->num_rows; ?> Profiles Verified</span>
        </div>
        
        <div class="table-responsive">
            <table class="table-astra">
                <thead>
                    <tr>
                        <th>Identity Profile</th>
                        <th>Module Engagement</th>
                        <th>Temporal Marker</th>
                        <th class="text-end">Verification Token</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($records->num_rows > 0){ while($row = $records->fetch_assoc()){ ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-indigo"><?php echo h($row['student_name']); ?></div>
                                <div class="small opacity-50 mt-1"><?php echo h($row['student_email']); ?></div>
                            </td>
                            <td>
                                <div class="badge-astra badge-indigo" style="font-size: 9px;"><?php echo h($row['course_name']); ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold" style="font-size: 13px;"><?php echo date('M d, Y', strtotime($row['date'])); ?></div>
                            </td>
                            <td class="text-end">
                                <span class="badge-astra badge-success" style="font-size: 10px;">PRESENT: <?php echo date('h:i A', strtotime($row['created_at'])); ?></span>
                            </td>
                        </tr>
                    <?php }} else { ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted p-5 opacity-50">
                                <div class="fs-1 mb-3">📄</div>
                                No verification tokens found for the selected intelligence context.
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div> <!-- End Container -->
</div> <!-- End Main -->
</div> <!-- End Wrapper -->
</body>
</html>
