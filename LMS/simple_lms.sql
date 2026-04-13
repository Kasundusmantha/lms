USE simple_lms;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100),
    password VARCHAR(100),
    role VARCHAR(20)
);

CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(100),
    teacher_id INT
);

CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    course_id INT
);

CREATE TABLE quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(255),
    option1 VARCHAR(100),
    option2 VARCHAR(100),
    correct_answer VARCHAR(100)
);

-- NOTE: Run update_passwords.php after importing this database to securely hash these default '123' passwords
INSERT INTO users (name,email,password,role) VALUES
('Admin','admin@gmail.com','123','admin'),
('Teacher1','teacher@gmail.com','123','teacher'),
('Student1','student@gmail.com','123','student');