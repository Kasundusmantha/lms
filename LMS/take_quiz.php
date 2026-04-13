<?php
session_start();
include 'config.php';

if($_SESSION['user']['role'] != 'student'){
    header("Location: dashboard.php");
    exit();
}

$student_id = $_SESSION['user']['id'];

/* ================= GET STUDENT COURSES ================= */
$my_courses = $conn->query("
    SELECT c.id, c.course_name 
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.student_id='$student_id'
");

?>
<?php
include 'components/header.php';
include 'components/sidebar.php';
?>

<!-- MAIN CONTENT -->
<div class="main-astra w-100">
    <div class="header-astra mb-4 animate-up">
        <h4 class="fw-bold mb-0">📚 Examination Portal</h4>
    </div>

<div class="container-fluid animate-up" style="animation-delay: 0.1s;">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h2 class="fw-bold mb-0">🚀 Select Assessment</h2>
    </div>

    <?php if($my_courses->num_rows == 0){ ?>
        <div class="card-astra p-5 text-center opacity-50">
            <div class="fs-1 mb-3">📭</div>
            <h5 class="fw-bold">No academic enrollments found.</h5>
            <p class="small">Enroll in a course to access its assessment infrastructure.</p>
        </div>
    <?php } else { ?>
        <div class="row g-4">
            <?php while($c = $my_courses->fetch_assoc()){ 
                $course_id = $c['id'];
                $has_quiz = $conn->query("SELECT id FROM quizzes WHERE course_id='$course_id' LIMIT 1");
            ?>
                <div class="col-md-4 mb-4">
                    <div class="card-astra p-5 shadow-lg h-100 d-flex flex-column transition-all hover-glow">
                        <div class="fs-1 mb-3">📄</div>
                        <h4 class="fw-bold mb-2"><?php echo htmlspecialchars($c['course_name']); ?></h4>
                        <p class="text-muted small mb-5">Standardized assessment for the module. Verify your readiness before initialization.</p>
                        
                        <div class="mt-auto">
                            <?php if($has_quiz->num_rows > 0){ ?>
                                <a href="quiz.php?course_id=<?php echo $c['id']; ?>" class="btn-astra w-100 py-3 fw-bold text-center">
                                    Initialize Quiz ▶
                                </a>
                            <?php } else { ?>
                                <button class="btn btn-secondary w-100 py-3 rounded-4 opacity-50" disabled style="cursor: not-allowed;">
                                    Awaiting Configuration
                                </button>
                            <?php } ?>
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