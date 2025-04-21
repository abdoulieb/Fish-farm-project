<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    header("Location: dashboard.php");
    exit();
}

$fishTypes = getAllFishTypes();
$inventory = getInventory();
$orders = getOrders();

// Get all employees for location assignment
$employees = $pdo->query("SELECT id, username FROM users WHERE role = 'employee'")->fetchAll();

// Get all locations
$locations = $pdo->query("SELECT * FROM locations")->fetchAll();

// Get fatality summary
$fatalitySummary = $pdo->query("
    SELECT ft.name, 
           SUM(ff.quantity) as total_fatalities,
           (SELECT SUM(fingerlings_quantity) FROM detailed_costs dc WHERE dc.fish_type_id = ft.id) as total_fingerlings,
           (SELECT SUM(fingerlings_quantity) FROM detailed_costs dc WHERE dc.fish_type_id = ft.id) - SUM(ff.quantity) as live_count
    FROM fish_fatalities ff
    JOIN fish_types ft ON ff.fish_type_id = ft.id
    GROUP BY ff.fish_type_id
")->fetchAll();

// Get daily sales by employee
$dailySales = $pdo->query("
    SELECT u.username, 
           DATE(s.sale_date) as sale_day,
           COUNT(s.id) as sales_count,
           SUM(s.total_amount) as total_sales,
           SUM(si.quantity_kg) as total_kg
    FROM sales s
    JOIN users u ON s.employee_id = u.id
    JOIN sale_items si ON s.id = si.sale_id
    GROUP BY u.id, DATE(s.sale_date)
    ORDER BY sale_day DESC, u.username
")->fetchAll();

// Get current location assignments
$assignments = $pdo->query("
    SELECT l.name as location_name, u.username, ft.name as fish_name, li.quantity
    FROM location_inventory li
    JOIN locations l ON li.location_id = l.id
    JOIN users u ON li.employee_id = u.id
    JOIN fish_types ft ON li.fish_type_id = ft.id
    ORDER BY l.name, u.username
")->fetchAll();

// Contact submissions query
$statusFilter = $_GET['status'] ?? 'all';
$contactsQuery = "SELECT * FROM contact_submissions WHERE 1=1";

if ($statusFilter === 'unread') {
    $contactsQuery .= " AND (status IS NULL OR status != 'responded')";
} elseif ($statusFilter === 'responded') {
    $contactsQuery .= " AND status = 'responded'";
}

$contactsQuery .= " ORDER BY submitted_at DESC";
$contacts = $pdo->query($contactsQuery)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Fish Inventory Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .tab-content {
            padding: 20px 0;
            border-left: 1px solid #dee2e6;
            border-right: 1px solid #dee2e6;
            border-bottom: 1px solid #dee2e6;
            border-radius: 0 0 5px 5px;
        }

        .location-assignment {
            margin-bottom: 20px;
        }

        .badge {
            font-size: 0.9rem;
        }

        .card {
            margin-bottom: 20px;
        }

        .nav-tabs {
            margin-top: 20px;
        }

        .table-responsive {
            overflow-x: auto;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Fish Farm</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">My Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin.php">Admin</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cost_management.php">Cost Management</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profit_analysis.php">Profit Analysis</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="employee_performance.php">Employee Performance</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contacts" data-bs-toggle="tab">
                            Messages
                            <?php
                            $unreadCount = $pdo->query("SELECT COUNT(*) FROM contact_submissions WHERE status IS NULL OR status != 'responded'")->fetchColumn();
                            if ($unreadCount > 0): ?>
                                <span class="badge bg-danger"><?= $unreadCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout (<?= htmlspecialchars($_SESSION['username']) ?>)</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Admin Dashboard</h2>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory" type="button" role="tab">Inventory</button>
            </li>

            <li class="nav-item" role="presentation">
                <a class="nav-link" href="location_management.php">Location Management</a>
            </li>
            <?php if (isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="manage_employees.php">Manage Employees</a>
                </li>
            <?php endif; ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="contacts-tab" data-bs-toggle="tab" data-bs-target="#contacts" type="button" role="tab">Customer Messages</button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="adminTabsContent">
            <!-- Inventory Tab -->
            <div class="tab-pane fade show active" id="inventory" role="tabpanel">
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5>Inventory Management</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Fish Type</th>
                                            <th>Quantity (kg)</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($inventory as $item): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($item['name']) ?></td>
                                                <td><?= number_format($item['quantity_kg'], 2) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateInventoryModal"
                                                        data-id="<?= $item['fish_type_id'] ?>" data-name="<?= htmlspecialchars($item['name']) ?>"
                                                        data-quantity="<?= $item['quantity_kg'] ?>">
                                                        Update
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5>Fish Types</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Price/kg</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($fishTypes as $fish): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($fish['name']) ?></td>
                                                <td>D<?= number_format($fish['price_per_kg'], 2) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editFishModal"
                                                        data-id="<?= $fish['id'] ?>" data-name="<?= htmlspecialchars($fish['name']) ?>"
                                                        data-description="<?= htmlspecialchars($fish['description']) ?>"
                                                        data-price="<?= $fish['price_per_kg'] ?>">
                                                        Edit
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addFishModal">
                                    Add New Fish Type
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card mt-4">
                    <div class="card-header bg-primary text-white">
                        <h5>All Orders</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>phone</th>
                                    <th>Address</th>
                                    <th>email</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?= $order['id'] ?></td>
                                        <td><?= htmlspecialchars($order['username']) ?></td>
                                        <td><?= date('M d, Y H:i', strtotime($order['order_date'])) ?></td>
                                        <td><?= htmlspecialchars($order['phone'] ?? $pdo->query("SELECT phone FROM users WHERE id = {$order['user_id']}")->fetchColumn() ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($order['address'] ?? $pdo->query("SELECT address FROM users WHERE id = {$order['user_id']}")->fetchColumn() ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($order['email'] ?? $pdo->query("SELECT email FROM users WHERE id = {$order['user_id']}")->fetchColumn() ?? 'N/A') ?></td>

                                        <td>D<?= number_format($order['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="badge 
                                <?= $order['status'] === 'completed' ? 'bg-success' : ($order['status'] === 'processing' ? 'bg-primary' : ($order['status'] === 'cancelled' ? 'bg-danger' : 'bg-secondary')) ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#orderDetailsModal"
                                                data-id="<?= $order['id'] ?>">
                                                Details
                                            </button>

                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                    Change Status
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <form method="POST" action="update_order_status.php" style="display:inline;">
                                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                            <input type="hidden" name="status" value="pending">
                                                            <button type="submit" class="dropdown-item">Pending</button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="update_order_status.php" style="display:inline;">
                                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                            <input type="hidden" name="status" value="processing">
                                                            <button type="submit" class="dropdown-item">Processing</button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="update_order_status.php" style="display:inline;">
                                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                            <input type="hidden" name="status" value="completed">
                                                            <button type="submit" class="dropdown-item">Completed</button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="update_order_status.php" style="display:inline;">
                                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                            <input type="hidden" name="status" value="cancelled">
                                                            <button type="submit" class="dropdown-item text-danger">Cancel Order</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>

                                            <?php if ($order['status'] === 'pending'): ?>
                                                <form method="POST" action="update_order_status.php" style="display:inline;">
                                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                    <input type="hidden" name="status" value="cancelled">
                                                    <button type="submit" class="btn btn-danger btn-sm ms-2">Cancel</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Contacts Tab -->
            <div class="tab-pane fade" id="contacts" role="tabpanel">
                <div class="card mt-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5>Customer Messages</h5>
                        <div>
                            <span class="badge bg-light text-dark">
                                Total: <?= count($contacts) ?>
                                <?php if ($statusFilter === 'unread'): ?>
                                    | Unread: <?= $pdo->query("SELECT COUNT(*) FROM contact_submissions WHERE status IS NULL OR status != 'responded'")->fetchColumn() ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="btn-group">
                                <a href="?status=all" class="btn btn-outline-primary <?= $statusFilter === 'all' ? 'active' : '' ?>">All Messages</a>
                                <a href="?status=unread" class="btn btn-outline-primary <?= $statusFilter === 'unread' ? 'active' : '' ?>">
                                    Unread
                                    <?php
                                    $unreadCount = $pdo->query("SELECT COUNT(*) FROM contact_submissions WHERE status IS NULL OR status != 'responded'")->fetchColumn();
                                    if ($unreadCount > 0): ?>
                                        <span class="badge bg-danger ms-1"><?= $unreadCount ?></span>
                                    <?php endif; ?>
                                </a>
                                <a href="?status=responded" class="btn btn-outline-primary <?= $statusFilter === 'responded' ? 'active' : '' ?>">Responded</a>
                            </div>
                        </div>

                        <?php if (empty($contacts)): ?>
                            <div class="alert alert-info">No messages found.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Contact</th>
                                            <th>Subject</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($contacts as $contact): ?>
                                            <tr class="<?= ($contact['status'] !== 'responded') ? 'table-warning' : '' ?>">
                                                <td><?= $contact['id'] ?></td>
                                                <td><?= htmlspecialchars($contact['name']) ?></td>
                                                <td>
                                                    <div><a href="mailto:<?= htmlspecialchars($contact['email']) ?>"><?= htmlspecialchars($contact['email']) ?></a></div>
                                                    <?php if (!empty($contact['phone'])): ?>
                                                        <div><a href="tel:<?= htmlspecialchars($contact['phone']) ?>"><?= htmlspecialchars($contact['phone']) ?></a></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars(ucfirst($contact['subject'])) ?></td>
                                                <td>
                                                    <?= date('M j, Y', strtotime($contact['submitted_at'])) ?><br>
                                                    <small class="text-muted"><?= date('h:i A', strtotime($contact['submitted_at'])) ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($contact['status'] === 'responded'): ?>
                                                        <span class="badge bg-success">Responded</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <button class="btn btn-sm btn-primary view-message-btn"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#messageDetailModal"
                                                            data-id="<?= $contact['id'] ?>"
                                                            data-name="<?= htmlspecialchars($contact['name']) ?>"
                                                            data-email="<?= htmlspecialchars($contact['email']) ?>"
                                                            data-phone="<?= htmlspecialchars($contact['phone']) ?>"
                                                            data-subject="<?= htmlspecialchars($contact['subject']) ?>"
                                                            data-message="<?= htmlspecialchars($contact['message']) ?>"
                                                            data-status="<?= $contact['status'] ?>"
                                                            data-date="<?= date('F j, Y \a\t h:i A', strtotime($contact['submitted_at'])) ?>">
                                                            <i class="fas fa-eye"></i> View
                                                        </button>

                                                        <?php if ($contact['status'] !== 'responded'): ?>
                                                            <form method="post" action="update_contact_status.php" class="d-inline">
                                                                <input type="hidden" name="id" value="<?= $contact['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-success">
                                                                    <i class="fas fa-check"></i> Mark as Responded
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message Detail Modal -->
        <div class="modal fade" id="messageDetailModal" tabindex="-1" aria-labelledby="messageDetailModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="messageDetailModalLabel">Message Details</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6>Customer Information</h6>
                                <p><strong>Name:</strong> <span id="detailName"></span></p>
                                <p><strong>Email:</strong> <span id="detailEmail"></span></p>
                                <p><strong>Phone:</strong> <span id="detailPhone"></span></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Message Details</h6>
                                <p><strong>Subject:</strong> <span id="detailSubject"></span></p>
                                <p><strong>Date:</strong> <span id="detailDate"></span></p>
                                <p><strong>Status:</strong> <span id="detailStatus" class="badge"></span></p>
                            </div>
                        </div>

                        <div class="mb-3">
                            <h6>Message Content</h6>
                            <div class="card">
                                <div class="card-body" id="detailMessage"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <h6>Admin Response</h6>
                            <form id="responseForm" method="post" action="update_contact_status.php">
                                <input type="hidden" name="id" id="responseId">
                                <div class="mb-3">
                                    <textarea class="form-control" name="admin_notes" id="adminNotes" rows="3" placeholder="Add response notes..."></textarea>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <a href="#" id="emailLink" class="btn btn-primary me-2">
                                            <i class="fas fa-envelope"></i> Reply via Email
                                        </a>
                                        <a href="#" id="callLink" class="btn btn-success">
                                            <i class="fas fa-phone"></i> Call Customer
                                        </a>
                                    </div>
                                    <button type="submit" name="mark_responded" class="btn btn-success">
                                        <i class="fas fa-check"></i> Mark as Responded
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Inventory Modal -->
        <div class="modal fade" id="updateInventoryModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Inventory</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="update_inventory.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" id="inventoryFishId" name="fish_type_id">
                            <div class="mb-3">
                                <label for="inventoryFishName" class="form-label">Fish Type</label>
                                <input type="text" class="form-control" id="inventoryFishName" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="inventoryQuantity" class="form-label">Quantity (kg)</label>
                                <input type="number" step="0.1" min="0" class="form-control" id="inventoryQuantity" name="quantity" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Fish Type Modal -->
        <div class="modal fade" id="editFishModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Fish Type</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="update_fish_type.php" method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" id="editFishId" name="id">
                            <input type="hidden" id="editCurrentImage" name="current_image">
                            <div class="mb-3">
                                <label for="editFishName" class="form-label">Name</label>
                                <input type="text" class="form-control" id="editFishName" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="editFishDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="editFishDescription" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="editFishPrice" class="form-label">Price per kg</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="editFishPrice" name="price" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Current Image</label>
                                <div id="editFishImagePreview" class="mb-2"></div>
                                <label for="editFishImage" class="form-label">Change Image</label>
                                <input type="file" class="form-control" id="editFishImage" name="image_path" accept="image/*">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add Fish Type Modal -->
        <div class="modal fade" id="addFishModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Fish Type</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="add_fish_type.php" method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="addFishName" class="form-label">Name</label>
                                <input type="text" class="form-control" id="addFishName" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="addFishDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="addFishDescription" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="addFishPrice" class="form-label">Price per kg</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="addFishPrice" name="price" required>
                            </div>
                            <div class="mb-3">
                                <label for="addFishInitialQuantity" class="form-label">Initial Quantity (kg)</label>
                                <input type="number" step="0.1" min="0" class="form-control" id="addFishInitialQuantity" name="initial_quantity" required>
                            </div>
                            <div class="mb-3">
                                <label for="addFishimage" class="form-label">image</label>
                                <input type="file" class="form-control" id="addFishimage" name="image_paths" accept="image/*" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Fish Type</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Order Details Modal -->
        <div class="modal fade" id="orderDetailsModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Order Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="orderDetailsContent">
                        Loading...
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Initialize all modals
            document.getElementById('updateInventoryModal').addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var fishId = button.getAttribute('data-id');
                var fishName = button.getAttribute('data-name');
                var quantity = button.getAttribute('data-quantity');

                var modal = this;
                modal.querySelector('#inventoryFishId').value = fishId;
                modal.querySelector('#inventoryFishName').value = fishName;
                modal.querySelector('#inventoryQuantity').value = quantity;
            });

            document.getElementById('editFishModal').addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var fishId = button.getAttribute('data-id');
                var fishName = button.getAttribute('data-name');
                var description = button.getAttribute('data-description');
                var price = button.getAttribute('data-price');
                var imagePath = button.getAttribute('data-image');

                var modal = this;
                modal.querySelector('#editFishId').value = fishId;
                modal.querySelector('#editFishName').value = fishName;
                modal.querySelector('#editFishDescription').value = description;
                modal.querySelector('#editFishPrice').value = price;
                modal.querySelector('#editCurrentImage').value = imagePath;

                var imagePreview = modal.querySelector('#editFishImagePreview');
                if (imagePath) {
                    imagePreview.innerHTML = `<img src="${imagePath}" class="img-fluid" style="max-height: 150px;">`;
                } else {
                    imagePreview.innerHTML = '<p>No image available</p>';
                }
            });

            document.getElementById('orderDetailsModal').addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var orderId = button.getAttribute('data-id');

                var modal = this;
                var content = modal.querySelector('#orderDetailsContent');
                content.innerHTML = 'Loading...';

                // Fetch order details via AJAX
                fetch('get_order_details.php?order_id=' + orderId)
                    .then(response => response.text())
                    .then(data => {
                        content.innerHTML = data;
                    })
                    .catch(error => {
                        content.innerHTML = 'Error loading order details.';
                    });
            });

            // Tab functionality
            const triggerTabList = [].slice.call(document.querySelectorAll('#adminTabs button'));
            triggerTabList.forEach(function(triggerEl) {
                const tabTrigger = new bootstrap.Tab(triggerEl);
                triggerEl.addEventListener('click', function(event) {
                    event.preventDefault();
                    tabTrigger.show();
                });
            });

            // Message Detail Modal
            document.getElementById('messageDetailModal').addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const modal = this;

                // Set message details
                modal.querySelector('#detailName').textContent = button.dataset.name;
                modal.querySelector('#detailEmail').textContent = button.dataset.email;
                modal.querySelector('#detailPhone').textContent = button.dataset.phone || 'Not provided';
                modal.querySelector('#detailSubject').textContent = button.dataset.subject;
                modal.querySelector('#detailMessage').textContent = button.dataset.message;
                modal.querySelector('#detailDate').textContent = button.dataset.date;

                // Set status badge
                const statusBadge = modal.querySelector('#detailStatus');
                statusBadge.textContent = button.dataset.status === 'responded' ? 'Responded' : 'Pending';
                statusBadge.className = 'badge ' + (button.dataset.status === 'responded' ? 'bg-success' : 'bg-warning');

                // Set form values
                modal.querySelector('#responseId').value = button.dataset.id;

                // Set up action links
                modal.querySelector('#emailLink').href = `mailto:${button.dataset.email}?subject=Re: ${encodeURIComponent(button.dataset.subject)}`;
                if (button.dataset.phone) {
                    modal.querySelector('#callLink').href = `tel:${button.dataset.phone}`;
                } else {
                    modal.querySelector('#callLink').classList.add('disabled');
                }

                // Hide mark as responded button if already responded
                if (button.dataset.status === 'responded') {
                    modal.querySelector('button[name="mark_responded"]').style.display = 'none';
                }
            });

            // Refresh unread count badge periodically
            function updateUnreadCount() {
                fetch('get_unread_count.php')
                    .then(response => response.json())
                    .then(data => {
                        const badge = document.querySelector('.nav-link[href="#contacts"] .badge');
                        if (data.count > 0) {
                            if (!badge) {
                                const link = document.querySelector('.nav-link[href="#contacts"]');
                                link.innerHTML += ` <span class="badge bg-danger">${data.count}</span>`;
                            } else {
                                badge.textContent = data.count;
                            }
                        } else if (badge) {
                            badge.remove();
                        }
                    });
            }

            setInterval(updateUnreadCount, 30000); // Update every 30 seconds
            updateUnreadCount(); // Initial update
        </script>
    </div>
</body>

</html>