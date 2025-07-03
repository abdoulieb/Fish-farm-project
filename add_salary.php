<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    $_SESSION['error'] = "You don't have permission to perform this action";
    header("Location: admin.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = $_POST['employee_id'] ?? null;
    $monthlySalary = $_POST['monthly_salary'] ?? null;
    $effectiveDate = $_POST['effective_date'] ?? null;

    try {
        $stmt = $pdo->prepare("INSERT INTO employee_salaries (employee_id, monthly_salary, effective_date) VALUES (?, ?, ?)");
        $stmt->execute([$employeeId, $monthlySalary, $effectiveDate]);

        $_SESSION['message'] = "Salary record added successfully";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding salary record: " . $e->getMessage();
    }
}

header("Location: admin.php");
exit();
<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    $_SESSION['error'] = "You don't have permission to perform this action";
    header("Location: admin.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = $_POST['employee_id'] ?? null;
    $monthlySalary = $_POST['monthly_salary'] ?? null;
    $effectiveDate = $_POST['effective_date'] ?? null;

    try {
        $stmt = $pdo->prepare("INSERT INTO employee_salaries (employee_id, monthly_salary, effective_date) VALUES (?, ?, ?)");
        $stmt->execute([$employeeId, $monthlySalary, $effectiveDate]);

        $_SESSION['message'] = "Salary record added successfully";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding salary record: " . $e->getMessage();
    }
}

header("Location: admin.php");
exit();
<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    $_SESSION['error'] = "You don't have permission to perform this action";
    header("Location: admin.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = $_POST['employee_id'] ?? null;
    $monthlySalary = $_POST['monthly_salary'] ?? null;
    $effectiveDate = $_POST['effective_date'] ?? null;

    try {
        $stmt = $pdo->prepare("INSERT INTO employee_salaries (employee_id, monthly_salary, effective_date) VALUES (?, ?, ?)");
        $stmt->execute([$employeeId, $monthlySalary, $effectiveDate]);

        $_SESSION['message'] = "Salary record added successfully";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding salary record: " . $e->getMessage();
    }
}

header("Location: admin.php");
exit();
