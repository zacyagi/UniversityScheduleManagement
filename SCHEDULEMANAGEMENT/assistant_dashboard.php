<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['username']) || $_SESSION['role_id'] != 1) {
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

// Fetch assistant information
$username = $_SESSION['username'];
$name = $_SESSION['name'];

// Fetch assistant ID
$stmt = $conn->prepare("SELECT employee_id FROM Employee WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$assistant_id = $stmt->get_result()->fetch_assoc()['employee_id'];

// Fetch courses for dropdown menu
$stmt = $conn->prepare("SELECT * FROM Courses WHERE department_id = (SELECT department_id FROM Employee WHERE username = ?)");
$stmt->bind_param("s", $username);
$stmt->execute();
$courses = $stmt->get_result();

// Handle course addition
$message = "";
if (isset($_POST['add_course'])) {
    $course_id = $_POST['course_id'];
    $schedule_date = $_POST['schedule_date'];
    $schedule_time = $_POST['schedule_time'];
    
    // Check for conflicts
    $stmt = $conn->prepare("
        SELECT 1 FROM AssistantExamAssignment a
        JOIN Exam e ON a.exam_id = e.exam_id
        WHERE a.assistant_id = ? AND e.exam_date = ? AND e.exam_time = ?
    ");
    $stmt->bind_param("iss", $assistant_id, $schedule_date, $schedule_time);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $message = "You already have an exam or lesson scheduled at this time.";
    } else {
        // Insert new exam 
        $stmt = $conn->prepare("INSERT INTO Exam (exam_name, exam_date, exam_time, course_id, num_classes) VALUES (?, ?, ?, ?, ?)");
        $exam_name = "Assistant Schedule";
        $num_classes = 1;
        $stmt->bind_param("sssii", $exam_name, $schedule_date, $schedule_time, $course_id, $num_classes);
        $stmt->execute();
        
        // Get the last inserted exam_id
        $exam_id = $conn->insert_id;

        // Insert schedule into the AssistantExamAssignment table
        $stmt = $conn->prepare("INSERT INTO AssistantExamAssignment (exam_id, assistant_id, score) VALUES (?, ?, 0)");
        $stmt->bind_param("ii", $exam_id, $assistant_id);
        $stmt->execute();

        $message = "Schedule added successfully.";
    }
}

// Determine the current week number
$current_week = date('W');

// Handle the selected week
$selected_week = isset($_POST['week']) ? $_POST['week'] : $current_week;

// Fetch weekly schedule
$weekly_schedule = [];
$days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
$time_slots = ["08:00-10:00", "10:00-12:00", "12:00-14:00", "14:00-16:00", "16:00-18:00"];

// Get the selected week's date range
$start_of_week = new DateTime();
$start_of_week->setISODate(date('Y'), $selected_week);
$start_of_week->modify('monday this week');
$end_of_week = clone $start_of_week;
$end_of_week->modify('sunday this week');

// Fetch courses and exams for the assistant
$stmt = $conn->prepare("
    SELECT c.course_name, e.exam_name, e.exam_date, e.exam_time
    FROM AssistantExamAssignment a
    JOIN Exam e ON a.exam_id = e.exam_id
    JOIN Courses c ON e.course_id = c.course_id
    WHERE a.assistant_id = ?
    AND e.exam_date BETWEEN ? AND ?
");
$start_of_week_str = $start_of_week->format('Y-m-d');
$end_of_week_str = $end_of_week->format('Y-m-d');
$stmt->bind_param("iss", $assistant_id, $start_of_week_str, $end_of_week_str);
$stmt->execute();
$schedule_items = $stmt->get_result();

while ($item = $schedule_items->fetch_assoc()) {
    $date = new DateTime($item['exam_date']);
    $day = $date->format('l');
    $time = $item['exam_time'];
    $exam_display = $item['exam_name'] . " (" . $item['course_name'] . ")";
    
    // Assign the exam to the correct time slot
    foreach ($time_slots as $slot) {
        list($slot_start, $slot_end) = explode('-', $slot);
        if ($time >= $slot_start && $time < $slot_end) {
            $weekly_schedule[$day][$slot] = $exam_display;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assistant Dashboard - Exam Planning System</title>
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
        .form-container select, .form-container input, .form-container button {
            padding: 10px;
            margin-right: 10px;
        }
        .weekly-table {
            width: 100%;
            border-collapse: collapse;
        }
        .weekly-table th, .weekly-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        .weekly-table th {
            background-color: #f4f4f4;
        }
    </style>
    <script>
        function submitForm() {
            document.getElementById("weekForm").submit();
        }
    </script>
</head>
<body>
    <div class="dashboard-container">
        <h2>Welcome, <?php echo htmlspecialchars($name); ?></h2>
        <div class="form-container">
            <form method="post" action="assistant_dashboard.php">
                <label for="course">Select Course:</label>
                <select name="course_id" id="course" required>
                    <?php while ($course = $courses->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($course['course_id']); ?>">
                            <?php echo htmlspecialchars($course['course_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <label for="schedule_date">Date:</label>
                <input type="date" name="schedule_date" id="schedule_date" required>
                <label for="schedule_time">Time:</label>
                <input type="time" name="schedule_time" id="schedule_time" required>
                <button type="submit" name="add_course">Add</button>
            </form>
        </div>
        <?php if ($message): ?>
            <p><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <div class="form-container">
            <form id="weekForm" method="post" action="assistant_dashboard.php">
                <label for="week">Select Week:</label>
                <select id="week" name="week" onchange="submitForm()">
                    <?php for ($i = 0; $i < 12; $i++): ?>
                        <option value="<?php echo $current_week + $i; ?>" <?php if ($selected_week == $current_week + $i) echo 'selected'; ?>>
                            Week <?php echo $current_week + $i; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>
        <div class="table-container">
            <h3>Weekly Schedule (<?php echo $start_of_week->format('d/m/Y'); ?> - <?php echo $end_of_week->format('d/m/Y'); ?>)</h3>
            <table class="weekly-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <?php foreach ($days as $day): ?>
                            <th><?php echo $day; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($time_slots as $time_slot): ?>
                        <tr>
                            <td><?php echo $time_slot; ?></td>
                            <?php foreach ($days as $day): ?>
                                <td>
                                    <?php echo isset($weekly_schedule[$day][$time_slot]) ? htmlspecialchars($weekly_schedule[$day][$time_slot]) : ''; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <form method="post" action="assistant_dashboard.php">
            <button type="submit">Refresh</button>
            <button type="submit" name="logout">Logout</button>
        </form>
    </div>
</body>
</html>
