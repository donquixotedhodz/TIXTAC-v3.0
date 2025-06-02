<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($role === 'admin') {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM technicians WHERE username = ?");
        }

        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: dashboard.php');
            } else {
                header('Location: technician/dashboard.php');
            }
            exit();
        } else {
            $_SESSION['error'] = 'Invalid username or password';
            header('Location: index.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Database error: ' . $e->getMessage();
        header('Location: login.php');
        exit();
    }
} else {
    header('Location: login.php');
    exit();
} 