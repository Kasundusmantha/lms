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

/* ================= DELETE NOTE ================= */
if(($role=='teacher' || $role=='admin') && isset($_GET['delete'])){

    $id = intval($_GET['delete']);

    $note = $conn->query("SELECT * FROM notes WHERE id='$id'")->fetch_assoc();

    if($note){
        if(file_exists($note['file_path'])){
            unlink($note['file_path']); // delete file from folder
        }
        $conn->query("DELETE FROM notes WHERE id='$id'");
        $message = "<div class='alert alert-danger'>Note Deleted Successfully!</div>";
    }
}

/* ================= UPDATE NOTE ================= */
if(($role=='teacher' || $role=='admin') && isset($_POST['update'])){

    $id = intval($_POST['note_id']);
    $title = $_POST['title'];

    $conn->query("UPDATE notes SET title='$title' WHERE id='$id'");

    $message = "<div class='alert alert-success'>Note Updated Successfully!</div>";
}

/* ================= UPLOAD NOTE ================= */
if(($role=='teacher' || $role=='admin') && isset($_POST['upload'])){

    $title = $_POST['title'];
    $course_id = intval($_POST['course_id']);

    if(isset($_FILES['pdf']) && $_FILES['pdf']['error'] == 0){

        $fileName = time()."_".basename($_FILES['pdf']['name']);
        $tmp = $_FILES['pdf']['tmp_name'];
        $path = "uploads/".$fileName;

        if(strtolower(pathinfo($path, PATHINFO_EXTENSION)) != "pdf"){
            $message = "<div class='alert alert-danger'>Only PDF files allowed!</div>";
        } else {

            if(!is_dir("uploads")){
                mkdir("uploads");
            }

            move_uploaded_file($tmp,$path);

            $stmt = $conn->prepare("INSERT INTO notes (course_id,title,file_path,uploaded_by) VALUES (?,?,?,?)");
            $stmt->bind_param("issi",$course_id,$title,$path,$user_id);
            $stmt->execute();

            $message = "<div class='alert alert-success'>PDF Uploaded Successfully!</div>";
        }
    }
}

/* ================= FETCH COURSES ================= */
if($role=='teacher'){
    $courses = $conn->query("SELECT * FROM courses WHERE teacher_id='$user_id'");
}elseif($role=='admin'){
    $courses = $conn->query("SELECT * FROM courses");
}

/* ================= FETCH NOTES ================= */
if($role=='student'){
    $course_filter = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;
    $filter_sql = $course_filter ? "AND n.course_id = '$course_filter'" : "";

    $notes = $conn->query("
        SELECT n.*, c.course_name
        FROM notes n
        JOIN enrollments e ON n.course_id = e.course_id
        JOIN courses c ON n.course_id = c.id
        WHERE e.student_id='$user_id' $filter_sql
        ORDER BY n.id DESC
    ");

}else{
    $course_filter = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;
    $filter_sql = $course_filter ? "WHERE n.course_id = '$course_filter'" : "";

    $notes = $conn->query("
        SELECT n.*, c.course_name
        FROM notes n
        JOIN courses c ON n.course_id = c.id
        $filter_sql
        ORDER BY n.id DESC
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
        <h4 class="fw-bold mb-0">📚 Knowledge Repository</h4>
    </div>

<div class="container-fluid animate-up" style="animation-delay: 0.1s;">

<?php echo $message; ?>

    <div class="d-flex justify-content-between align-items-center mb-5">
        <h2 class="fw-bold mb-0">✨ Academic Resources</h2>
    </div>

    <!-- ================= UPLOAD FORM ================= -->
    <?php if($role=='teacher' || $role=='admin'){ ?>
    <div class="card-astra p-5 mb-5 shadow-lg">
        <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
            <span style="font-size: 20px;">📂</span> Publish New Material
        </h5>
        <form method="post" enctype="multipart/form-data">
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label small fw-bold opacity-75">Target Module</label>
                    <select name="course_id" class="form-control-astra w-100" required style="height: 52px;">
                        <option value="">-- Select Module --</option>
                        <?php 
                        $courses->data_seek(0);
                        while($c=$courses->fetch_assoc()){ ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo h($c['course_name']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold opacity-75">Resource Title</label>
                    <input type="text" name="title" class="form-control-astra w-100" placeholder="e.g. Quantum Mechanics Vol 1" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold opacity-75">Document (.pdf only)</label>
                    <input type="file" name="pdf" class="form-control-astra w-100 py-2 pt-3" accept=".pdf" required>
                </div>
                <div class="col-md-12 text-end">
                    <button type="submit" name="upload" class="btn-astra px-5 py-3 shadow-lg">
                        Execute Publication 🚀
                    </button>
                </div>
            </div>
        </form>
    </div>
    <?php } ?>

    <!-- ================= NOTES TABLE ================= -->
    <div class="card-astra p-5 shadow-lg">
        <h5 class="fw-bold mb-4">Verified Resource Registry</h5>
        <div class="table-responsive">
            <table class="table-astra">
                <thead>
                    <tr>
                        <th>Title & Module</th>
                        <th>Engagement</th>
                        <?php if($role!='student'){ ?>
                            <th class="text-end">Management</th>
                        <?php } ?>
                        <th class="text-end">Temporal Stamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row=$notes->fetch_assoc()){ ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-indigo"><?php echo h($row['title']); ?></div>
                            <div class="small opacity-50 mt-1"><?php echo h($row['course_name']); ?></div>
                        </td>
                        <td>
                            <a href="<?php echo $row['file_path']; ?>" class="btn btn-astra btn-astra-outline py-2 px-3 shadow-sm" style="font-size: 11px;" target="_blank">
                                Access Resource 📄
                            </a>
                        </td>
                        <?php if($role!='student'){ ?>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <button class="btn btn-warning btn-sm rounded-3 px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>">
                                    ✏️ Edit Title
                                </button>
                                <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm rounded-3 px-3 shadow-sm" onclick="return confirm('Archive this academic resource?');">
                                    Delete
                                </a>
                            </div>
                        </td>
                        <?php } ?>
                        <td class="text-end small opacity-50"><?php echo date("d M Y", strtotime($row['created_at'])); ?></td>
                    </tr>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="post">
<div class="modal-header">
<h5>Edit Note</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
<input type="hidden" name="note_id" value="<?php echo $row['id']; ?>">
<input type="text" name="title" class="form-control"
value="<?php echo $row['title']; ?>" required>
</div>

<div class="modal-footer">
<button type="submit" name="update" class="btn btn-success">
Update
</button>
</div>
</form>
</div>
</div>
</div>

<?php } ?>

</tbody>
</table>

</div>

</div> <!-- End Container -->
</div> <!-- End Main -->
</div> <!-- End Wrapper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>