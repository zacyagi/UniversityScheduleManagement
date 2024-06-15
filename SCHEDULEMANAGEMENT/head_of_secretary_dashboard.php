<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['username']) || $_SESSION['role_id'] != 4) { 
    header("Location: login.php");
    exit();
}

// Logout functionality
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Fetch head of secretary information
$username = $_SESSION['username'];
$name = $_SESSION['name'];
$faculty_id = $_SESSION['faculty_id'];

// Fetch courses for the faculty
$stmt = $conn->prepare("SELECT * FROM Courses WHERE department_id IS NULL AND faculty_id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$courses = $stmt->get_result();

// Function to check for time conflicts
function has_time_conflict($conn, $assistant_id, $exam_date, $exam_time) {
    $stmt = $conn->prepare("
        SELECT e.exam_id
        FROM AssistantExamAssignment a
        JOIN Exam e ON a.exam_id = e.exam_id
        WHERE a.assistant_id = ?
        AND e.exam_date = ?
        AND (
            (e.exam_time <= ? AND ADDTIME(e.exam_time, '02:00:00') > ?) OR
            (? <= e.exam_time AND ADDTIME(?, '02:00:00') > e.exam_time)
        )
    ");
    $stmt->bind_param("isssss", $assistant_id, $exam_date, $exam_time, $exam_time, $exam_time, $exam_time);
    $stmt->execute();
    $conflicts = $stmt->get_result();
    return $conflicts->num_rows > 0;
}

// Handle exam creation
$error_message = "";
$assigned_assistants = [];
if (isset($_POST['create_exam'])) {
    // Extract exam details from form
    $exam_name = $_POST['exam_name'];
    $exam_date = $_POST['exam_date'];
    $exam_time = $_POST['exam_time'];
    $num_classes = $_POST['num_classes'];
    $course_id = $_POST['course_id'];

    // Fetch assistants for the faculty sorted by total score ascending
    $stmt = $conn->prepare("
        SELECT e.employee_id, e.name, IFNULL(SUM(a.score), 0) AS total_score
        FROM Employee e
        LEFT JOIN AssistantExamAssignment a ON e.employee_id = a.assistant_id
        WHERE e.role_id = 1 AND e.faculty_id = ?
        GROUP BY e.employee_id
        ORDER BY total_score ASC
    ");
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $assistants = $stmt->get_result();

    // Select assistants with the lowest scores that are available
    $selected_assistants = [];
    while ($assistant = $assistants->fetch_assoc()) {
        if (!has_time_conflict($conn, $assistant['employee_id'], $exam_date, $exam_time)) {
            $selected_assistants[] = $assistant;
            if (count($selected_assistants) == $num_classes) {
                break;
            }
        }
    }

    if (count($selected_assistants) < $num_classes) {
        $error_message = "Not enough number of assistant(s).";
    } else {
        // Insert exam into the database
        $stmt = $conn->prepare("INSERT INTO Exam (exam_name, exam_date, exam_time, course_id, num_classes) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $exam_name, $exam_date, $exam_time, $course_id, $num_classes);
        $stmt->execute();

        // Fetch the newly created exam ID
        $exam_id = $stmt->insert_id;

        // Insert assistants for the newly created exam
        $stmt = $conn->prepare("INSERT INTO AssistantExamAssignment (exam_id, assistant_id, score) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE score = score + 1");
        foreach ($selected_assistants as $assistant) {
            $stmt->bind_param("ii", $exam_id, $assistant['employee_id']);
            $stmt->execute();
            $assigned_assistants[] = $assistant['name'];
        }
    }
}

// Handle course creation
$course_error_message = "";
if (isset($_POST['create_course'])) {
    // Extract course details from form
    $course_code = $_POST['course_code'];
    $course_name = $_POST['course_name'];

    // Insert course into the database
    $stmt = $conn->prepare("INSERT INTO Courses (course_code, course_name, department_id, faculty_id) VALUES (?, ?, NULL, ?)");
    $stmt->bind_param("ssi", $course_code, $course_name, $faculty_id);
    if ($stmt->execute()) {
        $course_error_message = "Course created successfully.";
    } else {
        $course_error_message = "Error creating course.";
    }
}

// Fetch assistant scores
$stmt = $conn->prepare("
    SELECT e.name, IFNULL(SUM(a.score), 0) AS total_score
    FROM Employee e
    LEFT JOIN AssistantExamAssignment a ON e.employee_id = a.assistant_id
    WHERE e.role_id = 1 AND e.faculty_id = ?
    GROUP BY e.name
");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$assistant_scores = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Head of Secretary Dashboard - Exam Planning System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .dashboard-container {
            width: 80%;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .form-container, .table-container {
            margin-bottom: 20px;
        }
        .form-container select, .form-container button {
            padding: 10px;
            margin-right: 10px;
        }
        .assistant-scores {
            margin-top: 20px;
        }
        .assistant-scores table {
            width: 50%;
            border-collapse: collapse;
        }
        .assistant-scores th, .assistant-scores td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        .assistant-scores th {
            background-color: #f4f4f4;
        }
        .error-message, .success-message {
            color: red;
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <h2>Welcome, <?php echo $name; ?></h2>
        <?php if ($error_message): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <?php if ($course_error_message): ?>
            <p class="error-message"><?php echo $course_error_message; ?></p>
        <?php endif; ?>
        <?php if (!empty($assigned_assistants)): ?>
            <p class="success-message">Exam created successfully. Assigned Assistants: <?php echo implode(', ', $assigned_assistants); ?></p>
        <?php endif; ?>
        <div class="form-container">
            <form method="post" action="head_of_secretary_dashboard.php">
                <label for="course">Select Course:</label>
                <select name="course_id" id="course" required>
                    <?php while ($course = $courses->fetch_assoc()): ?>
                        <option value="<?php echo $course['course_id']; ?>">
                            <?php echo $course['course_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <br><br>
                <label for="exam_name">Exam Name:</label>
                <input type="text" name="exam_name" id="exam_name" required>
                <br><br>
                <label for="exam_date">Exam Date:</label>
                <input type="date" name="exam_date" id="exam_date" required>
                <br><br>
                <label for="exam_time">Exam Time:</label>
                <input type="time" name="exam_time" id="exam_time" required>
                <br><br>
                <label for="num_classes">Number of Classes:</label>
                <input type="number" name="num_classes" id="num_classes" required>
                <br><br>
                <button type="submit" name="create_exam">Create Exam</button>
            </form>
        </div>
        <div class="form-container">
            <form method="post" action="head_of_secretary_dashboard.php">
                <h3>Create New Course</h3>
                <label for="course_code">Course Code:</label>
                <input type="text" name="course_code" id="course_code" required>
                <br><br>
                <label for="course_name">Course Name:</label>
                <input type="text" name="course_name" id="course_name" required>
                <br><br>
                <button type="submit" name="create_course">Create Course</button>
            </form>
        </div>
        <div class="table-container assistant-scores">
            <h3>Assistant Scores</h3>
            <table>
                <tr>
                    <th>Assistant Name</th>
                    <th>Total Score</th>
                </tr>
                <?php while ($row = $assistant_scores->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['total_score']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
        <form method="post" action="head_of_secretary_dashboard.php">
            <button type="submit" name="logout">Logout</button>
        </form>
    </div>
</body>
</html>
