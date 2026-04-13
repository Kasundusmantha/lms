<?php
session_start();
include 'config.php';
require_role('admin');

$page_title = "Manage Users";
include 'components/header.php';
include 'components/sidebar.php';

$message = "";

/* ================= ADD USER ================= */
if(isset($_POST['add'])){
    if(!verify_csrf_token($_POST['csrf_token'] ?? '')){
        die("CSRF Error");
    }

    $name  = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role  = $_POST['role'];
    $phone = $_POST['phone'];
    $parent_phone = $_POST['parent_phone'];

    $check = $conn->prepare("SELECT id FROM users WHERE email=?");
    $check->bind_param("s",$email);
    $check->execute();
    $res = $check->get_result();

    if($res->num_rows > 0){
        $message = "<div class='alert alert-danger'>Email already exists</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO users(name,email,password,role,phone,parent_phone) VALUES(?,?,?,?,?,?)");
        $stmt->bind_param("ssssss",$name,$email,$password,$role,$phone,$parent_phone);
        $stmt->execute();
        $message = "<div class='alert alert-success'>User Added Successfully</div>";
    }
}

/* ================= DELETE ================= */
if(isset($_POST['delete_id'])){
    $id = intval($_POST['delete_id']);
    $conn->query("DELETE FROM users WHERE id=$id");
    $message = "<div class='alert alert-danger'>User Deleted</div>";
}

/* ================= UPDATE ================= */
if(isset($_POST['update'])){
    if(!verify_csrf_token($_POST['csrf_token'] ?? '')){
        die("CSRF Error");
    }

    $id = intval($_POST['user_id']);
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $phone = $_POST['phone'];
    $parent_phone = $_POST['parent_phone'];
    $password = $_POST['password'] ?? '';

    if(!empty($password)){
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET name=?,email=?,role=?,password=?,phone=?,parent_phone=? WHERE id=?");
        $stmt->bind_param("ssssssi",$name,$email,$role,$hash,$phone,$parent_phone,$id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name=?,email=?,role=?,phone=?,parent_phone=? WHERE id=?");
        $stmt->bind_param("sssssi",$name,$email,$role,$phone,$parent_phone,$id);
    }

    if(!$stmt->execute()){
        die($stmt->error);
    }

    $message = "<div class='alert alert-success'>User Updated Successfully</div>";
}

/* ================= FETCH USERS ================= */
$users = $conn->query("SELECT * FROM users ORDER BY id DESC");
?>

<div class="main-astra w-100">
<div class="container-fluid">

<h4 class="mb-4">👥 Manage Users</h4>
<?php echo $message; ?>

<!-- ADD USER -->
<div class="card-astra p-4 mb-4 shadow">
<h5 class="mb-3">➕ Add New User</h5>

<form method="post">
<input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

<div class="row g-3">

<div class="col-md-4">
<label>Full Name</label>
<input type="text" name="name" class="form-control-astra" placeholder="John Silva" required>
</div>

<div class="col-md-4">
<label>Email</label>
<input type="email" name="email" class="form-control-astra" placeholder="john@gmail.com" required>
</div>

<div class="col-md-4">
<label>Password</label>
<input type="password" name="password" class="form-control-astra" placeholder="Enter password" required>
</div>

<div class="col-md-3">
<label>Student Phone</label>
<input type="text" name="phone" class="form-control-astra" placeholder="0771234567">
</div>

<div class="col-md-3">
<label>Parent Phone</label>
<input type="text" name="parent_phone" class="form-control-astra" placeholder="0719876543">
</div>

<div class="col-md-3">
<label>Role</label>
<select name="role" class="form-control-astra" required>
<option value="">Select Role</option>
<option value="student">Student</option>
<option value="teacher">Teacher</option>
<option value="admin">Admin</option>
</select>
</div>

<div class="col-md-3 d-flex align-items-end">
<button name="add" class="btn-astra w-100">Create</button>
</div>

</div>
</form>
</div>

<!-- USER TABLE -->
<div class="card-astra p-4">
<table class="table-astra">
<thead>
<tr>
<th>Name</th>
<th>Email</th>
<th>Phone</th>
<th>Role</th>
<th class="text-end">Action</th>
</tr>
</thead>

<tbody>
<?php while($row = $users->fetch_assoc()){ ?>
<tr>
<td><?php echo $row['name']; ?></td>
<td><?php echo $row['email']; ?></td>
<td><?php echo $row['phone']; ?></td>
<td><?php echo ucfirst($row['role']); ?></td>

<td class="text-end">

<button class="btn btn-warning btn-sm"
data-bs-toggle="modal"
data-bs-target="#edit<?php echo $row['id']; ?>">
Edit
</button>

<form method="post" style="display:inline;">
<input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
<button type="button" class="btn btn-danger btn-sm"
onclick="confirmDelete(this.form)">
Delete
</button>
</form>

</td>
</tr>
<?php } ?>
</tbody>
</table>
</div>

<!-- MODALS OUTSIDE TABLE -->
<?php
$users->data_seek(0);
while($row = $users->fetch_assoc()){ ?>
<div class="modal fade" id="edit<?php echo $row['id']; ?>" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">

<form method="post">

<div class="modal-header">
<h5>Edit User</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
<input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">

<input type="text" name="name" class="form-control mb-2"
value="<?php echo $row['name']; ?>" placeholder="Name" required>

<input type="email" name="email" class="form-control mb-2"
value="<?php echo $row['email']; ?>" placeholder="Email" required>

<input type="text" name="phone" class="form-control mb-2"
value="<?php echo $row['phone']; ?>" placeholder="Phone">

<input type="text" name="parent_phone" class="form-control mb-2"
value="<?php echo $row['parent_phone']; ?>" placeholder="Parent Phone">

<input type="password" name="password" class="form-control mb-2"
placeholder="New Password (optional)">

<select name="role" class="form-control">
<option value="student" <?php if($row['role']=='student') echo 'selected'; ?>>Student</option>
<option value="teacher" <?php if($row['role']=='teacher') echo 'selected'; ?>>Teacher</option>
<option value="admin" <?php if($row['role']=='admin') echo 'selected'; ?>>Admin</option>
</select>

</div>

<div class="modal-footer">
<button name="update" class="btn btn-success">Update</button>
</div>

</form>

</div>
</div>
</div>
<?php } ?>

</div>
</div>

<!-- JS REQUIRED -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- DELETE CONFIRM -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmDelete(form){
Swal.fire({
title:'Are you sure?',
text:'This will delete user permanently!',
icon:'warning',
showCancelButton:true,
confirmButtonColor:'#d33'
}).then((r)=>{
if(r.isConfirmed){form.submit();}
});
}
</script>