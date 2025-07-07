<?php
$uploaded_products = $_SESSION['uploaded_products'] ?? [];
$message = $_SESSION['upload_message'] ?? '';

unset($_SESSION['uploaded_products'], $_SESSION['upload_message']);
?>


    <div class="card">
        <div class="card-header">
            Upload Pharmacy Products (Excel)
            <a href="/upload_products.xlsx" class="btn btn-info btn-sm float-right" target="_blank">Download Excel</a>
        </div>
        <div class="card-body">
            <?= $message ?>
            <form method="post" enctype="multipart/form-data" action="upload_handler.php">
                <div class="form-group">
                    <label>Select Excel File (.xlsx, .xls)</label>
                    <input type="file" name="excel" class="form-control" accept=".xlsx,.xls" required>
                </div>
                <button type="submit" name="submit" class="btn btn-success">Upload & Import</button>
            </form>

            <?php if (!empty($uploaded_products)): ?>
            <div class="mt-5">
                <h5>Uploaded Products</h5>
                <table class="table table-bordered table-striped table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th>Ref No</th>
                            <th>SKU</th>
                            <th>Product</th>
                            <th>Measurement</th>
                            <th>Supplier</th>
                            <th>Qty</th>
                            <th>Purchase Price</th>
                            <th>Sale Price</th>
                            <th>Expiry</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($uploaded_products as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['ref_no']) ?></td>
                            <td><?= htmlspecialchars($p['sku']) ?></td>
                            <td><?= htmlspecialchars($p['product']) ?></td>
                            <td><?= htmlspecialchars($p['measurement']) ?></td>
                            <td><?= htmlspecialchars($p['supplier']) ?></td>
                            <td><?= htmlspecialchars($p['qty']) ?></td>
                            <td><?= number_format($p['purchase_price'], 2) ?></td>
                            <td><?= number_format($p['sale_price'], 2) ?></td>
                            <td><?= htmlspecialchars($p['expiry']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <div class="mt-4">
                <p><strong>Required Excel Columns:</strong></p>
                <code>Category, Type, Product Name, Measurement, Description, Sale Price, Supplier, Expiry Date, Purchase Qty, Purchase Price</code>
            </div>
        </div>
    </div>
