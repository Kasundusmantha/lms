<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user'])){
    header("Location:index.php");
    exit();
}

$user_id = $_SESSION['user']['id'];
$role = $_SESSION['user']['role'];
$message = "";

/* =====================================================
   ADD QUESTION (Teacher/Admin)
===================================================== */
if(($role=='teacher' || $role=='admin') && isset($_POST['add'])){

    $course_id = intval($_POST['course_id']);
    $question  = $_POST['question'];
    $option1   = $_POST['option1'];
    $option2   = $_POST['option2'];
    $option3   = $_POST['option3'];
    $option4   = $_POST['option4'];
    $answer    = $_POST['answer'];
    $order     = intval($_POST['question_order']);

    $stmt = $conn->prepare("INSERT INTO quizzes
    (course_id,question,option1,option2,option3,option4,correct_answer,question_order)
    VALUES(?,?,?,?,?,?,?,?)");

    $stmt->bind_param("issssssi",
        $course_id,$question,$option1,$option2,$option3,$option4,$answer,$order);

    $stmt->execute();

    $message = "<div class='alert alert-success'>Question Added Successfully!</div>";
}

/* =====================================================
   ADD QUESTIONS VIA CSV (Teacher/Admin)
===================================================== */
if(($role=='teacher' || $role=='admin') && isset($_POST['import_csv'])){
    $course_id = intval($_POST['csv_course_id']);
    
    if(isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0){
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        
        if($handle !== FALSE){
            fgetcsv($handle); // Skip header row
            $imported = 0;
            
            $stmt = $conn->prepare("INSERT INTO quizzes (course_id,question,option1,option2,option3,option4,correct_answer,question_order) VALUES(?,?,?,?,?,?,?,?)");
            
            while(($data = fgetcsv($handle, 1000, ",")) !== FALSE){
                if(count($data) >= 7){
                    $order   = intval($data[0]);
                    $qtext   = $data[1];
                    $opt1    = $data[2];
                    $opt2    = $data[3];
                    $opt3    = $data[4];
                    $opt4    = $data[5];
                    $ans     = strtoupper(trim($data[6]));
                    
                    $stmt->bind_param("issssssi", $course_id, $qtext, $opt1, $opt2, $opt3, $opt4, $ans, $order);
                    $stmt->execute();
                    $imported++;
                }
            }
            fclose($handle);
            $message = "<div class='alert alert-success'>$imported Questions Imported Successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error opening CSV File!</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Please upload a valid CSV file.</div>";
    }
}

/* =====================================================
   DELETE QUESTION
===================================================== */
if(($role=='teacher' || $role=='admin') && isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM quizzes WHERE id='$id'");
    $message = "<div class='alert alert-danger'>Question Deleted!</div>";
}

/* =====================================================
   DELETE FULL QUIZ (All Questions in Course)
===================================================== */
if(($role=='teacher' || $role=='admin') && isset($_GET['delete_quiz'])){
    $cid = intval($_GET['delete_quiz']);
    $conn->query("DELETE FROM quizzes WHERE course_id='$cid'");
    $message = "<div class='alert alert-danger'>Entire Quiz Deleted!</div>";
}

/* =====================================================
   BROADCAST QUIZ TO STUDENTS
===================================================== */
if(($role=='teacher' || $role=='admin') && isset($_GET['broadcast_quiz'])){
    $cid = intval($_GET['broadcast_quiz']);
    
    // Get course info
    $cQuery = $conn->query("SELECT course_name, quiz_time FROM courses WHERE id='$cid'");
    if($cQuery->num_rows > 0) {
        $c = $cQuery->fetch_assoc();
        $cName = $c['course_name'];
        $qTime = $c['quiz_time'] ?? 10;
        
        $title = "📝 New Quiz: $cName";
        $notice = "A new quiz is now available for the course '$cName'.\nTime Limit: $qTime minutes\nGo to your 'Take Quiz' section to begin your attempt!";
        
        $stmt = $conn->prepare("INSERT INTO notices (title, message, created_by, course_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $title, $notice, $user_id, $cid);
        $stmt->execute();
        
        $message = "<div class='alert alert-success shadow-sm'>Quiz announcement broadcasted to students! 🚀</div>";
    }
}

/* =====================================================
   STUDENT MODE
===================================================== */
if($role == 'student'){

    if(!isset($_GET['course_id'])){
        echo "Course not selected.";
        exit();
    }

    $course_id = intval($_GET['course_id']);

    $questions = $conn->query("
        SELECT * FROM quizzes
        WHERE course_id='$course_id'
        ORDER BY question_order ASC
    ");

    $time_query = $conn->query("SELECT quiz_time FROM courses WHERE id='$course_id'");
    $time_data = $time_query->fetch_assoc();
    $quiz_time = $time_data['quiz_time'] ?? 10;
}

/* =====================================================
   TEACHER MODE
===================================================== */
elseif($role == 'teacher'){

    $courses = $conn->query("
        SELECT * FROM courses
        WHERE teacher_id='$user_id'
    ");

    $questions = $conn->query("
        SELECT q.*, c.course_name
        FROM quizzes q
        JOIN courses c ON q.course_id=c.id
        WHERE c.teacher_id='$user_id'
        ORDER BY q.question_order ASC
    ");
}

/* =====================================================
   ADMIN MODE
===================================================== */
elseif($role == 'admin'){

    $courses = $conn->query("SELECT * FROM courses");

    $questions = $conn->query("
        SELECT q.*, c.course_name
        FROM quizzes q
        JOIN courses c ON q.course_id=c.id
        ORDER BY q.question_order ASC
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
        <h4 class="fw-bold mb-0">📝 Knowledge Assessment Hub</h4>
    </div>

<div class="container-fluid animate-up" style="animation-delay: 0.1s;">

<?php echo $message; ?>

<!-- ================= STUDENT QUIZ ================= -->
<?php if($role=='student'){ ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <h2 class="fw-bold mb-0">🚀 Active Assessment</h2>
            <div class="card-astra py-2 px-4 shadow-sm border-0 d-flex align-items-center gap-3" style="background: var(--astra-warning-soft); color: var(--astra-warning);">
                <span style="font-size: 20px;">⏳</span>
                <span class="fw-bold" id="timer" style="font-size: 18px; letter-spacing: 1px;"><?php echo $quiz_time; ?>:00</span>
            </div>
        </div>

        <?php if($questions->num_rows==0){ ?>
            <div class="card-astra p-5 text-center opacity-50">
                <div class="fs-1 mb-3">📭</div>
                <h5 class="fw-bold">No evaluation units found.</h5>
                <p class="small">Contact your instructor for the assessment schedule.</p>
            </div>
        <?php } else { ?>
            <form method="post" action="submit_quiz.php" class="quiz-form">
                <?php while($q=$questions->fetch_assoc()){ ?>
                    <div class="card-astra p-5 mb-4 shadow-lg border-0 transition-all hover-glow">
                        <div class="d-flex align-items-start gap-4">
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold" style="min-width: 45px; height: 45px; background: var(--astra-indigo-soft); color: var(--astra-indigo);">
                                <?php echo $q['question_order']; ?>
                            </div>
                            <div class="w-100">
                                <h5 class="fw-bold mb-4 leading-relaxed"><?php echo h($q['question']); ?></h5>
                                <div class="row g-3">
                                    <?php foreach(['A' => 'option1', 'B' => 'option2', 'C' => 'option3', 'D' => 'option4'] as $key => $opt){ ?>
                                        <div class="col-md-6">
                                            <label class="d-block p-4 rounded-4 border border-2 border-transparent transition-all cursor-pointer bg-light hover-border-indigo" style="cursor: pointer;">
                                                <div class="d-flex align-items-center gap-3">
                                                    <input type="radio" name="answer[<?php echo $q['id']; ?>]" value="<?php echo $key; ?>" required style="accent-color: var(--astra-indigo); width: 22px; height: 22px;">
                                                    <span class="fw-semibold opacity-75"><?php echo h($q[$opt]); ?></span>
                                                </div>
                                            </label>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                
                <div class="text-end mt-5">
                    <button type="submit" class="btn-astra px-5 py-4 fw-bold shadow-lg" style="font-size: 16px;">
                        Finalize & Submit Assessment 🛡️
                    </button>
                </div>
            </form>
        <?php } ?>
    </div>
</div>

<script>
let time=<?php echo $quiz_time; ?>*60;
let timer=document.getElementById("timer");
setInterval(function(){
let m=Math.floor(time/60);
let s=time%60;
timer.innerHTML=m+":"+(s<10?"0":"")+s;
time--;
if(time<0){document.forms[0].submit();}
},1000);
</script>

<?php } ?>

<!-- ================= TEACHER / ADMIN ================= -->
<?php if($role=='teacher' || $role=='admin'){ ?>

<h2 class="fw-bold mb-5">📋 Assessment Management</h2>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card-astra p-5 h-100 shadow-lg">
            <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
                <span style="font-size: 20px;">✍️</span> Strategic Authoring
            </h5>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label small fw-bold opacity-75">Target Academic Module</label>
                    <select name="course_id" class="form-control-astra w-100" required style="height: 52px;">
                        <option value="">-- Select Module --</option>
                        <?php 
                        $courses->data_seek(0);
                        while($c=$courses->fetch_assoc()){ ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo h($c['course_name']); ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold opacity-75">Sequence Order</label>
                    <input type="number" name="question_order" class="form-control-astra w-100" placeholder="e.g. 1" required>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold opacity-75">Knowledge Query (Question)</label>
                    <textarea name="question" class="form-control-astra w-100" rows="3" placeholder="Compose your query here..." required></textarea>
                </div>

                <div class="row g-3 mb-4">
                    <?php for($i=1; $i<=4; $i++){ ?>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold opacity-50">Option <?php echo $i; ?></label>
                            <input type="text" name="option<?php echo $i; ?>" class="form-control-astra w-100" placeholder="..." required>
                        </div>
                    <?php } ?>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold opacity-75">Designated Correct Answer</label>
                    <select name="answer" class="form-control-astra w-100" required style="height: 52px;">
                        <option value="A">Verified: Option 1</option>
                        <option value="B">Verified: Option 2</option>
                        <option value="C">Verified: Option 3</option>
                        <option value="D">Verified: Option 4</option>
                    </select>
                </div>

                <button type="submit" name="add" class="btn-astra w-100 py-3 shadow-lg">
                    Append to Quiz 🏗️
                </button>
            </form>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card-astra p-5 h-100 shadow-lg" style="background: var(--astra-indigo-soft) !important; border: 2px dashed var(--astra-indigo);">
            <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
                <span style="font-size: 20px;">📁</span> Bulk Integration
            </h5>
            <p class="text-muted small mb-4">Deploy multiple questions simultaneously via CSV synchronization.</p>
            <form method="post" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="form-label small fw-bold opacity-75">Module Selection</label>
                    <select name="csv_course_id" class="form-control-astra w-100" required style="height: 52px;">
                        <option value="">-- Choose Module --</option>
                        <?php 
                        $courses->data_seek(0);
                        while($c=$courses->fetch_assoc()){ 
                        ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo h($c['course_name']); ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="mb-5">
                    <label class="form-label small fw-bold opacity-75">Select Synchronized File (.csv)</label>
                    <input type="file" name="csv_file" accept=".csv" class="form-control-astra w-100 py-3" required>
                    <div class="mt-2 text-end">
                        <a href="sample_template.csv" class="small fw-bold text-indigo" download>⬇️ Fetch Import Template</a>
                    </div>
                </div>

                <button type="submit" name="import_csv" class="btn-astra w-100 py-3 shadow-lg">
                    Execute Import Strategy ⚡
                </button>
            </form>
        </div>
    </div>
</div>

<div class="row g-4 mt-4 mb-5">
    <div class="col-md-12">
        <div class="card-astra p-5 shadow-lg">
            <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
                <span style="font-size: 20px;">📢</span> Strategy Broadcast
            </h5>
            <div class="row g-3">
                <?php 
                $courses->data_seek(0);
                while($c=$courses->fetch_assoc()){ ?>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center p-4 rounded-4 bg-light border transition-all hover-border-indigo h-100">
                            <div>
                                <div class="fw-bold text-indigo"><?php echo h($c['course_name']); ?></div>
                                <div class="small opacity-50 mt-1">Status: Assessment Ready</div>
                            </div>
                            <a href="?broadcast_quiz=<?php echo $c['id']; ?>" class="btn-astra py-2 px-3 shadow-sm" style="font-size: 11px;">Announce 🚀</a>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card-astra p-5 shadow-lg">
            <h5 class="fw-bold mb-4">Question Infrastructure</h5>
            <div class="table-responsive">
                <table class="table-astra">
                    <thead>
                        <tr>
                            <th>Identity</th>
                            <th>Module</th>
                            <th class="text-end">Management</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($questions->num_rows > 0){ while($q=$questions->fetch_assoc()){ ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold small" style="min-width: 35px; height: 35px; background: var(--astra-indigo-soft); color: var(--astra-indigo);">
                                            S<?php echo $q['question_order']; ?>
                                        </div>
                                        <div class="fw-semibold text-truncate" style="max-width: 400px;"><?php echo h($q['question']); ?></div>
                                    </div>
                                </td>
                                <td><span class="badge-astra badge-success" style="font-size: 10px;"><?php echo h($q['course_name']); ?></span></td>
                                <td class="text-end">
                                    <a href="?delete=<?php echo $q['id']; ?>" class="btn btn-danger btn-sm rounded-3 shadow-sm px-4 py-2" onclick="return confirm('Archive this knowledge unit?');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php }} else { echo "<tr><td colspan='3' class='text-center text-muted p-5 opacity-50'>No assessment infrastructure found.</td></tr>"; } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php } ?>

<a href="dashboard.php" class="btn btn-secondary mt-4">⬅ Back</a>

</div>
</body>
</html>