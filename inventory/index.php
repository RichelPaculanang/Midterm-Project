<?php
include 'models/authentication.php';
require_once "helpers/permissions.php";
require_once "config/database.php";
require_once "config/connect.php";

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Function to get total products count
function getTotalProducts($db) {
    $query = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['total'] ?? 0;
}

// Function to get total sales amount
function getTotalSales($db) {
    $query = "SELECT SUM(total_amount) as total FROM sales";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['total'] ?? 0;
}

// Function to get low stock items count
function getLowStockCount($db) {
    $query = "SELECT COUNT(*) as total FROM products p 
              INNER JOIN stock s ON p.product_id = s.product_id 
              WHERE s.current_quantity <= p.minimum_stock_level 
              AND s.current_quantity > 0 
              AND p.is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['total'] ?? 0;
}

// Function to get recent sales
function getRecentSales($db, $limit = 5) {
    $query = "SELECT s.sale_id, s.invoice_number, s.sale_date, s.total_amount, c.name as customer_name, u.username 
              FROM sales s 
              LEFT JOIN customers c ON s.customer_id = c.customer_id
              INNER JOIN users u ON s.user_id = u.user_id
              ORDER BY s.sale_date DESC 
              LIMIT :limit";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get low stock items
function getLowStockItems($db, $limit = 5) {
    $query = "SELECT p.product_id, p.name, p.sku, s.current_quantity, p.minimum_stock_level 
              FROM products p 
              INNER JOIN stock s ON p.product_id = s.product_id 
              WHERE s.current_quantity <= p.minimum_stock_level 
              AND p.is_active = 1 
              ORDER BY (p.minimum_stock_level - s.current_quantity) DESC 
              LIMIT :limit";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get data for dashboard
$totalProducts = getTotalProducts($db);
$totalSales = getTotalSales($db);
$lowStockCount = getLowStockCount($db);
$recentSales = getRecentSales($db);
$lowStockItems = getLowStockItems($db);

include "views/header.php";
?>
<link href="profile.css" rel="stylesheet">
<link rel="stylesheet" href="sidebarhover.css">
<div class="container-fluid">
    <div class="row">
        <?php include "views/sidebar.php"; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="py-4">
        <h2 class="fw-bold mb-4">Dashboard</h2>
        
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm rounded-4 p-3 border-2 bg-pink text-black">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 text-uppercase small">Total Products</p>
                            <h4 class="fw-semibold"><?php echo $totalProducts; ?></h4>
                        </div>
                        <i data-feather="package" class="feather-32"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm rounded-4 p-3 border-0 bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 text-uppercase small">Total Sales</p>
                            <h4 class="fw-semibold">$<?php echo number_format($totalSales, 2); ?></h4>
                        </div>
                        <i data-feather="dollar-sign" class="feather-32"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm rounded-4 p-3 border-0 bg-warning text-dark">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 text-uppercase small">Low Stock Items</p>
                            <h4 class="fw-semibold"><?php echo $lowStockCount; ?></h4>
                        </div>
                        <i data-feather="alert-triangle" class="feather-32"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card shadow-sm rounded-4">
                    <div class="card-header bg-light border-0 rounded-top-4 d-flex justify-content-between">
                        <h6 class="mb-0 fw-bold">Recent Sales</h6>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light border-0" data-bs-toggle="dropdown">
                                <i data-feather="more-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="sales.php">View All</a></li>
                                <li><a class="dropdown-item" href="reports.php?type=sales">Export</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <?php if (!empty($recentSales)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Invoice #</th>
                                            <th>Date</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentSales as $sale): ?>
                                            <tr>
                                                <td><a href="sale_details.php?id=<?php echo $sale['sale_id']; ?>" class="text-decoration-none"><?php echo $sale['invoice_number']; ?></a></td>
                                                <td><?php echo date('M d, Y', strtotime($sale['sale_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer'); ?></td>
                                                <td>$<?php echo number_format($sale['total_amount'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No recent sales to display.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm rounded-4">
                    <div class="card-header bg-light border-0 rounded-top-4 d-flex justify-content-between">
                        <h6 class="mb-0 fw-bold">Low Stock Alerts</h6>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light border-0" data-bs-toggle="dropdown">
                                <i data-feather="more-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="inventory.php?stock_status=low">View All</a></li>
                                <li><a class="dropdown-item" href="reports.php?type=low_stock">Export</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <?php if (!empty($lowStockItems)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th>Stock</th>
                                            <th>Min</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($lowStockItems as $item): ?>
                                            <tr>
                                                <td><a href="product_details.php?id=<?php echo $item['product_id']; ?>" class="text-decoration-none"><?php echo htmlspecialchars($item['name']); ?></a></td>
                                                <td><?php echo htmlspecialchars($item['sku']); ?></td>
                                                <td class="<?php echo ($item['current_quantity'] == 0) ? 'text-danger' : 'text-warning'; ?> fw-bold">
                                                    <?php echo $item['current_quantity']; ?>
                                                </td>
                                                <td><?php echo $item['minimum_stock_level']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No low stock alerts to display.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

    </div>
</div>

<!-- Modified footer markup to align with the main content area -->
<footer class="footer mt-auto py-3 bg-pink">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2">
                <!-- Empty space for sidebar alignment -->
            </div>
            <div class="col-md-9 col-lg-10">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-light">Inventory Management System &copy; <?php echo date('Y'); ?></span>
                    <span class="text-light">Version 1.0</span>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!-- Feather Icons -->
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script>
    // Initialize Feather icons
    document.addEventListener('DOMContentLoaded', function() {
        feather.replace();
    });
</script>
<!-- Custom scripts -->
<script src="assets/js/scripts.js"></script>
</body>
</html>
