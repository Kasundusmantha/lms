<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin'){
    header("Location:index.php");
    exit();
}

$message = "";

/* ================= DELETE ENROLLMENT ================= */
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    $conn->query("DELETE FROM enrollments WHERE id='$id'");
    $message = "<div class='alert alert-success'>Enrollment Deleted Successfully!</div>";
}

/* ================= UPDATE ENROLLMENT ================= */
if(isset($_POST['update'])){
    $id = $_POST['enroll_id'];
    $course_id = $_POST['course_id'];

    $conn->query("UPDATE enrollments 
                  SET course_id='$course_id'
                  WHERE id='$id'");

    $message = "<div class='alert alert-success'>Enrollment Updated Successfully!</div>";
}

/* ================= ADD ENROLLMENT ================= */
if(isset($_POST['enroll'])){
    $student_id = $_POST['student_id'];
    $course_id = $_POST['course_id'];

    // Prevent duplicate enrollment
    $check = $conn->query("SELECT * FROM enrollments 
                           WHERE student_id='$student_id' 
                           AND course_id='$course_id'");

    if($check->num_rows > 0){
        $message = "<div class='alert alert-warning'>Student already enrolled in this course!</div>";
    } else {
        $conn->query("INSERT INTO enrollments (student_id, course_id)
                      VALUES('$student_id','$course_id')");
        $message = "<div class='alert alert-success'>Student Enrolled Successfully!</div>";
    }
}

/* ================= FETCH DATA ================= */
$students = $conn->query("SELECT * FROM users WHERE role='student'");
$courses = $conn->query("SELECT * FROM courses");

$enrollments = $conn->query("
    SELECT e.*, 
           u.name as student_name, 
           c.course_name
    FROM enrollments e
    JOIN users u ON e.student_id=u.id
    JOIN courses c ON e.course_id=c.id
    ORDER BY e.id DESC
");
include 'components/header.php';
include 'components/sidebar.php';
?>

<!-- MAIN CONTENT -->
<div class="main-astra w-100">
    <div class="header-astra mb-4 animate-up">
        <h4 class="fw-bold mb-0">👨‍🎓 Enrollment Control</h4>
    </div>

<div class="container-fluid animate-up" style="animation-delay: 0.1s;">

<div class="container-fluid">

<?php echo $message; ?>

<!-- ENROLL FORM -->
<div class="card-astra p-5 mb-5 shadow-lg">
    <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
        <span style="font-size: 20px;">➕</span> Create New Enrollment
    </h5>

    <form method="post">
        <div class="row g-4">
            <div class="col-md-5">
                <label class="form-label small fw-bold opacity-75">Select Student</label>
                <select name="student_id" class="form-control-astra w-100" required style="height: 52px;">
                    <option value="">-- Choose Student --</option>
                    <?php while($s = $students->fetch_assoc()){ ?>
                        <option value="<?php echo $s['id']; ?>">
                            <?php echo h($s['name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="col-md-5">
                <label class="form-label small fw-bold opacity-75">Select Course</label>
                <select name="course_id" class="form-control-astra w-100" required style="height: 52px;">
                    <option value="">-- Choose Course --</option>
                    <?php 
                    $courses->data_seek(0); 
                    while($c = $courses->fetch_assoc()){ ?>
                        <option value="<?php echo $c['id']; ?>">
                            <?php echo h($c['course_name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" name="enroll" class="btn-astra w-100 py-3">
                    Enroll Now 🚀
                </button>
            </div>
        </div>
    </form>
</div>

<!-- ENROLLMENT LIST -->
<div class="card-astra p-5 shadow-lg">
    <h5 class="fw-bold mb-4">Current Enrollments</h5>

    <div class="table-responsive">
        <table class="table-astra">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Current Course Assignment</th>
                    <th class="text-end">Management Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $enrollments->fetch_assoc()){ ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: var(--astra-indigo-soft); color: var(--astra-indigo); font-weight: bold;">
                                <?php echo strtoupper(substr($row['student_name'], 0, 1)); ?>
                            </div>
                            <div class="fw-bold text-indigo"><?php echo h($row['student_name']); ?></div>
                        </div>
                    </td>
                    <td>
                        <form method="post" class="d-flex gap-2">
                            <input type="hidden" name="enroll_id" value="<?php echo $row['id']; ?>">
                            <select name="course_id" class="form-control-astra py-1 px-3" required style="font-size: 13px; height: 38px; min-width: 250px;">
                                <?php 
                                $courses2 = $conn->query("SELECT * FROM courses");
                                while($c2 = $courses2->fetch_assoc()){ ?>
                                    <option value="<?php echo $c2['id']; ?>" <?php if($row['course_id']==$c2['id']) echo "selected"; ?>>
                                        <?php echo h($c2['course_name']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <button type="submit" name="update" class="btn-astra py-1 px-3" style="font-size: 13px;">Update</button>
                        </form>
                    </td>
                    <td class="text-end">
                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm rounded-3 shadow-sm px-4 py-2" onclick="return confirm('Remove this student from the course?');">
                            Release
                        </a>
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