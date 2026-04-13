<?php
include 'config.php';
try {
    $conn->query("ALTER TABLE quizzes ADD COLUMN course_id INT;");
    $conn->query("ALTER TABLE quizzes ADD COLUMN option3 VARCHAR(100);");
    $conn->query("ALTER TABLE quizzes ADD COLUMN option4 VARCHAR(100);");
    $conn->query("ALTER TABLE quizzes ADD COLUMN question_order INT DEFAULT 0;");
    echo "Quizzes table patched successfully!";
} catch (Exception $e) {
    echo "Already patched or error: " . $e->getMessage();
}
?>
