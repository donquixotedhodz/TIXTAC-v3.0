<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all job orders
    $stmt = $pdo->query("
        SELECT 
            jo.*,
            COALESCE(am.model_name, 'Not Specified') as model_name,
            t.name as technician_name
        FROM job_orders jo 
        LEFT JOIN aircon_models am ON jo.aircon_model_id = am.id 
        LEFT JOIN technicians t ON jo.assigned_technician_id = t.id
        WHERE jo.status != 'completed'
        ORDER BY jo.created_at DESC
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get technicians for dropdown
    $stmt = $pdo->query("SELECT id, name FROM technicians");
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get aircon models for dropdown
    $stmt = $pdo->query("SELECT id, model_name, brand FROM aircon_models");
    $airconModels = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Job Order System</title>
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
                        <li class="active">
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
                <li>
                    <a href="technicians.php">
                        <i class="fas fa-users-cog"></i>
                        Technicians
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
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['username']) ?>&background=1a237e&color=fff" alt="Admin" class="rounded-circle me-2" width="32" height="32">
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
                    <h4 class="mb-0">Job Orders</h4>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addJobOrderModal">
                        <i class="fas fa-plus me-2"></i>Add Job Order
                    </button>
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
                                        <th>Technician</th>
                                        <th>Status</th>
                                        <th>Due Date</th>
                                        <th>Price</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
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
                                            <?php if ($order['technician_name']): ?>
                                                <div class="d-flex align-items-center">
                                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($order['technician_name']) ?>&background=1a237e&color=fff" 
                                                         alt="<?= htmlspecialchars($order['technician_name']) ?>" 
                                                         class="rounded-circle me-2" 
                                                         width="24" height="24">
                                                    <?= htmlspecialchars($order['technician_name']) ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">Not Assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $order['status'] === 'completed' ? 'success' : 
                                                ($order['status'] === 'in_progress' ? 'warning' : 
                                                ($order['status'] === 'pending' ? 'danger' : 'secondary')) ?> bg-opacity-10 text-<?= $order['status'] === 'completed' ? 'success' : 
                                                ($order['status'] === 'in_progress' ? 'warning' : 
                                                ($order['status'] === 'pending' ? 'danger' : 'secondary')) ?>">
                                                <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-medium"><?= date('M d, Y', strtotime($order['due_date'])) ?></div>
                                            <?php
                                            $due_date = new DateTime($order['due_date']);
                                            $today = new DateTime();
                                            $interval = $today->diff($due_date);
                                            $days_left = $interval->days;
                                            if ($due_date < $today) {
                                                echo '<small class="text-danger">Overdue by ' . $days_left . ' days</small>';
                                            } elseif ($days_left <= 3) {
                                                echo '<small class="text-warning">Due in ' . $days_left . ' days</small>';
                                            }
                                            ?>
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
                                                <a href="edit-order.php?id=<?= $order['id'] ?>" 
                                                   class="btn btn-sm btn-light" 
                                                   data-bs-toggle="tooltip" 
                                                   title="Edit Order">
                                                    <i class="fas fa-edit text-warning"></i>
                                                </a>
                                                <?php if ($order['status'] !== 'completed'): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-light" 
                                                        data-bs-toggle="tooltip" 
                                                        title="Mark as Completed">
                                                    <i class="fas fa-check text-success"></i>
                                                </button>
                                                <?php endif; ?>
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

    <!-- Add Job Order Modal -->
    <div class="modal fade" id="addJobOrderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Job Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="process_order.php" method="POST">
                    <div class="modal-body">
                        <div class="row g-3">
                            <!-- Customer Information -->
                            <div class="col-md-6">
                                <label class="form-label">Customer Name</label>
                                <input type="text" class="form-control" name="customer_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="customer_phone" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="customer_address" rows="2" required></textarea>
                            </div>

                            <!-- Service Information -->
                            <div class="col-md-6">
                                <label class="form-label">Service Type</label>
                                <select class="form-select" name="service_type" required>
                                    <option value="">Select Service Type</option>
                                    <option value="installation">Installation</option>
                                    <option value="repair">Repair</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Aircon Model</label>
                                <select class="form-select" name="aircon_model_id">
                                    <option value="">Select Model</option>
                                    <?php foreach ($airconModels as $model): ?>
                                    <option value="<?= $model['id'] ?>"><?= htmlspecialchars($model['brand'] . ' - ' . $model['model_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Assignment Information -->
                            <div class="col-md-6">
                                <label class="form-label">Assign Technician</label>
                                <select class="form-select" name="assigned_technician_id">
                                    <option value="">Select Technician</option>
                                    <?php foreach ($technicians as $tech): ?>
                                    <option value="<?= $tech['id'] ?>"><?= htmlspecialchars($tech['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Due Date</label>
                                <input type="date" class="form-control" name="due_date" required>
                            </div>

                            <!-- Price -->
                            <div class="col-md-6">
                                <label class="form-label">Price (₱)</label>
                                <input type="number" class="form-control" name="price" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Job Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="js/dashboard.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>
</body>
</html> 