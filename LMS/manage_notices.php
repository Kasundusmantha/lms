<?php
session_start();
include 'config.php';
require_role(['admin', 'teacher']);

$user_id = $_SESSION['user']['id'];
$role = $_SESSION['user']['role'];

$page_title = "Manage Notices";
include 'components/header.php';
include 'components/sidebar.php';

$message = "";

// Handle Delete Notice
if(isset($_GET['delete'])){
    if(!verify_csrf_token($_GET['csrf_token'] ?? '')){
        die("Security Check Failed (CSRF Token Invalid)");
    }
    $nid = intval($_GET['delete']);
    // Option: verify user owns the notice if security needed, but for simplicity admins/teachers can delete any or their own.
    if($_SESSION['user']['role'] == 'admin') {
        $conn->query("DELETE FROM notices WHERE id='$nid'");
    } else {
        $conn->query("DELETE FROM notices WHERE id='$nid' AND created_by='$user_id'");
    }
    $message = "<div class='alert alert-danger shadow-sm'>Notice Deleted Successfully!</div>";
}

// Handle Add Notice
if(isset($_POST['add'])){
    if(!verify_csrf_token($_POST['csrf_token'] ?? '')){
        die("Security Check Failed (CSRF Token Invalid)");
    }
    $title = $_POST['title'];
    $body = $_POST['message'];
    $course_id = ($_POST['course_id'] == 'global') ? NULL : intval($_POST['course_id']);
    
    $stmt = $conn->prepare("INSERT INTO notices (title, message, created_by, course_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $title, $body, $user_id, $course_id);
    $stmt->execute();
    
    $message = "<div class='alert alert-success shadow-sm'>Notice Broadcasted Successfully!</div>";
}

// Fetch Teacher/Admin Courses for the dropdown
if($_SESSION['user']['role'] == 'admin'){
    $my_courses = $conn->query("SELECT * FROM courses");
} else {
    $my_courses = $conn->query("SELECT * FROM courses WHERE teacher_id='$user_id'");
}

// Fetch Notices (Filtered History)
if($_SESSION['user']['role'] == 'admin') {
    // Admins see everything
    $notices = $conn->query("
        SELECT n.*, u.name as author, u.role as author_role, c.course_name 
        FROM notices n 
        JOIN users u ON n.created_by = u.id 
        LEFT JOIN courses c ON n.course_id = c.id
        ORDER BY n.created_at DESC
    ");
} else {
    // Teachers see their OWN notices OR official ADMIN notices
    $notices = $conn->query("
        SELECT n.*, u.name as author, u.role as author_role, c.course_name 
        FROM notices n 
        JOIN users u ON n.created_by = u.id 
        LEFT JOIN courses c ON n.course_id = c.id
        WHERE n.created_by = '$user_id' OR u.role = 'admin'
        ORDER BY n.created_at DESC
    ");
}
?>
<!-- MAIN CONTENT -->
<div class="main-astra w-100">
    <div class="header-astra mb-4 animate-up">
        <h4 class="fw-bold mb-0">📢 Communication Hub</h4>
    </div>

<div class="container-fluid animate-up" style="animation-delay: 0.1s;">

<div class="container mt-4">
    <h3 class="mb-4 fw-bold">📢 Manage Global Notices</h3>
    
    <?php echo $message; ?>

    <div class="row g-4">
        <div class="col-md-5">
            <div class="card-astra p-5 h-100 shadow-lg">
                <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
                    <span style="font-size: 20px;">🛡️</span> Broadcast New Notice
                </h5>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold opacity-75">Target Audience</label>
                        <select name="course_id" class="form-control-astra w-100" style="height: 52px;">
                            <option value="global">🌍 Global Announcement</option>
                            <?php while($c = $my_courses->fetch_assoc()){ ?>
                                <option value="<?php echo $c['id']; ?>">📚 <?php echo h($c['course_name']); ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold opacity-75">Notice Subject</label>
                        <input type="text" name="title" class="form-control-astra w-100" placeholder="e.g. Campus Holiday Update" required>
                    </div>

                    <div class="mb-5">
                        <label class="form-label small fw-bold opacity-75">Detailed Message</label>
                        <textarea name="message" class="form-control-astra w-100" rows="6" placeholder="Compose your announcement details here..." required></textarea>
                    </div>

                    <button type="submit" name="add" class="btn-astra w-100 py-3">
                        Broadcast Now 🚀
                    </button>
                </form>
            </div>
        </div>
        
        <div class="col-md-7">
            <div class="card-astra p-5 h-100 shadow-lg">
                <h5 class="fw-bold mb-4">Transmission History</h5>
                <div class="table-responsive">
                    <table class="table-astra">
                        <thead>
                            <tr>
                                <th>Info</th>
                                <th>Identity</th>
                                <th class="text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($notices->num_rows > 0){ while($n = $notices->fetch_assoc()){ ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo h($n['title']); ?></div>
                                        <div class="small opacity-50 mt-1"><?php echo date('M d, Y • H:i', strtotime($n['created_at'])); ?></div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <?php if($n['course_id']){ ?>
                                                <span class="badge-astra badge-success" style="font-size: 9px;"><?php echo h($n['course_name']); ?></span>
                                            <?php } else { ?>
                                                <span class="badge-astra badge-warning" style="font-size: 9px;">GLOBAL</span>
                                            <?php } ?>
                                        </div>
                                        <div class="small fw-semibold opacity-75">By <?php echo h($n['author']); ?></div>
                                    </td>
                                    <td class="text-end">
                                        <?php if($_SESSION['user']['role'] == 'admin' || $n['created_by'] == $user_id){ ?>
                                            <a href="?delete=<?php echo $n['id']; ?>&csrf_token=<?php echo generate_csrf_token(); ?>" class="btn btn-danger btn-sm rounded-3 px-3 shadow-sm" onclick="return confirm('Archive this official notice?');">
                                                Revoke
                                            </a>
                                        <?php } else { echo "<span class='opacity-25'>-</span>"; } ?>
                                    </td>
                                </tr>
                            <?php }} else { echo "<tr><td colspan='3' class='text-center text-muted p-5 opacity-50'>No transmitted history found.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div> <!-- End Container -->
</div> <!-- End Main -->
</div> <!-- End Wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
