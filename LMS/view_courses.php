<?php
session_start();
include 'config.php';
require_role(['admin', 'teacher', 'student']);

$user_id = $_SESSION['user']['id'];
$role = $_SESSION['user']['role'];

$page_title = ($role == 'admin') ? "Manage Courses" : "My Courses";
include 'components/header.php';
include 'components/sidebar.php';

$message = "";

/* ================= ADMIN FUNCTIONS ================= */
if($role == 'admin'){

    if(isset($_GET['delete'])){
        $id = $_GET['delete'];
        $conn->query("DELETE FROM courses WHERE id='$id'");
        $message = "<div class='alert alert-success'>Course Deleted Successfully!</div>";
    }

    if(isset($_POST['update'])){
        $id = $_POST['course_id'];
        $name = $_POST['course_name'];
        $description = $_POST['description'];
        $teacher_id = $_POST['teacher_id'];
        $days = isset($_POST['course_days']) ? implode(", ", $_POST['course_days']) : "";
        $time = $_POST['course_time'];
        $end_time = $_POST['course_end_time'];

        $stmt = $conn->prepare("UPDATE courses SET course_name=?, description=?, teacher_id=?, course_days=?, course_time=?, course_end_time=? WHERE id=?");
        $stmt->bind_param("ssisssi", $name, $description, $teacher_id, $days, $time, $end_time, $id);
        $stmt->execute();

        $message = "<div class='alert alert-success shadow-sm'>Course Updated Successfully!</div>";
    }

    if(isset($_POST['add'])){
        $name = $_POST['course_name'];
        $description = $_POST['description'];
        $teacher_id = $_POST['teacher_id'];
        $days = isset($_POST['course_days']) ? implode(", ", $_POST['course_days']) : "";
        $time = $_POST['course_time'];
        $end_time = $_POST['course_end_time'];

        $stmt = $conn->prepare("INSERT INTO courses (course_name, description, teacher_id, course_days, course_time, course_end_time) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisss", $name, $description, $teacher_id, $days, $time, $end_time);
        $stmt->execute();

        $message = "<div class='alert alert-success shadow-sm'>Course Added Successfully!</div>";
    }
}

/* ================= FETCH COURSES BASED ON ROLE ================= */

