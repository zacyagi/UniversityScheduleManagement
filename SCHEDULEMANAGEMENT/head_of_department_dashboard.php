<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['username']) || $_SESSION['role_id'] != 3) { 
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

// Fetch head of department information
$username = $_SESSION['username'];
$name = $_SESSION['name'];

// Fetch the department for the logged-in head of department
$stmt = $conn->prepare("
    SELECT d.department_id, d.department_name 
    FROM Department d 
    JOIN Employee e ON d.department_id = e.department_id 
    WHERE e.username = ?
");
$stmt->bind_param("s", $username);
$stmt->execute();
$department = $stmt->get_result()->fetch_assoc();
$department_id = $department['department_id'];
$department_name = $department['department_name'];

// Fetch exam schedule for the department in ascending order of date and time
$stmt = $conn->prepare("
    SELECT e.exam_name, e.exam_date, e.exam_time, c.course_name, e.num_classes
    FROM Exam e
    JOIN Courses c ON e.course_id = c.course_id
    WHERE c.department_id = ?
    ORDER BY e.exam_date ASC, e.exam_time ASC
");
$stmt->bind_param("i", $department_id);
$stmt->execute();
$exam_schedule = $stmt->get_result();

// Fetch assistant scores and calculate percentages
$stmt = $conn->prepare("
    SELECT e.name, IFNULL(SUM(a.score), 0) AS total_score
    FROM Employee e
    LEFT JOIN AssistantExamAssignment a ON e.employee_id = a.assistant_id
    WHERE e.role_id = 1 AND e.department_id = ?
    GROUP BY e.name
");
$stmt->bind_param("i", $department_id);
$stmt->execute();
$assistant_scores = $stmt->get_result();

$total_score = 0;
$assistants = [];
while ($row = $assistant_scores->fetch_assoc()) {
    $total_score += $row['total_score'];
    $assistants[] = $row;
}

function calculate_percentage($score, $total) {
    return ($total > 0) ? ($score / $total) * 100 : 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Head of Department Dashboard - Exam Planning System</title>
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
        .table-container {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #f4f4f4;
        }
        .assistant-scores table {
            width: 50%;
        }
        .logout-button {
            text-align: center;
            margin-top: 20px;
        }
        .logout-button button {
            padding: 10px 20px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <h2>Welcome, <?php echo $name; ?></h2>
        <div class="table-container">
            <h3>Exam Schedule for <?php echo $department_name; ?></h3>
            <table>
                <thead>
                    <tr>
                        <th>Exam Name</th>
                        <th>Course Name</th>
                        <th>Exam Date</th>
                        <th>Exam Time</th>
                        <th>Number of Classes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($exam = $exam_schedule->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $exam['exam_name']; ?></td>
                            <td><?php echo $exam['course_name']; ?></td>
                            <td><?php echo $exam['exam_date']; ?></td>
                            <td><?php echo $exam['exam_time']; ?></td>
                            <td><?php echo $exam['num_classes']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="assistant-scores">
            <h3>Assistant Workloads</h3>
            <table>
                <thead>
                    <tr>
                        <th>Assistant Name</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assistants as $assistant): ?>
                        <tr>
                            <td><?php echo $assistant['name']; ?></td>
                            <td><?php echo number_format(calculate_percentage($assistant['total_score'], $total_score), 2); ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="logout-button">
            <form method="post" action="">
                <button type="submit" name="logout">Logout</button>
            </form>
        </div>
    </div>
</body>
</html>
