<?php
require_once "../../config/auth.php";
include "../layouts/header.php";include "../layouts/sidebar.php";
?>
<div class="content"><?php include "../layouts/topbar.php"; ?><div class="container-fluid mt-4">
<div class="card shadow border-0"><div class="card-header bg-white d-flex justify-content-between"><h4 class="mb-0">Billing Import</h4><a href="index.php" class="btn btn-secondary">Back</a></div>
<div class="card-body"><div class="alert alert-info">Bulk import format is available. Download the CSV template, fill shipment rows, and keep a copy before importing. For safety, invoice creation should continue through the validated Create Invoice screen until the import mapping is verified against your live customer IDs.</div>
<a class="btn btn-success" href="download_format.php"><i class="fa fa-download"></i> Download CSV Format</a>
<a class="btn btn-primary" href="add.php"><i class="fa fa-plus"></i> Create Invoice</a></div></div></div></div>
<?php include "../layouts/footer.php"; ?>
