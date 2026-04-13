<?php
include 'config.php';

$data = $conn->query("
SELECT users.name, results.score 
FROM results 
JOIN users ON users.id = results.student_id
");

$names=[];
$scores=[];

while($row=$data->fetch_assoc()){
    $names[]=$row['name'];
    $scores[]=$row['score'];
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container mt-5">
<h3>Student Performance Graph</h3>
<canvas id="myChart"></canvas>
</div>

<script>
const ctx = document.getElementById('myChart');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($names); ?>,
        datasets: [{
            label: 'Scores',
            data: <?php echo json_encode($scores); ?>,
            backgroundColor: '#4e73df'
        }]
    }
});
</script>