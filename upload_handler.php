<?php
session_start();
date_default_timezone_set('Asia/Kolkata');

require 'db_connect.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$conn->query("SET time_zone = '+05:30'");

function generate_ref_no($conn) {
    $today = date('Y-m-d');
    $ref_prefix = "RCV/" . date('Y/m/d');
    $res = $conn->query("SELECT ref_no FROM receiving_list WHERE DATE(date_added) = '$today' ORDER BY id DESC LIMIT 1");

    $last_num = 0;
    if ($res->num_rows > 0) {
        preg_match('/(\d{4})$/', $res->fetch_assoc()['ref_no'], $matches);
        $last_num = isset($matches[1]) ? (int)$matches[1] : 0;
    }

    return $ref_prefix . '/' . str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);
}

function parse_to_ymd($value) {
    $value = trim($value);
    $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'Y/m/d', 'm/d/Y', 'm-d-Y'];
    foreach ($formats as $format) {
        $dt = DateTime::createFromFormat($format, $value);
        if ($dt && $dt->format($format) === $value) return $dt->format('Y-m-d');
    }
    $timestamp = strtotime($value);
    return $timestamp ? date('Y-m-d', $timestamp) : false;
}

$uploaded_products = [];

if (isset($_POST['submit']) && isset($_FILES['excel']['tmp_name'])) {
    $file = $_FILES['excel']['tmp_name'];

    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        foreach ($sheet as $i => $row) {
            if ($i === 1) continue;

            $missing = [];

            if (empty(trim($row['D']))) $missing[] = 'Product Name';
            if (empty(trim($row['E']))) $missing[] = 'Measurement';
            if (empty(trim($row['F']))) $missing[] = 'Description';
            if (empty(trim($row['G']))) $missing[] = 'Sale Price';
            if (empty(trim($row['H']))) $missing[] = 'Supplier';
            if (empty(trim($row['I']))) $missing[] = 'Expiry Date';
            if (empty(trim($row['J']))) $missing[] = 'Purchase Qty';
            if (empty(trim($row['K']))) $missing[] = 'Purchase Price';

            $expiry = parse_to_ymd($row['I'] ?? '');
            if (!empty(trim($row['I'])) && !$expiry) {
                $_SESSION['upload_message'] = "<div class='alert alert-danger'>Row $i has invalid expiry date format: " . htmlspecialchars($row['I']) . "</div>";
                header("Location: index.php?page=import_excel");
                exit;
            }

            if (!empty($missing)) {
                $_SESSION['upload_message'] = "<div class='alert alert-danger'>Row $i is missing: " . implode(', ', $missing) . "</div>";
                header("Location: index.php?page=import_excel");
                exit;
            }
        }

        $grouped = [];
        foreach ($sheet as $i => $row) {
            if ($i === 1) continue;
            $supplier = trim($row['H']);
            $grouped[$supplier][] = $row;
        }

        foreach ($grouped as $supplier => $rows) {
            // Supplier
            $supplier_res = $conn->query("SELECT id FROM supplier_list WHERE supplier_name = '$supplier'");
            if ($supplier_res->num_rows == 0) {
                $conn->query("INSERT INTO supplier_list(supplier_name) VALUES('$supplier')");
                $supplier_id = $conn->insert_id;
            } else {
                $supplier_id = $supplier_res->fetch_assoc()['id'];
            }

            $ref_no = generate_ref_no($conn);
            $total_amount = 0;
            $inventory_ids = [];

            foreach ($rows as $row) {
                $sku            = trim($row['A']);
                $category       = trim($row['B']);
                $type           = trim($row['C']);
                $name           = trim($row['D']);
                $measurement    = trim($row['E']);
                $description    = trim($row['F']);
                $sale_price     = floatval($row['G']);
                $expiry         = parse_to_ymd($row['I']);
                $qty            = intval($row['J']);
                $purchase_price = floatval($row['K']);

                $total_amount += $qty * $purchase_price;

                // Category
                $cat_res = $conn->query("SELECT id FROM category_list WHERE name = '$category'");
                if ($cat_res->num_rows > 0) {
                    $category_id = $cat_res->fetch_assoc()['id'];
                } else {
                    $conn->query("INSERT INTO category_list(name) VALUES('$category')");
                    $category_id = $conn->insert_id;
                }

                // Type
                $type_res = $conn->query("SELECT id FROM type_list WHERE name = '$type'");
                if ($type_res->num_rows > 0) {
                    $type_id = $type_res->fetch_assoc()['id'];
                } else {
                    $conn->query("INSERT INTO type_list(name) VALUES('$type')");
                    $type_id = $conn->insert_id;
                }

                // Product
                $prod_res = $conn->query("SELECT id FROM product_list WHERE name = '$name' AND category_id = '$category_id' AND type_id = '$type_id' AND measurement = '$measurement'");
                if ($prod_res->num_rows > 0) {
                    $product_id = $prod_res->fetch_assoc()['id'];
                } else {
                    $conn->query("INSERT INTO product_list(category_id, type_id, sku, price, name, measurement, description, prescription)
                        VALUES('$category_id','$type_id','$sku','$sale_price','$name','$measurement','$description',0)");
                    $product_id = $conn->insert_id;
                }

                $other_details = json_encode(['price' => $purchase_price, 'qty' => $qty]);
                $conn->query("INSERT INTO inventory(product_id, qty, type, stock_from, form_id, expiry_date, other_details, remarks)
                    VALUES('$product_id', '$qty', 1, 'receiving', 0, '$expiry', '$other_details', 'Excel import')");
                $inventory_ids[] = $conn->insert_id;

                $uploaded_products[] = [
                    'ref_no' => $ref_no,
                    'sku' => $sku,
                    'product' => $name,
                    'measurement' => $measurement,
                    'supplier' => $supplier,
                    'qty' => $qty,
                    'purchase_price' => $purchase_price,
                    'sale_price' => $sale_price,
                    'expiry' => $expiry,
                ];
            }

            // Receiving entry
            $conn->query("INSERT INTO receiving_list(ref_no, supplier_id, total_amount) VALUES('$ref_no', '$supplier_id', '$total_amount')");
            $receiving_id = $conn->insert_id;

            if (!empty($inventory_ids)) {
                $ids = implode(',', $inventory_ids);
                $conn->query("UPDATE inventory SET form_id = '$receiving_id' WHERE id IN ($ids)");
            }
        }

        $_SESSION['uploaded_products'] = $uploaded_products;
        $_SESSION['upload_message'] = "<div class='alert alert-success'>Excel uploaded successfully.</div>";
        header("Location: index.php?page=import_excel");
        exit;

    } catch (Throwable $e) {
        $_SESSION['upload_message'] = "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        header("Location: index.php?page=import_excel");
        exit;
    }
} else {
    $_SESSION['upload_message'] = "<div class='alert alert-warning'>No file selected.</div>";
    header("Location: index.php?page=import_excel");
    exit;
}
