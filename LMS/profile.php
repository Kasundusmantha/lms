<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user'])){
    header("Location:index.php");
    exit();
}

$user_id = $_SESSION['user']['id'];
$result = $conn->query("SELECT * FROM users WHERE id='$user_id'");
$user = $result->fetch_assoc();

$message = "";

/* ================= UPDATE PROFILE ================= */
if(isset($_POST['update'])){

    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // Handle profile picture upload
    if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error']==0){

        $allowed = ['jpg','jpeg','png'];
        $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));

        if(in_array($ext, $allowed)){

            $fileName = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/","_",$_FILES['profile_pic']['name']);

            $uploadDir = __DIR__ . "/uploads/profile/";
            $targetPath = $uploadDir . $fileName;

            if(!is_dir($uploadDir)){
                mkdir($uploadDir,0777,true);
            }

            if(move_uploaded_file($_FILES['profile_pic']['tmp_name'],$targetPath)){

                $savePath = "uploads/profile/".$fileName;

                $conn->query("UPDATE users SET profile_pic='$savePath' WHERE id='$user_id'");
            }
        }
    }

    $conn->query("UPDATE users 
                  SET name='$name',
                      phone='$phone',
                      address='$address'
                  WHERE id='$user_id'");

    $message = "<div class='alert alert-success'>Profile Updated Successfully!</div>";

    $result = $conn->query("SELECT * FROM users WHERE id='$user_id'");
    $user = $result->fetch_assoc();
}
?>

<?php
include 'components/header.php';
include 'components/sidebar.php';
?>

<!-- MAIN CONTENT -->
<div class="main-astra w-100">
    <div class="header-astra mb-4 animate-up">
        <h4 class="fw-bold mb-0">👤 MyProfile Management</h4>
    </div>

<div class="container-fluid animate-up" style="animation-delay: 0.1s;">

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card-astra p-5 mb-5 shadow-lg">
            <h3 class="fw-bold mb-5 d-flex align-items-center gap-3">
                <span style="font-size: 24px;">👤</span> Account Settings
            </h3>

            <?php echo $message; ?>

            <div class="text-center mb-5">
                <div class="position-relative d-inline-block">
                    <?php
                    $pic = $user['profile_pic'] ?? '';
                    $has_pic = !empty($pic) && file_exists($pic);
                    if($has_pic): ?>
                        <img src="<?php echo h($pic); ?>"
                             class="rounded-circle shadow-lg"
                             style="width:130px; height:130px; object-fit:cover; border: 4px solid var(--astra-indigo);">
                    <?php else: ?>
                        <div style="width:130px; height:130px; border-radius:50%; background: var(--astra-indigo); color:#fff; display:flex; align-items:center; justify-content:center; font-size:48px; font-weight:700; border: 4px solid var(--border-color); margin: 0 auto; box-shadow: 0 8px 24px rgba(94,92,230,0.25);">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mt-3">
                    <div class="fw-bold" style="font-size:18px;"><?php echo h($user['name']); ?></div>
                    <div class="small" style="color:var(--astra-indigo); font-weight:600; text-transform:uppercase; letter-spacing:1px; font-size:11px;"><?php echo ucfirst($user['role']); ?></div>
                </div>
            </div>

            <form method="post" enctype="multipart/form-data">
                <div class="row g-4">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold opacity-75">Full Name</label>
                        <input type="text" name="name" class="form-control-astra w-100"
                        value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold opacity-75">Email Address</label>
                        <input type="email" class="form-control-astra w-100 opacity-50"
                        value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label small fw-bold opacity-75">Phone Number</label>
                        <input type="text" name="phone" class="form-control-astra w-100"
                        value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label small fw-bold opacity-75">Residential Address</label>
                        <textarea name="address" class="form-control-astra w-100" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-md-12 mb-4">
                        <label class="form-label small fw-bold opacity-75">Update Profile Picture</label>
                        <input type="file" name="profile_pic" class="form-control-astra w-100 py-3">
                        <small class="text-muted mt-2 d-block" style="font-size: 11px;">Supported formats: JPG, JPEG, PNG. Recommended 500x500px.</small>
                    </div>

                    <div class="col-md-12 text-end">
                        <button type="submit" name="update" class="btn-astra px-5 py-3">
                            Save Changes 🛡️
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

</div> <!-- End Container -->
</div> <!-- End Main -->
</div> <!-- End Wrapper -->
</body>
</html>