<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'student'){
    header("Location:index.php");
    exit();
}

$student_id = $_SESSION['user']['id'];

$results = $conn->query("
    SELECT r.*, c.course_name
    FROM results r
    LEFT JOIN courses c ON r.quiz_id = c.id
    WHERE r.student_id = '$student_id'
    ORDER BY r.id DESC
");
?>

<?php
include 'components/header.php';
include 'components/sidebar.php';
?>

<!-- MAIN CONTENT -->
<div class="main-astra w-100">
    <div class="header-astra mb-4 animate-up">
        <h4 class="fw-bold mb-0">📊 Performance Analytics</h4>
    </div>

<div class="container-fluid animate-up" style="animation-delay: 0.1s;">

    <div class="d-flex justify-content-between align-items-center mb-5">
        <h2 class="fw-bold mb-0">📈 Assessment History</h2>
    </div>

    <?php if($results && $results->num_rows == 0){ ?>
        <div class="card-astra p-5 text-center opacity-50">
            <div class="fs-1 mb-3">📭</div>
            <h5 class="fw-bold">No academic evaluations found.</h5>
            <p class="small">Complete a quiz module to generate performance telemetry.</p>
        </div>
    <?php } else { ?>
        <div class="card-astra p-5 shadow-lg">
            <h5 class="fw-bold mb-4">Verified Outcome Metrics</h5>
            <div class="table-responsive">
                <table class="table-astra">
                    <thead>
                        <tr>
                            <th>Evaluation Module</th>
                            <th>Raw Score</th>
                            <th>Total Points</th>
                            <th>Proficiency</th>
                            <th class="text-end">Temporal Stamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $results->fetch_assoc()){ 
                            $percentage = ($row['total']>0) ? round(($row['score']/$row['total'])*100,2) : 0;
                            $badge_class = ($percentage >= 50) ? "badge-success" : "badge-danger";
                        ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-indigo"><?php echo h($row['course_name'] ?? 'Quiz Result'); ?></div>
                                <div class="small opacity-50 mt-1">Result ID: #<?php echo $row['id']; ?></div>
                            </td>
                            <td class="fw-semibold"><?php echo $row['score']; ?></td>
                            <td class="opacity-75"><?php echo $row['total']; ?></td>
                            <td>
                                <span class="badge-astra <?php echo $badge_class; ?>"><?php echo $percentage; ?>% Mastery</span>
                            </td>
                            <td class="text-end small opacity-50">
                                <?php echo isset($row['created_at']) ? date('M d, Y', strtotime($row['created_at'])) : '-'; ?>
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