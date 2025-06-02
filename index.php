<?php
session_start();
require_once 'config/database.php';

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: technician/dashboard.php');
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (empty($username) || empty($password) || empty($role)) {
        $error = 'All fields are required';
    } else {
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Check admin login
            if ($role === 'admin') {
                $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = 'admin';
                    header('Location: dashboard.php');
                    exit();
                }
            }
            // Check technician login
            else if ($role === 'technician') {
                $stmt = $pdo->prepare("SELECT * FROM technicians WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = 'technician';
                    header('Location: technician/dashboard.php');
                    exit();
                }
            }

            $error = 'Invalid username or password';
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Job Order System</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --primary-blue: #1a237e;
            --light-blue: #e8eaf6;
            --hover-blue: #283593;
        }
        
        body {
            background: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }
        
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 2rem;
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header i {
            font-size: 3rem;
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }
        
        .role-selector {
            margin-bottom: 1.5rem;
        }
        
        .role-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        
        .role-card:hover {
            border-color: var(--primary-blue);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 35, 126, 0.1);
        }
        
        .role-card.selected {
            border-color: var(--primary-blue);
            background-color: var(--light-blue);
        }
        
        .role-card i {
            font-size: 2rem;
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }
        
        .role-card h5 {
            margin: 0;
            color: #333;
            font-weight: 600;
        }
        
        .role-card p {
            margin: 0.5rem 0 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .form-floating {
            margin-bottom: 1rem;
        }
        
        .form-floating > .form-control {
            padding: 1rem 0.75rem;
        }
        
        .form-floating > label {
            padding: 1rem 0.75rem;
        }
        
        .btn-login {
            background-color: var(--primary-blue);
            border: none;
            padding: 0.75rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            background-color: var(--hover-blue);
            transform: translateY(-1px);
        }
        
        .alert {
            border-radius: 8px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-circle">
                    <img src="images/logo.png" alt="Logo" class="logo-image">
                </div>
                <h2>Job Order System</h2>
                <p class="text-muted">Please login to continue</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="role-selector">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="role-card" data-role="admin">
                                <i class="fas fa-user-shield"></i>
                                <h5>Admin</h5>
                                <p>System Administrator</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="role-card" data-role="technician">
                                <i class="fas fa-user-cog"></i>
                                <h5>Technician</h5>
                                <p>Service Provider</p>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="role" id="selectedRole" value="">
                </div>

                <div class="form-floating">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                    <label for="username">Username</label>
                </div>

                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password">Password</label>
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </button>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roleCards = document.querySelectorAll('.role-card');
            const selectedRoleInput = document.getElementById('selectedRole');

            roleCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Remove selected class from all cards
                    roleCards.forEach(c => c.classList.remove('selected'));
                    // Add selected class to clicked card
                    this.classList.add('selected');
                    // Update hidden input value
                    selectedRoleInput.value = this.dataset.role;
                });
            });
        });
    </script>
</body>
</html> 