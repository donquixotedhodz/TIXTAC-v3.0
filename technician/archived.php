<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a technician
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') {
    header('Location: ../index.php');
    exit();
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get completed orders
    $stmt = $pdo->prepare("
        SELECT 
            jo.*,
            COALESCE(am.model_name, 'Not Specified') as model_name 
        FROM job_orders jo 
        LEFT JOIN aircon_models am ON jo.aircon_model_id = am.id 
        WHERE jo.assigned_technician_id = ? 
        AND jo.status = 'completed'
        ORDER BY jo.completed_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $completedOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Orders - Job Order System</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <style>
        #sidebar .components li a[aria-expanded="true"] {
            background: rgba(255, 255, 255, 0.1);
        }
        #sidebar .components li .collapse {
            padding-left: 1rem;
        }
        #sidebar .components li .collapse a {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        #sidebar .components li .collapse a:hover {
            background: rgba(255, 255, 255, 0.1);
        }
    </style>
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
                        <li class="active">
                            <a href="archived.php">
                                <i class="fas fa-archive"></i>
                                Archived
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="profile.php">
                        <i class="fas fa-user"></i>
                        Profile
                    </a>
                </li>
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
                    <h4 class="mb-0">Archived Orders</h4>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print
                        </button>
                        <button type="button" class="btn btn-outline-primary">
                            <i class="fas fa-file-export me-2"></i>Export
                        </button>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Service Type</th>
                                        <th>Model</th>
                                        <th>Completed Date</th>
                                        <th>Price</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($completedOrders as $order): ?>
                                    <tr>
                                        <td>
                                            <span class="fw-semibold"><?= htmlspecialchars($order['job_order_number']) ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-medium"><?= htmlspecialchars($order['customer_name']) ?></div>
                                            <small class="text-muted d-block"><?= htmlspecialchars($order['customer_phone']) ?></small>
                                            <small class="text-muted d-block text-truncate" style="max-width: 200px;"><?= htmlspecialchars($order['customer_address']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info bg-opacity-10 text-info">
                                                <?= ucfirst(htmlspecialchars($order['service_type'])) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($order['model_name']) ?></td>
                                        <td>
                                            <div class="fw-medium"><?= date('M d, Y', strtotime($order['completed_at'])) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">₱<?= number_format($order['price'], 2) ?></div>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="view-order.php?id=<?= $order['id'] ?>" 
                                                   class="btn btn-sm btn-light" 
                                                   data-bs-toggle="tooltip" 
                                                   title="View Details">
                                                    <i class="fas fa-eye text-primary"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-light" 
                                                        data-bs-toggle="tooltip" 
                                                        title="Print Receipt">
                                                    <i class="fas fa-receipt text-success"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>
</body>
</html> 