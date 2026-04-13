<?php
session_start();
include 'config.php';
require_role('admin');

$user_id = $_SESSION['user']['id'];
$role = $_SESSION['user']['role'];

$page_title = "Add New Course";
include 'components/header.php';
include 'components/sidebar.php';

$message = "";
if(isset($_POST['add'])){
    if(!verify_csrf_token($_POST['csrf_token'] ?? '')){
        die("Security Check Failed (CSRF Token Invalid)");
    }
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $teacher = intval($_POST['teacher']);
    $days = isset($_POST['course_days']) ? implode(", ", $_POST['course_days']) : "";
    $time = $_POST['course_time'];
    $end_time = $_POST['course_end_time'];

    $stmt = $conn->prepare("INSERT INTO courses (course_name, description, teacher_id, course_days, course_time, course_end_time) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisss", $name, $desc, $teacher, $days, $time, $end_time);
    
    if($stmt->execute()){
        $message = "<div class='alert alert-success shadow-sm'>Course Added Successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger shadow-sm'>Error: " . $conn->error . "</div>";
    }
}

$teachers = $conn->query("SELECT * FROM users WHERE role='teacher'");
?>
<!-- MAIN CONTENT -->
<div class="main-astra w-100">
    <div class="header-astra mb-4 animate-up">
        <h4 class="fw-bold mb-0">📚 Course Administration</h4>
    </div>

<div class="container-fluid animate-up" style="animation-delay: 0.1s;">

<div class="container mt-4" style="max-width: 700px;">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h2 class="fw-bold mb-0">✨ Create New Offering</h2>
        <a href="view_courses.php" class="btn-astra btn-astra-outline py-2 px-4 shadow-sm">
            ⬅ Review Course List
        </a>
    </div>

    <?php echo $message; ?>

    <form method="post" class="card-astra p-5 shadow-lg mx-auto" style="max-width: 800px;">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        
        <div class="row g-4 mb-4">
            <div class="col-md-12">
                <label class="form-label small fw-bold opacity-75">Course Title</label>
                <input class="form-control-astra w-100" name="name" placeholder="e.g. Advanced Web Architectures" required>
            </div>

            <div class="col-md-12">
                <label class="form-label small fw-bold opacity-75">Course Description</label>
                <textarea class="form-control-astra w-100" name="description" rows="4" placeholder="Brief overview of the course content and objectives..." required></textarea>
            </div>

            <div class="col-md-12">
                <label class="form-label small fw-bold opacity-75">Assigned Instructor</label>
                <select name="teacher" class="form-control-astra w-100" required style="height: 52px;">
                    <option value="">-- Choose an Instructor --</option>
                    <?php while($t=$teachers->fetch_assoc()){ ?>
                        <option value="<?php echo $t['id']; ?>"><?php echo h($t['name']); ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="p-4 rounded-4 mb-5 shadow-sm" style="background: var(--astra-indigo-soft); border: 1px solid var(--border-color);">
            <h6 class="fw-bold mb-4 d-flex align-items-center gap-2">
                <span style="font-size: 18px;">📅</span> Academic Schedule
            </h6>
            
            <div class="mb-4">
                <label class="form-label small fw-bold opacity-75 mb-3">Active Weekdays</label>
                <div class="d-flex flex-wrap gap-4">
                    <?php foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day){ ?>
                        <div class="form-check m-0">
                            <input class="form-check-input shadow-none" type="checkbox" name="course_days[]" value="<?php echo $day; ?>" id="chk<?php echo $day; ?>" style="width: 20px; height: 20px; cursor: pointer;">
                            <label class="form-check-label small fw-semibold ms-1" for="chk<?php echo $day; ?>" style="cursor: pointer;"><?php echo $day; ?></label>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold opacity-75">Lecture Window Opens</label>
                    <input type="time" name="course_time" class="form-control-astra w-100" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold opacity-75">Lecture Window Closes</label>
                    <input type="time" name="course_end_time" class="form-control-astra w-100" required>
                </div>
            </div>
        </div>

        <button class="btn-astra w-100 py-4 fw-bold shadow-lg mt-3" name="add" style="font-size: 16px;">
            Publish Course Offering 🚀
        </button>
    </form>
    </div>
</div> <!-- End Wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>