if($role == 'admin'){
    $courses = $conn->query("
        SELECT c.*, u.name as teacher_name
        FROM courses c
        LEFT JOIN users u ON c.teacher_id=u.id
        ORDER BY c.id DESC
    ");
}
elseif($role == 'teacher'){
    $courses = $conn->query("
        SELECT c.*, u.name as teacher_name
        FROM courses c
        LEFT JOIN users u ON c.teacher_id=u.id
        WHERE c.teacher_id='$user_id'
        ORDER BY c.id DESC
    ");
}
elseif($role == 'student'){
    $courses = $conn->query("
        SELECT c.*, u.name as teacher_name
        FROM courses c
        JOIN enrollments e ON c.id=e.course_id
        LEFT JOIN users u ON c.teacher_id=u.id
        WHERE e.student_id='$user_id'
        ORDER BY c.id DESC
    ");
}

/* Fetch teachers for dropdown (Admin only) */
if($role == 'admin'){
    $teachers = $conn->query("SELECT * FROM users WHERE role='teacher'");
}
?>

<!-- MAIN CONTENT -->
<div class="main-astra w-100">
    <div class="header-astra mb-4 animate-up">
        <h4 class="fw-bold mb-0">📚 Course Catalog & Logistics</h4>
    </div>

<div class="container-fluid animate-up" style="animation-delay: 0.1s;">

<div class="d-flex justify-content-between align-items-center mb-5">
    <h2 class="fw-bold mb-0">
        <?php
        if($role=='admin') echo "🛡️ Strategic Course Management";
        elseif($role=='teacher') echo "👨‍🏫 Academic Portfolio";
        else echo "🎓 My Learning Journey";
        ?>
    </h2>
</div>

<?php echo $message; ?>

<!-- ================= ADD COURSE (ADMIN ONLY) ================= -->
<?php if($role=='admin'){ ?>
<div class="card-astra p-5 mb-5 shadow-lg">
    <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
        <span style="font-size: 20px;">➕</span> Provision New Offering
    </h5>
    <form method="post">
        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label small fw-bold opacity-75">Course Infrastructure Name</label>
                <input type="text" name="course_name" class="form-control-astra w-100" placeholder="e.g. Applied Quantum Computing" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold opacity-75">Lead Instructor</label>
                <select name="teacher_id" class="form-control-astra w-100" required style="height: 52px;">
                    <option value="">-- Choose Instructor --</option>
                    <?php while($t=$teachers->fetch_assoc()){ ?>
                        <option value="<?php echo $t['id']; ?>"><?php echo h($t['name']); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-12">
                <label class="form-label small fw-bold opacity-75">Curriculum Overview</label>
                <textarea name="description" class="form-control-astra w-100" rows="3" placeholder="Brief outline of course objectives..."></textarea>
            </div>
            <div class="col-md-12">
                <div class="p-4 rounded-4 mb-3" style="background: var(--astra-indigo-soft); border: 1px solid var(--border-color);">
                    <label class="form-label small fw-bold opacity-75 mb-3 d-block">Active Academic Cycle (Days)</label>
                    <div class="d-flex flex-wrap gap-4">
                        <?php foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day){ ?>
                            <div class="form-check m-0">
                                <input class="form-check-input shadow-none" type="checkbox" name="course_days[]" value="<?php echo $day; ?>" id="add<?php echo $day; ?>" style="width: 20px; height: 20px; cursor: pointer;">
                                <label class="form-check-label small fw-semibold ms-1" for="add<?php echo $day; ?>" style="cursor: pointer;"><?php echo $day; ?></label>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <label class="form-label small fw-bold opacity-75">Lecture Window Open</label>
                <input type="time" name="course_time" class="form-control-astra w-100" required>
            </div>
            <div class="col-md-5">
                <label class="form-label small fw-bold opacity-75">Lecture Window Close</label>
                <input type="time" name="course_end_time" class="form-control-astra w-100" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" name="add" class="btn-astra w-100 py-3 shadow-lg">
                    Build Course 🚀
                </button>
            </div>
        </div>
    </form>
</div>
<?php } ?>

<!-- ================= COURSE LIST ================= -->
<div class="card-astra p-5 shadow-lg">
    <h5 class="fw-bold mb-4">Academic Offerings Registry</h5>
    <div class="table-responsive">
        <table class="table-astra">
            <thead>
                <tr>
                    <th>Module Profile</th>
                    <th>Engagement Window</th>
                    <th>Authority</th>
                    <?php if($role=='admin'){ ?><th class="text-end">Management</th><?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php while($row=$courses->fetch_assoc()){ ?>
                <tr>
                    <td>
                        <div class="fw-bold text-indigo" style="font-size: 15px;"><?php echo htmlspecialchars($row['course_name']); ?></div>
                        <div class="small opacity-50 text-truncate mt-1" style="max-width: 300px;"><?php echo htmlspecialchars($row['description']); ?></div>
                    </td>
                    <td>
                        <div class="small fw-bold mb-1" style="color: var(--astra-indigo);">
                             ⏱ <?php 
                                $start = $row['course_time'] ? date('h:i A', strtotime($row['course_time'])) : 'N/A';
                                $end = $row['course_end_time'] ? date('h:i A', strtotime($row['course_end_time'])) : 'N/A';
                                echo "$start - $end";
                            ?>
                        </div>
                        <div class="small opacity-50">🗓 <?php echo $row['course_days'] ?: 'Unscheduled'; ?></div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold small" style="min-width: 30px; height: 30px; background: var(--border-color); font-size: 10px;">
                                Instructor
                            </div>
                            <span class="small fw-semibold opacity-75"><?php echo htmlspecialchars($row['teacher_name'] ?? 'Unassigned'); ?></span>
                        </div>
                    </td>
                    <?php if($role=='admin'){ ?>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-2">
                            <button class="btn btn-warning btn-sm rounded-3 px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>">
                                Edit
                            </button>
                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm rounded-3 px-3 shadow-sm" onclick="return confirm('Decommission this academic offering?');">
                                Delete
                            </a>
                        </div>
                    </td>
                    <?php } ?>
                </tr>

<!-- ================= EDIT MODAL (ADMIN ONLY) ================= -->
<?php if($role=='admin'){ ?>
<div class="modal fade"
id="editModal<?php echo $row['id']; ?>"
tabindex="-1">

<div class="modal-dialog">
<div class="modal-content">

<div class="modal-header">
<h5>Edit Course</h5>
<button type="button"
class="btn-close"
data-bs-dismiss="modal"></button>
</div>

<form method="post">
<div class="modal-body">

<input type="hidden"
name="course_id"
value="<?php echo $row['id']; ?>">

<input type="text"
name="course_name"
class="form-control mb-3"
value="<?php echo $row['course_name']; ?>"
required>

<textarea name="description"
class="form-control mb-3"><?php echo $row['description']; ?></textarea>

<select name="teacher_id" class="form-control mb-3" required>
    <?php
    $teachers2 = $conn->query("SELECT * FROM users WHERE role='teacher'");
    while($t2=$teachers2->fetch_assoc()){
    ?>
        <option value="<?php echo $t2['id']; ?>" <?php if($row['teacher_id']==$t2['id']) echo "selected"; ?>>
            <?php echo $t2['name']; ?>
        </option>
    <?php } ?>
</select>

<div class="mb-3">
    <label class="fw-bold small d-block mb-2">Teaching Days</label>
    <div class="d-flex flex-wrap gap-2">
        <?php 
        $current_days = array_map('trim', explode(',', $row['course_days']));
        foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day){ ?>
            <div class="form-check form-check-inline m-0">
                <input class="form-check-input" type="checkbox" name="course_days[]" value="<?php echo $day; ?>" id="edit<?php echo $row['id'].$day; ?>" <?php if(in_array($day, $current_days)) echo "checked"; ?>>
                <label class="form-check-label small" for="edit<?php echo $row['id'].$day; ?>"><?php echo $day; ?></label>
            </div>
        <?php } ?>
    </div>
</div>

<div class="row g-2 mb-3">
    <div class="col-6">
        <label class="fw-bold small d-block mb-1">Start Time</label>
        <input type="time" name="course_time" class="form-control" value="<?php echo $row['course_time']; ?>" required>
    </div>
    <div class="col-6">
        <label class="fw-bold small d-block mb-1">End Time</label>
        <input type="time" name="course_end_time" class="form-control" value="<?php echo $row['course_end_time']; ?>" required>
    </div>
</div>

</div>

<div class="modal-footer">
<button type="submit"
name="update"
class="btn btn-success">
Update
</button>
</div>

</form>

</div>
</div>
</div>
<?php } ?>

<?php } ?>

</tbody>
</table>

</div>

    </div>
</div> <!-- End Wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>