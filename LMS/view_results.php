<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user'])){
    header("Location:index.php");
    exit();
}

$user = $_SESSION['user'];

/* Only teacher or admin can view all results */
if($user['role'] != "teacher" && $user['role'] != "admin"){
    die("Access Denied");
}

/* Fetch Results with Student Names */
$result = $conn->query("
    SELECT r.*, u.name 
    FROM results r
    JOIN users u ON r.student_id = u.id
    ORDER BY r.created_at DESC
");
?>
<?php
include 'components/header.php';
include 'components/sidebar.php';
?>

<!-- MAIN CONTENT -->
<div class="main-astra w-100">
    <div class="header-astra mb-4 animate-up">
        <h4 class="fw-bold mb-0">📊 Student Performance Registry</h4>
    </div>

<div class="container-fluid animate-up" style="animation-delay: 0.1s;">

    <div class="d-flex justify-content-between align-items-center mb-5">
        <h2 class="fw-bold mb-0">📈 Comprehensive Exam Analytics</h2>
    </div>

    <?php if($result->num_rows == 0){ ?>
        <div class="card-astra p-5 text-center opacity-50">
            <div class="fs-1 mb-3">📭</div>
            <h5 class="fw-bold">No examination records found.</h5>
            <p class="small">Awaiting initial assessment synchronizations from students.</p>
        </div>
    <?php } else { ?>
        <div class="card-astra p-5 shadow-lg">
            <h5 class="fw-bold mb-4">Verified Outcome Metrics</h5>
            <div class="table-responsive">
                <table class="table-astra">
                    <thead>
                        <tr>
                            <th>Student Identity</th>
                            <th>Raw Marks</th>
                            <th>Denominator</th>
                            <th>Proficiency Index</th>
                            <th class="text-end">Temporal Stamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while($row = $result->fetch_assoc()){
                            $percentage = ($row['total'] > 0) ? round(($row['score'] / $row['total']) * 100, 2) : 0;
                            $badge_class = ($percentage >= 50) ? "badge-success" : "badge-danger";
                        ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-indigo"><?php echo h($row['name']); ?></div>
                                <div class="small opacity-50 mt-1">Profile Identifier: #<?php echo $row['student_id']; ?></div>
                            </td>
                            <td class="fw-semibold"><?php echo $row['score']; ?></td>
                            <td class="opacity-75"><?php echo $row['total']; ?></td>
                            <td>
                                <span class="badge-astra <?php echo $badge_class; ?>"><?php echo $percentage; ?>% Mastery</span>
                            </td>
                            <td class="text-end small opacity-50">
                                <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php } ?>

</div> <!-- End Container -->
</div> <!-- End Main -->
</div> <!-- End Wrapper -->
</body>
</html>