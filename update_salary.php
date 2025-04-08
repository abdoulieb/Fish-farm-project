<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    $_SESSION['error'] = "You don't have permission to perform this action";
    header("Location: admin.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $employeeId = $_POST['employee_id'] ?? null;
    $monthlySalary = $_POST['monthly_salary'] ?? null;
    $effectiveDate = $_POST['effective_date'] ?? null;

    try {
        $stmt = $pdo->prepare("UPDATE employee_salaries SET employee_id = ?, monthly_salary = ?, effective_date = ? WHERE id = ?");
        $stmt->execute([$employeeId, $monthlySalary, $effectiveDate, $id]);

        $_SESSION['message'] = "Salary record updated successfully";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating salary record: " . $e->getMessage();
    }
}

header("Location: admin.php");
exit();
