<?php
include 'models/authentication.php';
require_once "helpers/permissions.php";
require_once "config/database.php";
require_once "config/connect.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

$message = "";
$message_type = "";

include "views/header.php";
?>

<div class="container-fluid">
    <div class="row">
        <?php include "views/sidebar.php"; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- You can add any new content here if needed -->
            <div class="mb-5 pb-4"></div>
        </main>
    </div>
</div>

<footer class="footer mt-auto py-3 bg-primary">
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
    document.addEventListener('DOMContentLoaded', function () {
        feather.replace();
    });
</script>
<!-- Custom scripts -->
<script src="assets/js/scripts.js"></script>
</body>
</html>
