<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'student'){
    header("Location:index.php");
    exit();
}

$student_id = $_SESSION['user']['id'];

/* Fetch Enrolled Courses */
$courses = $conn->query("
    SELECT c.*
    FROM courses c
    JOIN enrollments e ON c.id = e.course_id
    WHERE e.student_id = '$student_id'
");
?>

<?php
include 'components/header.php';
include 'components/sidebar.php';
?>

<!-- MAIN CONTENT -->
<div class="main-astra w-100">
    <div class="header-astra mb-4 animate-up">
        <h4 class="fw-bold mb-0">📖 My Academic Portfolio</h4>
    </div>

<div class="container-fluid animate-up" style="animation-delay: 0.1s;">

<div class="d-flex justify-content-between align-items-center mb-5">
    <h2 class="fw-bold mb-0">🎓 Currently Enrolled</h2>
</div>

<?php if($courses->num_rows == 0){ ?>
    <div class="card-astra p-5 text-center opacity-50">
        <div class="fs-1 mb-3">📭</div>
        <h5 class="fw-bold">No academic enrollments found.</h5>
        <p class="small">Visit the registrar or administration office to enroll in modules.</p>
    </div>
<?php } else { ?>
    <div class="row g-4">
        <?php while($row = $courses->fetch_assoc()){ ?>
            <div class="col-md-4 mb-4">
                <div class="card-astra p-5 shadow-lg h-100 d-flex flex-column transition-all hover-glow">
                    <div class="fs-1 mb-3">📘</div>
                    <h4 class="fw-bold mb-2"><?php echo htmlspecialchars($row['course_name']); ?></h4>
                    <p class="text-muted small mb-5 leading-relaxed"><?php echo htmlspecialchars($row['description']); ?></p>
                    
                    <div class="mt-auto d-flex flex-column gap-2">
                        <a href="quiz.php?course_id=<?php echo $row['id']; ?>" class="btn-astra w-100 py-3 fw-bold text-center">
                            Launch Assessment ▶
                        </a>
                        <div class="d-flex gap-2">
                            <a href="submit_assignment.php?course_id=<?php echo $row['id']; ?>" class="btn-astra btn-astra-outline w-100 py-3 fw-bold text-center" style="font-size: 11px;">
                                Submit Tasks 📤
                            </a>
                            <a href="notes.php?course_id=<?php echo $row['id']; ?>" class="btn-astra btn-astra-outline w-100 py-3 fw-bold text-center" style="font-size: 11px;">
                                Resources 📚
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
<?php } ?>

</div> <!-- End Container -->
</div> <!-- End Main -->
</div> <!-- End Wrapper -->
</body>
</html>