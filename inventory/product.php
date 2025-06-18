<?php
include 'models/authentication.php';
require_once "config/database.php";
require_once "models/product.php";
require_once "helpers/permissions.php";
include "views/header.php";

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$page = isset($_GET['page']) ? $_GET['page'] : 1;
$records_per_page = 10;
$from_record_num = ($records_per_page * $page) - $records_per_page;

$stmt = $product->readAll($from_record_num, $records_per_page);
$num = $stmt->rowCount();

$total_rows = $product->countAll();
$total_pages = ceil($total_rows / $records_per_page);

$isAdmin = checkPermission('manage_products');

$cartItemCount = 0;
foreach ($_SESSION['cart'] as $item) {
    $cartItemCount += $item['quantity'];
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    body {
        background-color: #f8f9fa;
    }
    .card-custom {
        background: #ffffff;
        border: 1px solid #dee2e6;
        border-radius: 10px;
    }
    .header-title {
        color: #5a189a;
    }
    .btn-primary, .btn-outline-primary {
        background-color:rgb(154, 24, 96);
        border-color:rgb(253, 236, 249);
    }
    .btn-primary:hover, .btn-outline-primary:hover {
        background-color:rgb(191, 44, 149);
        border-color:rgb(191, 44, 154);
    }
    .pagination .page-link {
        color:rgb(253, 252, 254);
    }
    .pagination .page-item.active .page-link {
        background-color:rgb(240, 238, 243);
        border-color:rgb(244, 242, 246);
    }
</style>

<div class="container-fluid">
    <div class="row">
        <?php include "views/sidebar.php"; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="header-title">Product Management</h2>
                <div>
                    <a href="cart.php" class="btn btn-outline-success position-relative me-2">
                        <i data-feather="shopping-cart"></i> Cart
                        <?php if ($cartItemCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $cartItemCount; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <?php if($isAdmin): ?>
                    <a href="product_create.php" class="btn btn-primary">
                        <i data-feather="plus"></i> Add Product
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-custom p-4 mb-4">
                <form action="product.php" method="GET" class="row g-3">
                    <div class="col-md-8">
                        <input type="text" name="search" class="form-control" placeholder="Search products..." 
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                    <div class="col-md-4">
                        <select name="category" class="form-select" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php
                            $categories = $product->getCategories();
                            foreach($categories as $category) {
                                $selected = (isset($_GET['category']) && $_GET['category'] == $category['category']) ? 'selected' : '';
                                echo "<option value='{$category['category']}' {$selected}>{$category['category']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </form>
            </div>

            <div class="card-custom p-4 mb-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#ID</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>SKU</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        if($num > 0) {
                            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                extract($row);
                                $stock_class = ($stock_level <= $minimum_stock_level) ? 'text-danger fw-bold' : '';
                                echo "<tr>
                                    <td>{$product_id}</td>
                                    <td>{$name}</td>
                                    <td>{$category}</td>
                                    <td>\${$price}</td>
                                    <td>{$sku}</td>
                                    <td class='{$stock_class}'>{$stock_level}</td>
                                    <td>
                                        <div class='btn-group btn-group-sm'>
                                            <a href='product_view.php?id={$product_id}' class='btn btn-outline-primary' title='View'><i data-feather='eye'></i></a>";
                                if($isAdmin) {
                                    echo "<a href='product_edit.php?id={$product_id}' class='btn btn-outline-secondary' title='Edit'><i data-feather='edit'></i></a>";
                                }
                                if($stock_level > 0) {
                                    echo "<a href='add_to_cart.php?id={$product_id}' class='btn btn-outline-success' title='Add to Cart'><i data-feather='shopping-cart'></i></a>";
                                }
                                echo "</div></td></tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center text-muted'>No products found</td></tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php 
                    if($page > 1) {
                        echo "<li class='page-item'><a class='page-link' href='product.php?page=".($page-1)."'>Previous</a></li>";
                    } else {
                        echo "<li class='page-item disabled'><span class='page-link'>Previous</span></li>";
                    }

                    for($i=1; $i<=$total_pages; $i++) {
                        if($i == $page) {
                            echo "<li class='page-item active'><span class='page-link'>{$i}</span></li>";
                        } else {
                            echo "<li class='page-item'><a class='page-link' href='product.php?page={$i}'>{$i}</a></li>";
                        }
                    }

                    if($page < $total_pages) {
                        echo "<li class='page-item'><a class='page-link' href='product.php?page=".($page+1)."'>Next</a></li>";
                    } else {
                        echo "<li class='page-item disabled'><span class='page-link'>Next</span></li>";
                    }
                    ?>
                </ul>
            </nav>
            <?php endif; ?>

        </main>
    </div>
</div>

<footer class="footer mt-auto py-3 bg-dark text-light">
    <div class="container-fluid">
        <div class="d-flex justify-content-between">
            <span>Inventory Management System &copy; <?php echo date('Y'); ?></span>
            <span>Version 1.0</span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        feather.replace();
    });
</script>
