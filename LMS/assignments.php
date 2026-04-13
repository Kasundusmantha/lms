<?php
session_start();
include 'config.php';
require_role('teacher');

$teacher_id = $_SESSION['user']['id'];
$role = $_SESSION['user']['role'];

$page_title = "Manage Assignments";
include 'components/header.php';
include 'components/sidebar.php';

$message = "";

/* ===== DELETE ASSIGNMENT ===== */
if(isset($_GET['delete'])){
    if(!verify_csrf_token($_GET['csrf_token'] ?? '')){
        die("Security Check Failed (CSRF Token Invalid)");
    }
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM assignments WHERE id='$id'");
    $message = "<div class='alert alert-danger shadow-sm'>Assignment deleted successfully.</div>";
}

/* ===== UPLOAD ASSIGNMENT ===== */
if(isset($_POST['upload'])){
    if(!verify_csrf_token($_POST['csrf_token'] ?? '')){
        die("Security Check Failed (CSRF Token Invalid)");
    }
    $title = $_POST['title'];
    $start_date = $_POST['start_date'];
    $deadline = $_POST['deadline'];
    $course_id = intval($_POST['course_id']);

    if(isset($_FILES['file']) && $_FILES['file']['error'] == 0){
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'docx', 'doc', 'zip', 'jpg', 'png', 'ppt', 'pptx', 'xls', 'xlsx'];
        
        if(!in_array($ext, $allowed)){
            $message = "<div class='alert alert-danger shadow-sm'>Error: File type not allowed. Please use PDF, Word, Zip, or Images.</div>";
        } else {
            $fileName = time()."_".preg_replace("/[^a-zA-Z0-9.]/", "_", $_FILES['file']['name']);
            $targetPath = __DIR__."/uploads/".$fileName;
            $savePath = "uploads/".$fileName;

        if(!is_dir('uploads')) mkdir('uploads');

        if(move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)){
            $stmt = $conn->prepare("INSERT INTO assignments (title, file_path, start_date, deadline, course_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $title, $savePath, $start_date, $deadline, $course_id);
            $stmt->execute();

            // BROADCAST TO NOTICE BOARD
            $cData = $conn->query("SELECT course_name FROM courses WHERE id='$course_id'")->fetch_assoc();
            $cName = $cData['course_name'] ?? 'Course';
            $formattedStart = date('D, M d, Y - h:i A', strtotime($start_date));
            $formattedDeadline = date('D, M d, Y - h:i A', strtotime($deadline));
            
            $noticeTitle = "📂 New Assignment: $title";
            $noticeMsg = "A new assignment has been posted for **$cName**.\n\n✨ **Start Date:** $formattedStart\n⏰ **Deadline:** $formattedDeadline\n\nPlease check your portal to download the instructions.";
            
            $stmtNotice = $conn->prepare("INSERT INTO notices (title, message, created_by, course_id) VALUES (?, ?, ?, ?)");
            $stmtNotice->bind_param("ssii", $noticeTitle, $noticeMsg, $teacher_id, $course_id);
            $stmtNotice->execute();

            $message = "<div class='alert alert-success shadow-sm'>Assignment created & Broadcasted successfully!</div>";
        }
        }
    } else {
        $message = "<div class='alert alert-warning shadow-sm'>Please select a valid file to upload.</div>";
    }
}

