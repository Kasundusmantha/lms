<?php
// sidebar.php - Astra Premium Navigation v2
$user_role = $_SESSION['user']['role'];
$_sid      = $_SESSION['user']['id'];

// Fetch full user row from DB if not already available (profile_pic, xp, etc.)
if(!isset($user) || !is_array($user) || !array_key_exists('profile_pic', $user)) {
    $_uq = $conn->query("SELECT * FROM users WHERE id='$_sid' LIMIT 1");
    $user = ($_uq && $_uq->num_rows > 0) ? $_uq->fetch_assoc() : [];
}

$user_name = $user['name']  ?? $_SESSION['user']['name'] ?? 'User';
$user_xp   = $user['xp']   ?? 0;
$cur_page  = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar-astra">


    <!-- User Profile Card -->
    <div style="padding: 12px;">
        <div style="background: var(--astra-indigo-soft); border-radius: 12px; padding: 12px; border: 1px solid var(--border-color);">
            <div style="display:flex; align-items:center; gap:10px;">
                <?php
                $sb_pic = $user['profile_pic'] ?? '';
                $sb_has_pic = !empty($sb_pic) && file_exists($sb_pic);
                if($sb_has_pic): ?>
                    <img src="<?php echo h($sb_pic); ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid var(--astra-indigo);">
                <?php else: ?>
                    <div style="width:36px; height:36px; border-radius:50%; background: var(--astra-indigo); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; flex-shrink:0;">
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <div style="overflow:hidden;">
                    <div style="font-weight:600; font-size:13px; color:var(--text-main); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        <?php echo htmlspecialchars($user_name); ?>
                    </div>
                    <div style="font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:var(--astra-indigo); opacity:0.8;">
                        <?php echo ucfirst($user_role); ?>
                    </div>
                </div>
            </div>
            <?php if($user_xp > 0){ ?>
            <div style="margin-top:8px; display:flex; justify-content:space-between; align-items:center; font-size:10px;">
                <span style="color:var(--text-muted);">XP Points</span>
                <span style="font-weight:700; color:var(--astra-indigo);">⭐ <?php echo $user_xp; ?></span>
            </div>
            <?php } ?>
        </div>
    </div>

    <!-- Navigation -->
    <nav style="flex:1; overflow-y:auto; padding: 8px 0;">

        <!-- General -->
        <div style="padding: 12px 20px 4px; font-size:9px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:var(--text-muted); opacity:0.6;">
            General
        </div>

        <a href="dashboard.php" class="nav-link <?php echo $cur_page=='dashboard.php'?'active':''; ?>">
            <span>🏠</span> Dashboard
        </a>

        <!-- Admin Section -->
        <?php if($user_role == 'admin'){ ?>
        <div style="padding: 12px 20px 4px; font-size:9px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:var(--text-muted); opacity:0.6; margin-top:4px;">
            Administration
        </div>
        <a href="manage_users.php" class="nav-link <?php echo $cur_page=='manage_users.php'?'active':''; ?>">
            <span>👥</span> Users
        </a>
        <a href="add_course.php" class="nav-link <?php echo $cur_page=='add_course.php'?'active':''; ?>">
            <span>📚</span> Courses
        </a>
        <a href="enroll_student.php" class="nav-link <?php echo $cur_page=='enroll_student.php'?'active':''; ?>">
            <span>🎓</span> Enrollment
        </a>
        <a href="manage_notices.php" class="nav-link <?php echo $cur_page=='manage_notices.php'?'active':''; ?>">
            <span>📢</span> Notices
        </a>
        <a href="attendance_report.php" class="nav-link <?php echo $cur_page=='attendance_report.php'?'active':''; ?>">
            <span>📊</span> Reports
        </a>
        <?php } ?>

        <!-- Student Section -->
        <?php if($user_role == 'student'){ ?>
        <div style="padding: 12px 20px 4px; font-size:9px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:var(--text-muted); opacity:0.6; margin-top:4px;">
            My Learning
        </div>
        <a href="my_courses.php" class="nav-link <?php echo $cur_page=='my_courses.php'?'active':''; ?>">
            <span>📖</span> My Courses
        </a>
        <a href="submit_assignment.php" class="nav-link <?php echo $cur_page=='submit_assignment.php'?'active':''; ?>">
            <span>📤</span> Assignments
        </a>
        <a href="take_quiz.php" class="nav-link <?php echo $cur_page=='take_quiz.php'?'active':''; ?>">
            <span>📝</span> Quizzes
        </a>
        <a href="notes.php" class="nav-link <?php echo $cur_page=='notes.php'?'active':''; ?>">
            <span>📚</span> Resources
        </a>
        <a href="my_attendance.php" class="nav-link <?php echo $cur_page=='my_attendance.php'?'active':''; ?>">
            <span>📅</span> Attendance
        </a>
        <a href="my_results.php" class="nav-link <?php echo $cur_page=='my_results.php'?'active':''; ?>">
            <span>🏆</span> My Results
        </a>
        <?php } ?>

        <!-- Teacher Section -->
        <?php if($user_role == 'teacher'){ ?>
        <div style="padding: 12px 20px 4px; font-size:9px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:var(--text-muted); opacity:0.6; margin-top:4px;">
            Teaching
        </div>
        <a href="assignments.php" class="nav-link <?php echo $cur_page=='assignments.php'?'active':''; ?>">
            <span>📄</span> Assignments
        </a>
        <a href="quiz.php" class="nav-link <?php echo $cur_page=='quiz.php'?'active':''; ?>">
            <span>✍️</span> Manage Quizzes
        </a>
        <a href="notes.php" class="nav-link <?php echo $cur_page=='notes.php'?'active':''; ?>">
            <span>📚</span> Resources
        </a>
        <a href="manage_attendance.php" class="nav-link <?php echo $cur_page=='manage_attendance.php'?'active':''; ?>">
            <span>📱</span> QR Attendance
        </a>
        <a href="view_results.php" class="nav-link <?php echo $cur_page=='view_results.php'?'active':''; ?>">
            <span>📊</span> View Results
        </a>
        <?php } ?>

        <!-- Common -->
        <div style="padding: 12px 20px 4px; font-size:9px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:var(--text-muted); opacity:0.6; margin-top:4px;">
            Account
        </div>
        <a href="profile.php" class="nav-link <?php echo $cur_page=='profile.php'?'active':''; ?>">
            <span>👤</span> My Profile
        </a>

    </nav>

    <!-- Footer: Theme + Logout -->
    <div style="padding: 14px 12px; border-top: 1px solid var(--border-color); flex-shrink:0;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; padding: 0 6px;">
            <span style="font-size:12px; font-weight:500; color:var(--text-muted);">Dark Mode</span>
            <div id="themeToggle" class="theme-switch-astra">
                <div class="toggle"></div>
            </div>
        </div>
        <a href="logout.php" style="display:block; text-align:center; padding: 9px; background: rgba(255,55,95,0.08); color: #FF375F; border-radius:10px; font-size:12px; font-weight:700; text-decoration:none; transition:all 0.2s;">
            🚪 Logout
        </a>
    </div>

</div>

<script>
(function(){
    // Sync toggle visual on load
    const toggle = document.getElementById('themeToggle');
    if(!toggle) return;
    toggle.addEventListener('click', function(){
        const cur = document.documentElement.getAttribute('data-theme');
        const next = cur === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
    });
})();
</script>
