<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user'])){
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user']['id'];
$role = $_SESSION['user']['role'];

$userData = $conn->query("SELECT * FROM users WHERE id='$user_id'");
$user = $userData->fetch_assoc();

$page_title = "Dashboard Overview";

// Fetch Notices (Filtered based on role)
if($role == 'student') {
    // Students see GLOBAL news OR news for courses they ARE IN
    $notices_query = $conn->query("
        SELECT n.*, u.name as author 
        FROM notices n 
        JOIN users u ON n.created_by = u.id 
        WHERE n.course_id IS NULL 
           OR n.course_id IN (SELECT course_id FROM enrollments WHERE student_id = '$user_id')
        ORDER BY n.created_at DESC 
        LIMIT 6
    ");
} elseif($role == 'teacher') {
    // Teachers see GLOBAL news OR news for courses THEY TEACH
    $notices_query = $conn->query("
        SELECT n.*, u.name as author, c.course_name 
        FROM notices n 
        JOIN users u ON n.created_by = u.id 
        LEFT JOIN courses c ON n.course_id = c.id
        WHERE n.course_id IS NULL 
           OR n.course_id IN (SELECT id FROM courses WHERE teacher_id = '$user_id')
           OR n.created_by = '$user_id'
        ORDER BY n.created_at DESC 
        LIMIT 6
    ");
} else {
    // Admins see EVERYTHING for monitoring
    $notices_query = $conn->query("
        SELECT n.*, u.name as author, c.course_name 
        FROM notices n 
        JOIN users u ON n.created_by = u.id 
        LEFT JOIN courses c ON n.course_id = c.id
        ORDER BY n.created_at DESC 
        LIMIT 6
    ");
}

/* ================= ADMIN ANALYTICS ================= */
if($role == "admin"){

    $total_users = $conn->query("SELECT COUNT(*) as t FROM users")->fetch_assoc()['t'];
    $total_students = $conn->query("SELECT COUNT(*) as t FROM users WHERE role='student'")->fetch_assoc()['t'];
    $total_teachers = $conn->query("SELECT COUNT(*) as t FROM users WHERE role='teacher'")->fetch_assoc()['t'];
    $total_courses = $conn->query("SELECT COUNT(*) as t FROM courses")->fetch_assoc()['t'];
    $total_assignments = $conn->query("SELECT COUNT(*) as t FROM assignments")->fetch_assoc()['t'];
    $total_submissions = $conn->query("SELECT COUNT(*) as t FROM submissions")->fetch_assoc()['t'];

    $course_stats = $conn->query("
        SELECT c.course_name,
               COUNT(e.student_id) as student_count
        FROM courses c
        LEFT JOIN enrollments e ON c.id=e.course_id
        GROUP BY c.id
    ");
}

/* ================= TEACHER ANALYTICS ================= */
if($role == "teacher"){

    $my_courses = $conn->query("SELECT COUNT(*) as t FROM courses WHERE teacher_id='$user_id'")->fetch_assoc()['t'];

    $my_students = $conn->query("
        SELECT COUNT(DISTINCT e.student_id) as t
        FROM enrollments e
        JOIN courses c ON e.course_id=c.id
        WHERE c.teacher_id='$user_id'
    ")->fetch_assoc()['t'];

    $my_submissions = $conn->query("
        SELECT COUNT(*) as t
        FROM submissions s
        JOIN assignments a ON s.assignment_id=a.id
        JOIN courses c ON a.course_id=c.id
        WHERE c.teacher_id='$user_id'
    ")->fetch_assoc()['t'];

    $chart_query = $conn->query("
        SELECT c.course_name,
               COUNT(e.student_id) as total_students
        FROM courses c
        LEFT JOIN enrollments e ON c.id=e.course_id
        WHERE c.teacher_id='$user_id'
        GROUP BY c.id
    ");

    $recent_subs = $conn->query("
        SELECT s.submitted_at, u.name, a.title, s.file_path 
        FROM submissions s
        JOIN users u ON s.student_id = u.id
        JOIN assignments a ON s.assignment_id = a.id
        JOIN courses c ON a.course_id = c.id
        WHERE c.teacher_id='$user_id'
        ORDER BY s.submitted_at DESC
        LIMIT 5
    ");

    $labels = [];
    $values = [];
    while($row = $chart_query->fetch_assoc()){
        $labels[] = $row['course_name'];
        $values[] = $row['total_students'];
    }

    $labels = json_encode($labels);
    $values = json_encode($values);
}

/* ================= STUDENT ANALYTICS ================= */
if($role == "student"){
    $my_courses = $conn->query("SELECT COUNT(*) as t FROM enrollments WHERE student_id='$user_id'")->fetch_assoc()['t'];
    $my_submissions = $conn->query("SELECT COUNT(*) as t FROM submissions WHERE student_id='$user_id'")->fetch_assoc()['t'];
    $quiz_attempts = $conn->query("SELECT COUNT(*) as t FROM results WHERE student_id='$user_id'")->fetch_assoc()['t'];
}
include 'components/header.php';
include 'components/sidebar.php';
?>
<!-- MAIN CONTENT AREA -->
<div class="main-astra w-100">
    <div class="header-astra mb-4 animate-up">
        <h4 class="fw-bold mb-0"><?php echo $page_title; ?></h4>
    </div>
<!-- Dashboard Content Starts here -->
<!-- GLOBAL NOTICES -->
<?php if($notices_query->num_rows > 0){ ?>
<div class="card-astra p-4 mb-5 animate-up" style="border-left: 5px solid var(--astra-warning) !important;">
    <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
        <span style="font-size: 20px;">📢</span> Latest Announcements
    </h5>
    <div class="row g-4">
        <?php while($n = $notices_query->fetch_assoc()){ ?>
        <div class="col-md-4">
            <div class="p-3 h-100 rounded-4" style="background: var(--astra-indigo-soft); border: 1px solid var(--border-color);">
                <?php if(isset($n['course_name']) && $n['course_name']){ ?>
                    <span class="badge-astra badge-success mb-2 d-inline-block"><?php echo htmlspecialchars($n['course_name']); ?></span>
                <?php } ?>
                <h6 class="fw-bold mb-2 text-indigo"><?php echo htmlspecialchars($n['title']); ?></h6>
                <p class="small mb-3 opacity-75"><?php echo nl2br(htmlspecialchars($n['message'])); ?></p>
                <div class="d-flex align-items-center gap-2 mt-auto">
                    <div class="rounded-circle" style="width: 24px; height: 24px; background: var(--astra-indigo); color: white; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold;">
                        <?php echo strtoupper(substr($n['author'], 0, 1)); ?>
                    </div>
                    <small class="text-muted" style="font-size: 10px;">By <?php echo htmlspecialchars($n['author']); ?> • <?php echo date('M d', strtotime($n['created_at'])); ?></small>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>
</div>
<?php } ?>

<!-- ADMIN ANALYTICS SUMMARY -->
<?php if($role=="admin"){ ?>
<div class="row g-4 mb-5 animate-up" style="animation-delay: 0.1s;">
    <div class="col-md-3">
        <div class="card-astra p-4 d-flex flex-row align-items-center gap-3" style="border-bottom: 4px solid var(--astra-indigo) !important;">
            <div class="fs-1">👥</div>
            <div>
                <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 10px; letter-spacing: 1px;">Total Users</small>
                <h3 class="fw-bold mb-0"><?php echo number_format($total_users);?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-astra p-4 d-flex flex-row align-items-center gap-3" style="border-bottom: 4px solid var(--astra-success) !important;">
            <div class="fs-1">👨‍🎓</div>
            <div>
                <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 10px; letter-spacing: 1px;">Students</small>
                <h3 class="fw-bold mb-0"><?php echo number_format($total_students);?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-astra p-4 d-flex flex-row align-items-center gap-3" style="border-bottom: 4px solid var(--astra-warning) !important;">
            <div class="fs-1">👨‍🏫</div>
            <div>
                <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 10px; letter-spacing: 1px;">Teachers</small>
                <h3 class="fw-bold mb-0"><?php echo number_format($total_teachers);?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-astra p-4 d-flex flex-row align-items-center gap-3" style="border-bottom: 4px solid var(--astra-info) !important;">
            <div class="fs-1">📚</div>
            <div>
                <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 10px; letter-spacing: 1px;">Courses</small>
                <h3 class="fw-bold mb-0"><?php echo number_format($total_courses);?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row mt-5">
    <div class="col-md-8">
        <div class="card-astra p-5 h-100 animate-up" style="animation-delay: 0.2s;">
            <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
                <span style="font-size: 20px;">📈</span> Course Distribution
            </h5>
            <div style="display:flex; flex-direction:column; gap:18px;">
                <?php 
                $course_stats->data_seek(0);
                while($row=$course_stats->fetch_assoc()){ 
                    $percent = ($total_students > 0) ? round(($row['student_count'] / $total_students) * 100) : 0;
                ?>
                <div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <span style="font-weight:600; font-size:14px; color:var(--text-main);"><?php echo htmlspecialchars($row['course_name']);?></span>
                        <span class="badge-astra badge-success"><?php echo $row['student_count'];?> Students</span>
                    </div>
                    <div style="height:10px; background:var(--border-color); border-radius:10px; overflow:hidden;">
                        <div style="height:100%; width:<?php echo $percent; ?>%; background:var(--astra-indigo); border-radius:10px; transition:width 1s ease;"></div>
                    </div>
                    <div style="font-size:11px; color:var(--text-muted); margin-top:4px; text-align:right;"><?php echo $percent; ?>%</div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-astra p-5 h-100 text-white animate-up" style="background: var(--astra-indigo) !important; animation-delay: 0.3s;">
            <h5 class="fw-bold mb-4">Quick Overview</h5>
            <p class="small opacity-75 mb-4 leading-relaxed">Your LMS currently manages <strong><?php echo $total_courses; ?></strong> active courses and a community of <strong><?php echo $total_users; ?></strong> members.</p>
            <div class="divider opacity-20 mb-4" style="height: 1px; background: white;"></div>
            <div class="mb-4">
                <small class="d-block opacity-70 text-uppercase fw-bold" style="font-size: 9px; letter-spacing: 1.5px;">Total Assignments</small>
                <h2 class="fw-bold mb-0"><?php echo number_format($total_assignments); ?></h2>
            </div>
            <div>
                <small class="d-block opacity-70 text-uppercase fw-bold" style="font-size: 9px; letter-spacing: 1.5px;">Student Submissions</small>
                <h2 class="fw-bold mb-0"><?php echo number_format($total_submissions); ?></h2>
            </div>
        </div>
    </div>
</div>
<?php } ?>

<!-- TEACHER ANALYTICS -->
<?php if($role=="teacher"){ ?>
<div class="row g-4 mb-5 animate-up">
    <div class="col-md-4">
        <a href="assignments.php" class="btn-astra w-100 py-3 d-flex flex-column gap-1">
            <span style="font-size: 18px;">📄</span>
            <span>Manage Assignments</span>
        </a>
    </div>
    <div class="col-md-4">
        <a href="quiz.php" class="btn-astra w-100 py-3 d-flex flex-column gap-1" style="background: var(--astra-success)">
            <span style="font-size: 18px;">📝</span>
            <span>Create New Quiz</span>
        </a>
    </div>
    <div class="col-md-4">
        <a href="view_results.php" class="btn-astra w-100 py-3 d-flex flex-column gap-1" style="background: var(--astra-info)">
            <span style="font-size: 18px;">📊</span>
            <span>Student Results</span>
        </a>
    </div>
</div>

<div class="row g-4 animate-up" style="animation-delay: 0.1s;">
    <div class="col-md-4">
        <div class="card-astra p-4 d-flex align-items-center gap-3" style="border-left: 5px solid var(--astra-indigo) !important;">
            <div class="fs-2 text-indigo">📚</div>
            <div>
                <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 9px; letter-spacing: 1px;">My Courses</small>
                <h3 class="fw-bold mb-0"><?php echo $my_courses;?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-astra p-4 d-flex align-items-center gap-3" style="border-left: 5px solid var(--astra-success) !important;">
            <div class="fs-2 text-success">👨‍🎓</div>
            <div>
                <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 9px; letter-spacing: 1px;">Total Students</small>
                <h3 class="fw-bold mb-0"><?php echo $my_students;?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-astra p-4 d-flex align-items-center gap-3" style="border-left: 5px solid var(--astra-warning) !important;">
            <div class="fs-2 text-warning">📥</div>
            <div>
                <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 9px; letter-spacing: 1px;">Submissions</small>
                <h3 class="fw-bold mb-0"><?php echo $my_submissions;?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-7">
        <div class="card-astra p-5 h-100 shadow-lg">
            <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
                <span style="font-size: 20px;">📥</span> Recent Submissions
            </h5>
            <div class="table-responsive">
                <table class="table-astra">
                    <thead>
                        <tr>
                            <th>Student Identity</th>
                            <th>Assessment Title</th>
                            <th>Temporal Stamp</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if($recent_subs->num_rows > 0){ while($rs = $recent_subs->fetch_assoc()){ ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-indigo"><?php echo h($rs['name']); ?></div>
                                <div class="small opacity-50">Verified Profile</div>
                            </td>
                            <td class="fw-semibold opacity-75"><?php echo h($rs['title']); ?></td>
                            <td>
                                <div class="small fw-bold"><?php echo date('M d', strtotime($rs['submitted_at'])); ?></div>
                                <div class="small opacity-50">⏰ <?php echo date('H:i', strtotime($rs['submitted_at'])); ?></div>
                            </td>
                            <td class="text-end">
                                <a href="<?php echo $rs['file_path']; ?>" class="btn-astra btn-astra-outline py-2 px-3 shadow-sm" style="font-size: 11px;" target="_blank">
                                    View Repository 📄
                                </a>
                            </td>
                        </tr>
                    <?php } } else { ?>
                        <tr><td colspan="4" class="text-center text-muted p-5 opacity-50">No incoming deliverables found.</td></tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card-astra p-5 h-100 shadow-lg">
            <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
                <span style="font-size: 20px;">📊</span> Demographic Distribution
            </h5>
            <div class="p-3 bg-white rounded-4 shadow-sm">
                <canvas id="courseChart" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('courseChart'),{
type:'bar',
data:{
    labels:<?php echo $labels;?>,
    datasets:[{
        label:'Students',
        data:<?php echo $values;?>,
        backgroundColor: 'rgba(78, 115, 223, 0.8)',
        borderRadius: 8
    }]
},
options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});
</script>
<?php } ?>

<!-- STUDENT ANALYTICS -->
<?php if($role=="student"){ ?>
<div class="row g-4 animate-up">
    <div class="col-md-4">
        <div class="card-astra p-5 text-center" style="border-bottom: 5px solid var(--astra-indigo) !important;">
            <div class="fs-1 mb-2">📚</div>
            <div class="text-muted text-uppercase fw-bold mb-1" style="font-size: 10px; letter-spacing: 1px;">My Enrolled Courses</div>
            <h2 class="fw-bold mb-0"><?php echo $my_courses;?></h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-astra p-5 text-center" style="border-bottom: 5px solid var(--astra-success) !important;">
            <div class="fs-1 mb-2">📤</div>
            <div class="text-muted text-uppercase fw-bold mb-1" style="font-size: 10px; letter-spacing: 1px;">Total Submissions</div>
            <h2 class="fw-bold mb-0"><?php echo $my_submissions;?></h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-astra p-5 text-center" style="border-bottom: 5px solid var(--astra-warning) !important;">
            <div class="fs-1 mb-2">📝</div>
            <div class="text-muted text-uppercase fw-bold mb-1" style="font-size: 10px; letter-spacing: 1px;">Quiz Attempts</div>
            <h2 class="fw-bold mb-0"><?php echo $quiz_attempts;?></h2>
        </div>
    </div>
</div>
<?php } ?>

</div>

<!-- Dashboard Specific Scripts -->
</div> <!-- End Main -->
</div> <!-- End Wrapper -->
</body>
</html>