/* ===== DATA FETCHING ===== */
$courses = $conn->query("SELECT * FROM courses WHERE teacher_id='$teacher_id'");
$assignments = $conn->query("
    SELECT a.*, c.course_name 
    FROM assignments a 
    JOIN courses c ON a.course_id = c.id 
    WHERE c.teacher_id = '$teacher_id' 
    ORDER BY a.id DESC
");
?>
<!-- MAIN CONTENT -->
<div class="main-astra w-100">
    <div class="header-astra mb-4 animate-up">
        <h4 class="fw-bold mb-0">📂 Assignment Management</h4>
    </div>

<div class="container-fluid animate-up" style="animation-delay: 0.1s;">

<div class="container mt-4" style="max-width: 1000px;">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h2 class="fw-bold mb-0">✨ Create New Task</h2>
        <a href="dashboard.php" class="btn-astra btn-astra-outline py-2 px-4 shadow-sm">
            ⬅ Return to Dashboard
        </a>
    </div>

    <?php echo $message; ?>

    <!-- ADD ASSIGNMENT FORM -->
    <div class="card-astra p-5 mb-5 shadow-lg">
        <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
            <span style="font-size: 20px;">📂</span> Project Provisioning
        </h5>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label small fw-bold opacity-75">Target Course</label>
                    <select name="course_id" class="form-control-astra w-100" required style="height: 52px;">
                        <option value="">-- Choose Course --</option>
                        <?php while($c = $courses->fetch_assoc()){ ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo h($c['course_name']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold opacity-75">Opening Date</label>
                    <input type="text" name="start_date" id="startPicker" class="form-control-astra w-100" placeholder="Select Opening Time..." required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold opacity-75">Closing Deadline</label>
                    <input type="text" name="deadline" id="deadlinePicker" class="form-control-astra w-100" placeholder="Select Deadline..." required>
                </div>
                <div class="col-md-12">
                    <label class="form-label small fw-bold opacity-75">Assignment Title</label>
                    <input type="text" name="title" class="form-control-astra w-100" placeholder="e.g. Theoretical Physics Mid-Term" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label small fw-bold opacity-75">Resource Materials (.pdf, .zip, .docx)</label>
                    <input type="file" name="file" class="form-control-astra w-100 py-3" required>
                </div>
                <div class="col-md-12 text-end">
                    <button type="submit" name="upload" class="btn-astra px-5 py-3 shadow-lg">
                        Finalize & Broadcast 🚀
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ASSIGNMENT LIST -->
    <div class="card-astra p-5 shadow-lg">
        <h5 class="fw-bold mb-4">Active Deliverables</h5>
        <div class="table-responsive">
            <table class="table-astra">
                <thead>
                    <tr>
                        <th>Module</th>
                        <th>Opening Window</th>
                        <th>Enforcement Deadline</th>
                        <th class="text-end">Management</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($assignments->num_rows > 0){ while($row = $assignments->fetch_assoc()){ ?>
                        <tr>
                            <td>
                                <div class="badge-astra badge-success mb-1" style="font-size: 9px;"><?php echo h($row['course_name']); ?></div>
                                <div class="fw-bold text-indigo"><?php echo h($row['title']); ?></div>
                            </td>
                            <td>
                                <div class="small fw-semibold">📅 <?php echo date('M d, Y', strtotime($row['start_date'])); ?></div>
                                <div class="small opacity-50 mt-1">⏰ <?php echo date('h:i A', strtotime($row['start_date'])); ?></div>
                            </td>
                            <td>
                                <div class="small fw-bold text-danger">🏁 <?php echo date('M d, Y', strtotime($row['deadline'])); ?></div>
                                <div class="small opacity-50 mt-1">⏰ <?php echo date('h:i A', strtotime($row['deadline'])); ?></div>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="<?php echo $row['file_path']; ?>" class="btn btn-astra btn-astra-outline py-2 px-3" style="font-size: 12px;" target="_blank">View</a>
                                    <a href="?delete=<?php echo $row['id']; ?>&csrf_token=<?php echo generate_csrf_token(); ?>" class="btn btn-danger btn-sm rounded-3 px-3 shadow-sm" onclick="return confirm('Archive this module assignment?');">
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php }} else { ?>
                        <tr><td colspan="4" class="text-center text-muted p-5 opacity-50">No published assignments found.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    // Start Date Picker (Defaults to NOW)
    flatpickr("#startPicker", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        defaultDate: "today", // Sets to current date/time
        altInput: true,
        altFormat: "F j, Y - h:i K",
        theme: "material_blue"
    });

    // Deadline Picker
    flatpickr("#deadlinePicker", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        altInput: true,
        altFormat: "F j, Y - h:i K",
        theme: "material_blue"
    });
</script>

    </div>
</div> <!-- End Wrapper -->

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>