<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'student'){
    header("Location:index.php");
    exit();
}

$student_id = $_SESSION['user']['id'];
$message = "";

/* =====================================================
   CHECK STUDENT ENROLLMENT
===================================================== */
$enrolled = $conn->query("
    SELECT course_id 
    FROM enrollments 
    WHERE student_id='$student_id'
");

$no_course = ($enrolled->num_rows == 0);

/* =====================================================
   HANDLE ASSIGNMENT SUBMISSION
===================================================== */
if(isset($_POST['submit_assignment']) && !$no_course){

    $assignment_id = intval($_POST['assignment_id']);

    // Verify assignment belongs to student enrolled course
    $check = $conn->query("
        SELECT a.* 
        FROM assignments a
        WHERE a.id='$assignment_id'
        AND a.course_id IN (
            SELECT course_id 
            FROM enrollments 
            WHERE student_id='$student_id'
        )
    ");

    if($check->num_rows == 0){

        $message = "<div class='alert alert-danger'>
                    You are not allowed to submit this assignment.
                    </div>";

    } else {

        $assignment = $check->fetch_assoc();

        // Deadline check
        if(strtotime($assignment['deadline']) < time()){

            $message = "<div class='alert alert-danger'>
                        Deadline has passed!
                        </div>";

        } else {

            if(isset($_FILES['file']) && $_FILES['file']['error']==0){

                if(!is_dir("uploads")){
                    mkdir("uploads");
                }

                $fileName = time()."_".
                    preg_replace("/[^a-zA-Z0-9.]/","_",$_FILES['file']['name']);

                $targetPath = __DIR__."/uploads/".$fileName;
                $savePath = "uploads/".$fileName;

                if(move_uploaded_file($_FILES['file']['tmp_name'],$targetPath)){

                    // Check if already submitted
                    $exists = $conn->query("
                        SELECT id FROM submissions
                        WHERE assignment_id='$assignment_id'
                        AND student_id='$student_id'
                    ");

                    if($exists->num_rows > 0){

                        $conn->query("
                            UPDATE submissions 
                            SET file_path='$savePath'
                            WHERE assignment_id='$assignment_id'
                            AND student_id='$student_id'
                        ");

                    } else {

                        $conn->query("
                            INSERT INTO submissions
                            (assignment_id,student_id,file_path)
                            VALUES('$assignment_id','$student_id','$savePath')
                        ");
                        
                        // Reward XP
                        $conn->query("UPDATE users SET xp = xp + 100 WHERE id='$student_id'");
                        if(isset($_SESSION['user']['xp'])) {
                            $_SESSION['user']['xp'] += 100;
                        } else {
                            $_SESSION['user']['xp'] = 100;
                        }
                    }

                    $message = "<div class='alert alert-success'>
                                Assignment Submitted Successfully!
                                </div>";
                }
            }
        }
    }
}

/* =====================================================
   FETCH ASSIGNMENTS (ONLY STUDENT COURSES)
===================================================== */
$assignments = [];

if(!$no_course){
    $course_filter = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;
    $filter_sql = $course_filter ? "AND a.course_id = '$course_filter'" : "";
    
    $assignments = $conn->query("
        SELECT a.*, c.course_name
        FROM assignments a
        JOIN courses c ON a.course_id = c.id
        WHERE a.course_id IN (
            SELECT course_id 
            FROM enrollments 
            WHERE student_id='$student_id'
        ) $filter_sql
        ORDER BY a.id DESC
    ");
}
?>

<?php
include 'components/header.php';
include 'components/sidebar.php';
?>

<!-- MAIN CONTENT -->
<div class="main-astra w-100">
    <div class="header-astra mb-4 animate-up">
        <h4 class="fw-bold mb-0">📤 Deliverables Portal</h4>
    </div>

<div class="container-fluid animate-up" style="animation-delay: 0.1s;">

<?php echo $message; ?>

    <div class="d-flex justify-content-between align-items-center mb-5">
        <h2 class="fw-bold mb-0">✨ Academic Deliverables</h2>
    </div>

    <?php if($no_course){ ?>
        <div class="card-astra p-5 text-center opacity-50">
            <div class="fs-1 mb-3">📭</div>
            <h5 class="fw-bold">No academic enrollments found.</h5>
            <p class="small">Contact the administration office to initialize your profile.</p>
        </div>
    <?php } else { ?>
        <?php if($assignments->num_rows == 0){ ?>
            <div class="card-astra p-5 text-center opacity-50">
                <div class="fs-1 mb-3">📄</div>
                <h5 class="fw-bold">No active assignments.</h5>
                <p class="small">Contact your course instructor for assessment schedules.</p>
            </div>
        <?php } else { ?>


        <div class="card-astra p-5 shadow-lg">
            <h5 class="fw-bold mb-4">Open Submission Windows</h5>
            <div class="table-responsive">
                <table class="table-astra">
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th>Deadline (Enforcement)</th>
                            <th>Reference Infrastructure</th>
                            <th class="text-end">Synchronize Deliverable</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $assignments->fetch_assoc()){ 
                            $is_late = strtotime($row['deadline']) < time();
                            $status_badge = $is_late ? "badge-danger" : "badge-success";
                        ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-indigo"><?php echo h($row['course_name']); ?></div>
                                <div class="small fw-semibold mt-1"><?php echo h($row['title']); ?></div>
                            </td>
                            <td>
                                <div class="small fw-bold <?php echo $is_late ? 'text-danger' : 'text-success'; ?>">
                                    🏁 <?php echo date('M d, Y', strtotime($row['deadline'])); ?>
                                </div>
                                <div class="small opacity-50 mt-1">⏰ <?php echo date('h:i A', strtotime($row['deadline'])); ?></div>
                            </td>
                            <td>
                                <?php if(!empty($row['file_path'])){ ?>
                                    <a href="<?php echo $row['file_path']; ?>" class="btn btn-astra btn-astra-outline py-2 px-3 shadow-sm" style="font-size: 11px;" target="_blank">
                                        Download PDF 📄
                                    </a>
                                <?php } else { ?>
                                    <span class="opacity-25 small">No Reference File</span>
                                <?php } ?>
                            </td>
                            <td class="text-end">
                                <form method="post" enctype="multipart/form-data" class="d-flex flex-column align-items-end gap-2">
                                    <input type="hidden" name="assignment_id" value="<?php echo $row['id']; ?>">
                                    <input type="file" name="file" class="form-control-astra py-2 px-3" required style="font-size: 11px; max-width: 250px;">
                                    <button type="submit" name="submit_assignment" class="btn-astra py-2 px-4 shadow-sm" style="font-size: 11px;">
                                        Execute Submission 🚀
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php } ?>
    <?php } ?>

</div> <!-- End Container -->
</div> <!-- End Main -->
</div> <!-- End Wrapper -->
</body>
</html>