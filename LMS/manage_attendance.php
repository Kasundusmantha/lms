<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'teacher'){
    header("Location: index.php");
    exit();
}

$teacher_id = $_SESSION['user']['id'];
$today = date('Y-m-d');

// --- SELF-REPAIR BLOCK (Ensures columns exist before use) ---
$check_cols = $conn->query("SHOW COLUMNS FROM users LIKE 'parent_phone'");
if($check_cols->num_rows == 0) {
    try {
        $conn->query("ALTER TABLE users ADD COLUMN phone VARCHAR(255) DEFAULT NULL, ADD COLUMN parent_phone VARCHAR(255) DEFAULT NULL");
    } catch (Exception $e) { /* Already exists or locked */ }
}
// -----------------------------------------------------------

// Fetch courses taught by this teacher
$courses = $conn->query("SELECT * FROM courses WHERE teacher_id='$teacher_id'");

$selected_course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;
$qr_url = null;
$attendance_list = null;

if($selected_course_id){
    // Generate the URL that students will scan
// $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    if(isset($site_url)) {
        // We use your manual IP from config.php
        $base_url = $site_url;
    } else {
        $host = $_SERVER['HTTP_HOST']; 
        // If accessing via localhost or a broken IP, we force-detect the real LAN IP
        if($host == 'localhost' || $host == '127.0.0.1' || strpos($host, '169.254') !== false || $host == '::1') {
            $ip_info = shell_exec('ipconfig');
            if(preg_match('/IPv4 Address.*?: (192\.168\.\d+\.\d+|10\.\d+\.\d+\.\d+)/', $ip_info, $matches)) {
                $host = $matches[1];
            } else {
                $host = getHostByName(getHostName()); 
            }
        }
        $base_url = "http://" . $host . "/LMS";
    }
    
    $target_url = $base_url . "/mark_attendance.php?course_id=" . $selected_course_id . "&d=" . $today;
    
    // Quick free QR code API
    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($target_url);
    
    // Fetch students who have already checked in today
    $attendance_list = $conn->query("
        SELECT u.id, u.name, u.email, a.status 
        FROM attendance a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.course_id='$selected_course_id' AND a.date='$today'
    ");

    // Fetch students who are ENROLLED but NOT PRESENT today
    $absentees = $conn->query("
        SELECT u.id, u.name, u.email, u.parent_phone 
        FROM enrollments e 
        JOIN users u ON e.student_id = u.id 
        WHERE e.course_id = '$selected_course_id' 
        AND u.id NOT IN (SELECT user_id FROM attendance WHERE course_id = '$selected_course_id' AND date = '$today')
    ");

    // Get Course Name for message
    $cName = $conn->query("SELECT course_name FROM courses WHERE id='$selected_course_id'")->fetch_assoc()['course_name'];
}
?>
<?php
include 'components/header.php';
include 'components/sidebar.php';
?>

<!-- MAIN CONTENT -->
<div class="main-astra w-100">
    <div class="header-astra mb-4 animate-up">
        <h4 class="fw-bold mb-0">📱 Attendance Infrastructure</h4>
    </div>

<div class="container-fluid animate-up" style="animation-delay: 0.1s;">

<div class="d-flex justify-content-between align-items-center mb-5">
    <h2 class="fw-bold mb-0">📡 Session Broadcasting</h2>
</div>

<div class="row g-4">
    <!-- Left Panel: QR Code -->
    <div class="col-md-5 mb-4 text-center">
        <div class="card-astra p-5 h-100 shadow-lg">
            <h5 class="fw-bold mb-4 d-flex align-items-center justify-content-center gap-2">
                <span style="font-size: 20px;">📘</span> Module Selection
            </h5>
            <form method="get" class="mb-5">
                <select name="course_id" class="form-control-astra w-100" required onchange="this.form.submit()" style="height: 52px;">
                    <option value="">-- Select Active Course --</option>
                    <?php while($c = $courses->fetch_assoc()){ 
                        $sel = ($selected_course_id == $c['id']) ? "selected" : "";
                        echo "<option value='".$c['id']."' $sel>".h($c['course_name'])."</option>";
                    } ?>
                </select>
            </form>

            <?php if($selected_course_id){ ?>
                <div class="p-4 rounded-4 bg-white shadow-sm mb-4">
                    <h6 class="fw-bold mb-3 text-indigo">Digital Presence Token</h6>
                    <div class="qr-container p-3 mb-3 border rounded-4 bg-light">
                        <img src="<?php echo $qr_url; ?>" alt="Attendance QR" class="img-fluid rounded-3" style="max-height: 300px;">
                    </div>
                    <p class="text-muted small">Broadcast this session token to initialize student verification.</p>
                </div>
            <?php } else { ?>
                <div class="card-astra p-5 text-center opacity-50 border-0" style="background: var(--astra-indigo-soft) !important;">
                    <div class="fs-1 mb-3">📡</div>
                    <h5 class="fw-bold">No Active Session</h5>
                    <p class="small">Choose a module to generate the digital session token.</p>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- Right Panel: Live Attendance & Absentees -->
    <div class="col-md-7 mb-4">
        <div class="card-astra p-5 mb-5 shadow-lg">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h5 class="fw-bold mb-1">📋 Live Verification Registry</h5>
                    <p class="text-muted small mb-0">Synchronized every 3s via Digital Pulse</p>
                </div>
                <?php if($selected_course_id){ ?>
                    <span class="badge-astra badge-success">LIVE BROADCAST</span>
                <?php } ?>
            </div>

            <div class="table-responsive">
                <table class="table-astra">
                    <thead>
                        <tr>
                            <th>Student Identity</th>
                            <th class="text-end">Verification Status</th>
                        </tr>
                    </thead>
                    <tbody id="attendance-list">
                        <?php if(!$selected_course_id){ ?>
                            <tr><td colspan="2" class="text-center text-muted p-5 opacity-50">Choose a course to initialize telemetry.</td></tr>
                        <?php } else { ?>
                            <?php if($attendance_list && $attendance_list->num_rows > 0){ 
                                    while($row = $attendance_list->fetch_assoc()){ ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo h($row['name']); ?></div>
                                        <div class="small opacity-50"><?php echo h($row['email']); ?></div>
                                    </td>
                                    <td class="text-end"><span class="badge-astra badge-success">VERIFIED PRESENT</span></td>
                                </tr>
                            <?php } } else { ?>
                                <tr><td colspan="2" class="text-center text-muted p-5 opacity-50">Awaiting student synchronization tokens.</td></tr>
                            <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if($selected_course_id){ ?>
            <div class="card-astra p-5 shadow-lg" style="border-left: 5px solid var(--astra-danger) !important;">
                <h5 class="fw-bold text-danger mb-4 d-flex align-items-center gap-2">
                    <span style="font-size: 20px;">🚨</span> Disconnected Profiles (Absentees)
                </h5>
                <div class="table-responsive">
                    <table class="table-astra">
                        <thead>
                            <tr>
                                <th>Identity</th>
                                <th class="text-end">Notify Authority</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($absentees && $absentees->num_rows > 0){ 
                                    while($abs = $absentees->fetch_assoc()){ 
                                        $parent_no = $abs['parent_phone'];
                                        $wa_btn = "<span class='opacity-25'>No Digital Contact</span>";
                                        if(!empty($parent_no)) {
                                            $wa_msg = "Official LMS Alert: " . $abs['name'] . " is currently absent from their " . $cName . " session (" . date('D, M d, Y') . "). Please verify their current status.";
                                            $wa_url = "https://wa.me/" . preg_replace('/[^0-9]/', '', $parent_no) . "?text=" . urlencode($wa_msg);
                                            $wa_btn = "<a href='$wa_url' target='_blank' class='btn-astra px-4 py-2' style='background:#25D366; font-size: 11px;'>Notify Parent 📲</a>";
                                        }
                            ?>
                                <tr>
                                    <td class="fw-bold"><?php echo h($abs['name']); ?></td>
                                    <td class="text-end"><?php echo $wa_btn; ?></td>
                                </tr>
                            <?php } } else { ?>
                                <tr><td colspan="2" class="text-center text-muted p-5 opacity-50">100% Convergence Achieved. All students verified. ✨</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php } ?>
    </div>
</div>
        </div>
    </div>

</div> <!-- End Container -->
</div> <!-- End Main -->
</div> <!-- End Wrapper -->
</body>
</html>
