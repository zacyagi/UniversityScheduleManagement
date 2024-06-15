<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['username']) || $_SESSION['role_id'] != 5) {
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

// Fetch dean information
$username = $_SESSION['username'];
$name = $_SESSION['name'];

// Fetch departments under the dean's faculty
$stmt = $conn->prepare("
    SELECT department_id, department_name 
    FROM Department 
    WHERE faculty_id = ?
");
$stmt->bind_param("i", $_SESSION['faculty_id']);
$stmt->execute();
$departments = $stmt->get_result();

// Fetch exams for the selected department
$exams = [];
if (isset($_POST['department_id'])) {
    $department_id = $_POST['department_id'];
    $stmt = $conn->prepare("
        SELECT exam_name, exam_date, exam_time
        FROM Exam
        WHERE course_id IN (
            SELECT course_id FROM Courses WHERE department_id = ?
        )
        ORDER BY exam_date, exam_time ASC
    ");
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $exams = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dean Dashboard - Exam Planning System</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #f4f4f4;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <h2>Welcome, <?php echo $name; ?></h2>
        <form method="post" action="dean_dashboard.php" class="form-container">
            <label for="department">Select Department:</label>
            <select name="department_id" id="department" onchange="this.form.submit()" required>
                <option value="">Select Department</option>
                <?php while ($department = $departments->fetch_assoc()): ?>
                    <option value="<?php echo $department['department_id']; ?>">
                        <?php echo $department['department_name']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </form>
        <?php if (!empty($exams)): ?>
            <div class="table-container">
                <h3>Exams</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Exam Name</th>
                            <th>Date</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($exam = $exams->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $exam['exam_name']; ?></td>
                                <td><?php echo $exam['exam_date']; ?></td>
                                <td><?php echo $exam['exam_time']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <form method="post" action="">
            <button type="submit" name="logout">Logout</button>
        </form>
    </div>
</body>
</html>
