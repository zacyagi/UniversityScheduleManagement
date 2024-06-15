CREATE DATABASE ExamPlanningSystem;
USE ExamPlanningSystem;

CREATE TABLE Faculty (
    faculty_id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_name VARCHAR(100) NOT NULL
);

CREATE TABLE Department (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL,
    faculty_id INT,
    FOREIGN KEY (faculty_id) REFERENCES Faculty(faculty_id)
);

CREATE TABLE UserRole (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL
);

CREATE TABLE Employee (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT,
    department_id INT,
    faculty_id INT,
    FOREIGN KEY (role_id) REFERENCES UserRole(role_id),
    FOREIGN KEY (department_id) REFERENCES Department(department_id),
    FOREIGN KEY (faculty_id) REFERENCES Faculty(faculty_id)
);

CREATE TABLE Courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(10) NOT NULL,
    course_name VARCHAR(100) NOT NULL,
    department_id INT,
    faculty_id INT,
    FOREIGN KEY (department_id) REFERENCES Department(department_id),
    FOREIGN KEY (faculty_id) REFERENCES Faculty(faculty_id)
);

CREATE TABLE Exam (
    exam_id INT AUTO_INCREMENT PRIMARY KEY,
    exam_name VARCHAR(100) NOT NULL,
    exam_date DATE NOT NULL,
    exam_time TIME NOT NULL,
    course_id INT,
    num_classes INT NOT NULL,
    FOREIGN KEY (course_id) REFERENCES Courses(course_id)
);

CREATE TABLE AssistantExamAssignment (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT,
    assistant_id INT,
    score INT DEFAULT 0,
    FOREIGN KEY (exam_id) REFERENCES Exam(exam_id),
    FOREIGN KEY (assistant_id) REFERENCES Employee(employee_id)
);

INSERT INTO Faculty (faculty_name) VALUES 
('Engineering'), 
('Arts'), 
('Science'), 
('Business'), 
('Law');

INSERT INTO Department (department_name, faculty_id) VALUES 
('Computer Engineering', 1), 
('Electrical Engineering', 1), 
('Mechanical Engineering', 1), 
('History', 2), 
('Physics', 3);

INSERT INTO UserRole (role_name) VALUES 
('Assistant'), 
('Secretary'), 
('Head of Department'), 
('Head of Secretary'), 
('Dean');

INSERT INTO Employee (name, username, password, role_id, department_id, faculty_id) VALUES 
('John Doe', 'johndoe', '1234', 1, 1, 1), 
('Perente', 'Perente', '1234', 1, 1, 1),
('Burcu', 'burcu', '1234', 1, 1, 1),
('Gülşah', 'gulsah', '1234', 1, 1, 1),
('ahmet', 'ahmet', '1234', 1, 2, 1),
('Alice Johnson', 'alicej', '1234', 2, 1, 1), 
('headdep', 'headdep', '1234', 3, 1, 1), 
('headsec', 'headsec', '1234', 4, NULL, 1), 
('dean', 'dean', '1234', 5, NULL, 1);

INSERT INTO Courses (course_code, course_name, department_id, faculty_id) VALUES 
('CSE101', 'Introduction to Computer Engineering', 1, 1), 
('CSE211', 'Data Structures', 1, 1), 
('CSE331', 'Operating Systems', 1, 1), 
('ELE101', 'Circuit Analysis', 2, 1), 
('PHY101', 'General Physics', 5, 3),
('ES114',  'Electric114',NULL,1),
('ES211',  'Electric211',NULL,1),
('ES272',  'Electric272',NULL,1);




