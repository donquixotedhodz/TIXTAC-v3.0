<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
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
            t.name as technician_name,
            t.phone as technician_phone
        FROM job_orders jo 
        LEFT JOIN aircon_models am ON jo.aircon_model_id = am.id 
        LEFT JOIN technicians t ON jo.assigned_technician_id = t.id
        WHERE jo.id = ?
    ");
    $stmt->execute([$_GET['id']]);
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
    <link rel="stylesheet" href="css/style.css">
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
                    <a href="#jobOrdersSubmenu" data-bs-toggle="collapse" aria-expanded="true" class="dropdown-toggle">
                        <i class="fas fa-clipboard-list"></i>
                        Job Orders
                    </a>
                    <ul class="collapse show list-unstyled" id="jobOrdersSubmenu">
                        <li>
                            <a href="orders.php">
                                <i class="fas fa-file-alt"></i>
                                Orders
                            </a>
                        </li>
                        <li>
                            <a href="archived.php">
                                <i class="fas fa-archive"></i>
                                Archived
                            </a>
                        </li>
                    </ul>
                </li>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <li>
                    <a href="technicians.php">
                        <i class="fas fa-users-cog"></i>
                        Technicians
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="reports.php">
                        <i class="fas fa-chart-bar"></i>
                        Reports
                    </a>
                </li>
                <li>
                    <a href="settings.php">
                        <i class="fas fa-cog"></i>
                        Settings
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
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['username']) ?>&background=1a237e&color=fff" alt="User" class="rounded-circle me-2" width="32" height="32">
                                <span class="me-3">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="container-fluid">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-0">Job Order Details</h4>
                        <p class="text-muted mb-0">Order #<?= htmlspecialchars($order['job_order_number']) ?></p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?= $_SESSION['role'] === 'admin' ? 'orders.php' : 'technician/orders.php' ?>" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Orders
                        </a>
                        <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print
                        </button>
                    </div>
                </div>

                <!-- Order Details -->
                <div class="row">
                    <div class="col-md-8">
                        <!-- Customer Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Customer Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted">Customer Name</label>
                                        <p class="mb-0"><?= htmlspecialchars($order['customer_name']) ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted">Phone Number</label>
                                        <p class="mb-0"><?= htmlspecialchars($order['customer_phone']) ?></p>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label text-muted">Address</label>
                                        <p class="mb-0"><?= htmlspecialchars($order['customer_address']) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Service Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Service Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted">Service Type</label>
                                        <p class="mb-0">
                                            <span class="badge bg-info bg-opacity-10 text-info">
                                                <?= ucfirst(htmlspecialchars($order['service_type'])) ?>
                                            </span>
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted">Aircon Model</label>
                                        <p class="mb-0"><?= htmlspecialchars($order['model_name']) ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted">Price</label>
                                        <p class="mb-0 fw-semibold">₱<?= number_format($order['price'], 2) ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted">Due Date</label>
                                        <p class="mb-0"><?= date('M d, Y', strtotime($order['due_date'])) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- Status Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Status Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label text-muted">Current Status</label>
                                    <p class="mb-0">
                                        <span class="badge bg-<?= $order['status'] === 'completed' ? 'success' : 
                                            ($order['status'] === 'in_progress' ? 'warning' : 
                                            ($order['status'] === 'pending' ? 'danger' : 'secondary')) ?> bg-opacity-10 text-<?= $order['status'] === 'completed' ? 'success' : 
                                            ($order['status'] === 'in_progress' ? 'warning' : 
                                            ($order['status'] === 'pending' ? 'danger' : 'secondary')) ?>">
                                            <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted">Created Date</label>
                                    <p class="mb-0"><?= date('M d, Y H:i', strtotime($order['created_at'])) ?></p>
                                </div>
                                <?php if ($order['completed_at']): ?>
                                <div class="mb-3">
                                    <label class="form-label text-muted">Completed Date</label>
                                    <p class="mb-0"><?= date('M d, Y H:i', strtotime($order['completed_at'])) ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Technician Information -->
                        <?php if ($order['technician_name']): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Assigned Technician</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($order['technician_name']) ?>&background=1a237e&color=fff" 
                                         alt="<?= htmlspecialchars($order['technician_name']) ?>" 
                                         class="rounded-circle me-3" 
                                         width="48" height="48">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($order['technician_name']) ?></h6>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($order['technician_phone']) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Action Buttons -->
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Actions</h5>
                                <div class="d-grid gap-2">
                                    <?php if ($_SESSION['role'] === 'admin'): ?>
                                        <?php if ($order['status'] !== 'completed'): ?>
                                        <a href="edit-order.php?id=<?= $order['id'] ?>" class="btn btn-warning">
                                            <i class="fas fa-edit me-2"></i>Edit Order
                                        </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($order['status'] === 'pending'): ?>
                                        <a href="update-status.php?id=<?= $order['id'] ?>&status=in_progress" class="btn btn-warning">
                                            <i class="fas fa-play me-2"></i>Start Work
                                        </a>
                                        <?php elseif ($order['status'] === 'in_progress'): ?>
                                        <a href="update-status.php?id=<?= $order['id'] ?>&status=completed" class="btn btn-success">
                                            <i class="fas fa-check me-2"></i>Mark as Completed
                                        </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                                        <i class="fas fa-print me-2"></i>Print Details
                                    </button>
                                </div>
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
    <script src="js/dashboard.js"></script>
</body>
</html> 