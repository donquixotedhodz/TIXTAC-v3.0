<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a technician
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') {
    header('Location: ../index.php');
    exit();
}

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit();
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get order details
    $stmt = $pdo->prepare("
        SELECT 
            jo.*,
            COALESCE(am.model_name, 'Not Specified') as model_name,
            COALESCE(am.brand, 'Not Specified') as brand
        FROM job_orders jo 
        LEFT JOIN aircon_models am ON jo.aircon_model_id = am.id 
        WHERE jo.id = ? AND jo.assigned_technician_id = ?
    ");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        header('Location: orders.php');
        exit();
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order - Job Order System</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="text-white">
            <div class="sidebar-header">
                <h3><i class="fas fa-tools me-2"></i>Job Order System</h3>
            </div>

            <ul class="list-unstyled components">
                <li>
                    <a href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>
                <li class="active">
                    <a href="orders.php">
                        <i class="fas fa-clipboard-list"></i>
                        My Orders
                    </a>
                </li>
                <li>
                    <a href="profile.php">
                        <i class="fas fa-user"></i>
                        Profile
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-white">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="ms-auto d-flex align-items-center">
                        <div class="dropdown">
                            <a class="d-flex align-items-center text-decoration-none dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['username']) ?>&background=1a237e&color=fff" alt="Technician" class="rounded-circle me-2" width="32" height="32">
                                <span class="me-3">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="container-fluid">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-0">Order Details</h4>
                        <p class="text-muted mb-0">Order #<?= htmlspecialchars($order['job_order_number']) ?></p>
                    </div>
                    <a href="orders.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Orders
                    </a>
                </div>

                <!-- Order Details -->
                <div class="row">
                    <!-- Main Information -->
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Order Information</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Service Type</label>
                                        <div>
                                            <span class="badge bg-info">
                                                <?= ucfirst(htmlspecialchars($order['service_type'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Status</label>
                                        <div>
                                            <?php
                                            $statusClass = [
                                                'pending' => 'warning',
                                                'in_progress' => 'primary',
                                                'completed' => 'success'
                                            ][$order['status']];
                                            ?>
                                            <span class="badge bg-<?= $statusClass ?>">
                                                <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Due Date</label>
                                        <div>
                                            <?php
                                            $dueDate = new DateTime($order['due_date']);
                                            $today = new DateTime();
                                            $isOverdue = $dueDate < $today && $order['status'] !== 'completed';
                                            ?>
                                            <div class="<?= $isOverdue ? 'text-danger' : '' ?>">
                                                <?= $dueDate->format('M d, Y') ?>
                                                <?php if ($isOverdue): ?>
                                                    <i class="fas fa-exclamation-circle ms-1" title="Overdue"></i>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Price</label>
                                        <div class="fw-semibold">₱<?= number_format($order['price'], 2) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Information -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Customer Information</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Name</label>
                                        <div><?= htmlspecialchars($order['customer_name']) ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Phone Number</label>
                                        <div><?= htmlspecialchars($order['customer_phone']) ?></div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label text-muted">Address</label>
                                        <div><?= htmlspecialchars($order['customer_address']) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Aircon Information -->
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Aircon Information</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Brand</label>
                                        <div><?= htmlspecialchars($order['brand']) ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Model</label>
                                        <div><?= htmlspecialchars($order['model_name']) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Actions</h5>
                                <?php if ($order['status'] === 'pending'): ?>
                                    <a href="../update-status.php?id=<?= $order['id'] ?>&status=in_progress" class="btn btn-primary w-100 mb-2">
                                        <i class="fas fa-play me-2"></i>Start Work
                                    </a>
                                <?php elseif ($order['status'] === 'in_progress'): ?>
                                    <a href="../update-status.php?id=<?= $order['id'] ?>&status=completed" class="btn btn-success w-100">
                                        <i class="fas fa-check me-2"></i>Mark as Completed
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../js/dashboard.js"></script>
</body>
</html> 