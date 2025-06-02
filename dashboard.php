<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get counts for dashboard cards
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
        FROM job_orders
    ");
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get all technicians for the dropdown
    $stmt = $pdo->query("SELECT id, name FROM technicians ORDER BY name ASC");
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent job orders
    $stmt = $pdo->query("
        SELECT 
            jo.*,
            t.name as technician_name
        FROM job_orders jo
        LEFT JOIN technicians t ON jo.assigned_technician_id = t.id
        ORDER BY jo.created_at DESC
        LIMIT 5
    ");
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Job Order System</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
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
        .technician-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s;
        }
        .technician-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }
        .technician-item img {
            width: 24px;
            height: 24px;
            border-radius: 50%;
        }
        .technician-item .name {
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sidebar-logo-glow {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .sidebar-logo-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            box-shadow: 0 0 16px 4px #2196f3, 0 0 32px 8px #1a237e33;
            border: 3px solid #fff;
            background: #fff;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="text-white">
           <div class="sidebar-header">
    <div class="sidebar-logo-glow mb-2">
        <img src="images/logo.png" alt="Logo" class="sidebar-logo-img">
    </div>

            <ul class="list-unstyled components">
                <li class="active">
                    <a href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="#jobOrdersSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="fas fa-clipboard-list"></i>
                        Job Orders
                    </a>
                    <ul class="collapse list-unstyled" id="jobOrdersSubmenu">
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
                <li>
                    <a href="#techniciansSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="fas fa-users-cog"></i>
                        Technicians
                    </a>
                    <ul class="collapse list-unstyled" id="techniciansSubmenu">
                        <li>
                            <a href="technicians.php">
                                <i class="fas fa-list"></i>
                                All Technicians
                            </a>
                        </li>
                        <?php foreach ($technicians as $tech): ?>
                        <li>
                            <a href="technician-orders.php?id=<?= $tech['id'] ?>" class="technician-item">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($tech['name']) ?>&background=1a237e&color=fff" alt="<?= htmlspecialchars($tech['name']) ?>">
                                <span class="name"><?= htmlspecialchars($tech['name']) ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
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
                <!-- Alert Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success_message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success_message']); endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error_message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error_message']); endif; ?>

                <!-- Dashboard Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card total-orders">
                            <div class="card-body text-white">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">Total Orders</h5>
                                    <i class="fas fa-clipboard-list fa-2x"></i>
                                </div>
                                <h2 class="card-text mb-2"><?= $counts['total'] ?? 0 ?></h2>
                                <p class="card-text mb-0"><small>All time</small></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card completed-orders">
                            <div class="card-body text-white">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">Completed</h5>
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                                <h2 class="card-text mb-2"><?= $counts['completed'] ?? 0 ?></h2>
                                <p class="card-text mb-0"><small>Successfully done</small></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card in-progress-orders">
                            <div class="card-body text-white">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">In Progress</h5>
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                                <h2 class="card-text mb-2"><?= $counts['in_progress'] ?? 0 ?></h2>
                                <p class="card-text mb-0"><small>Currently working</small></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card pending-orders">
                            <div class="card-body text-white">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">Pending</h5>
                                    <i class="fas fa-hourglass-half fa-2x"></i>
                                </div>
                                <h2 class="card-text mb-2"><?= $counts['pending'] ?? 0 ?></h2>
                                <p class="card-text mb-0"><small>Awaiting action</small></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="card-title mb-0">Orders Overview</h5>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-primary active">Monthly</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary">Weekly</button>
                                    </div>
                                </div>
                                <div style="height: 300px;">
                                    <canvas id="ordersChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="card-title mb-0">Technician Performance</h5>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            Last 30 Days
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#">Last 7 Days</a></li>
                                            <li><a class="dropdown-item" href="#">Last 30 Days</a></li>
                                            <li><a class="dropdown-item" href="#">Last 90 Days</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div style="height: 300px;">
                                    <canvas id="technicianChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Recent Job Orders</h5>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addJobOrderModal">
                                        <i class="fas fa-plus me-2"></i>Add Job Order
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Customer</th>
                                                <th>Service</th>
                                                <th>Technician</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td>#<?= $order['id'] ?></td>
                                                <td>
                                                    <div>
                                                        <h6 class="mb-0"><?= htmlspecialchars($order['customer_name']) ?></h6>
                                                        <small class="text-muted"><?= htmlspecialchars($order['customer_phone']) ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <h6 class="mb-0"><?= htmlspecialchars($order['service_type']) ?></h6>
                                                        <small class="text-muted"><?= htmlspecialchars($order['aircon_model']) ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($order['technician_name']): ?>
                                                    <div class="d-flex align-items-center">
                                                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($order['technician_name']) ?>&background=1a237e&color=fff" 
                                                             alt="<?= htmlspecialchars($order['technician_name']) ?>" 
                                                             class="rounded-circle me-2" width="32" height="32">
                                                        <span><?= htmlspecialchars($order['technician_name']) ?></span>
                                                    </div>
                                                    <?php else: ?>
                                                    <span class="text-muted">Unassigned</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = [
                                                        'pending' => 'warning',
                                                        'in_progress' => 'primary',
                                                        'completed' => 'success'
                                                    ][$order['status']] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?= $status_class ?> bg-opacity-10 text-<?= $status_class ?>">
                                                        <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span><?= date('M d, Y', strtotime($order['created_at'])) ?></span>
                                                        <small class="text-muted"><?= date('h:i A', strtotime($order['created_at'])) ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex justify-content-center gap-2">
                                                        <a href="view_order.php?id=<?= $order['id'] ?>" 
                                                           class="btn btn-sm btn-light" 
                                                           data-bs-toggle="tooltip" 
                                                           title="View Details">
                                                            <i class="fas fa-eye text-primary"></i>
                                                        </a>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-light" 
                                                                data-bs-toggle="tooltip" 
                                                                title="Edit Order">
                                                            <i class="fas fa-edit text-warning"></i>
                                                        </button>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-light" 
                                                                data-bs-toggle="tooltip" 
                                                                title="Delete Order">
                                                            <i class="fas fa-trash text-danger"></i>
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
                <div class="modal-body">
                    <form id="addJobOrderForm" action="add_job_order.php" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Customer Name</label>
                                <input type="text" class="form-control" name="customer_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="customer_phone" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="customer_address" rows="2" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Aircon Model</label>
                                <input type="text" class="form-control" name="aircon_model" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Aircon Type</label>
                                <select class="form-select" name="aircon_type" required>
                                    <option value="">Select Type</option>
                                    <option value="Split Type">Split Type</option>
                                    <option value="Window Type">Window Type</option>
                                    <option value="Floor Standing">Floor Standing</option>
                                    <option value="Ceiling Type">Ceiling Type</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Service Type</label>
                                <select class="form-select" name="service_type" required>
                                    <option value="">Select Service</option>
                                    <option value="Installation">Installation</option>
                                    <option value="Repair">Repair</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Cleaning">Cleaning</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Assign Technician</label>
                                <select class="form-select" name="technician_id" required>
                                    <option value="">Select Technician</option>
                                    <?php foreach ($technicians as $tech): ?>
                                    <option value="<?= $tech['id'] ?>"><?= htmlspecialchars($tech['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Job Order</button>
                        </div>
                    </form>
                </div>
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