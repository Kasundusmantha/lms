<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user'])){
    header("Location:index.php");
    exit();
}

$user     = $_SESSION['user'];
$score    = 0;
$total    = 0;
$course_id = 0;

if(isset($_POST['answer']) && is_array($_POST['answer'])) {
    foreach($_POST['answer'] as $qid => $selected_option) {
        $qid  = intval($qid);
        $total++;

        $qQuery = $conn->query("SELECT course_id, correct_answer FROM quizzes WHERE id='$qid'");
        if($qQuery && $qQuery->num_rows > 0) {
            $qRow      = $qQuery->fetch_assoc();
            $course_id = $qRow['course_id'];

            if(strtoupper($selected_option) == strtoupper($qRow['correct_answer'])) {
                $score++;
            }
        }
    }
}

if($total > 0) {
    // Check if results table has course_id column, save accordingly
    $colCheck = $conn->query("SHOW COLUMNS FROM results LIKE 'course_id'");
    if($colCheck && $colCheck->num_rows > 0) {
        // Preferred: use course_id column
        $conn->query("INSERT INTO results (student_id, course_id, score, total)
                      VALUES ('".$user['id']."', '$course_id', '$score', '$total')");
    } else {
        // Fallback: old schema with quiz_id
        $conn->query("INSERT INTO results (student_id, quiz_id, score, total)
                      VALUES ('".$user['id']."', '$course_id', '$score', '$total')");
    }

    // Reward XP
    $conn->query("UPDATE users SET xp = xp + 50 WHERE id='".$user['id']."'");
    $_SESSION['user']['xp'] = ($_SESSION['user']['xp'] ?? 0) + 50;
}

$percentage = ($total > 0) ? round(($score / $total) * 100) : 0;
$passed = $percentage >= 50;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Result | Astra LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script>
        (function(){
            const t = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
    <style>
        .result-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-main);
            padding: 40px 20px;
        }
        .result-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 56px 48px;
            max-width: 480px;
            width: 100%;
            box-shadow: var(--shadow-lg);
            text-align: center;
        }
        .score-ring {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            margin: 0 auto 28px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            font-weight: 700;
        }
    </style>
</head>
<body>
<div class="result-wrapper">
    <div class="result-card">

        <div class="score-ring" style="background: <?php echo $passed ? 'rgba(48,209,88,0.12)' : 'rgba(255,55,95,0.12)' ?>; border: 4px solid <?php echo $passed ? '#30D158' : '#FF375F' ?>;">
            <div style="font-size: 36px;"><?php echo $passed ? '🏆' : '📚'; ?></div>
        </div>

        <h2 class="fw-bold mb-1" style="color: var(--text-main);">
            <?php echo $passed ? 'Excellent Work!' : 'Keep Practicing!'; ?>
        </h2>
        <p class="mb-5" style="color: var(--text-muted); font-size: 14px;">
            Quiz assessment has been recorded.
        </p>

        <div class="p-4 rounded-4 mb-4" style="background: var(--astra-indigo-soft); border: 1px solid var(--border-color);">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 6px;">Your Score</div>
            <div style="font-size: 52px; font-weight: 700; color: var(--astra-indigo); line-height: 1;">
                <?php echo $score; ?> <span style="font-size: 22px; color: var(--text-muted); font-weight: 400;">/ <?php echo $total; ?></span>
            </div>
            <div style="margin-top: 10px; font-size: 18px; font-weight: 600; color: <?php echo $passed ? '#30D158' : '#FF375F'; ?>">
                <?php echo $percentage; ?>% <?php echo $passed ? '✅ Passed' : '❌ Failed'; ?>
            </div>
        </div>

        <p style="color: var(--astra-warning); font-weight: 600; font-size: 13px; margin-bottom: 24px;">
            ⭐ +50 XP Added to Your Profile!
        </p>

        <a href="dashboard.php" class="btn-astra w-100 py-3 fw-bold" style="font-size: 15px;">
            Return to Dashboard 🏠
        </a>

    </div>
</div>
</body>
</html>