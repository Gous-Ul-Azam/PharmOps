<?php include 'db_connect.php';

if (isset($_GET['id'])) {
    $invoicDetails = $conn->query("SELECT * FROM sales_list where id=" . $_GET['id'])->fetch_array();

    $sales_tranaction = $conn->query(
        "
            SELECT 
                i.*, 
                p.name AS product_name,
                JSON_UNQUOTE(JSON_EXTRACT(i.other_details, '$.qty')) AS quantity,
                JSON_UNQUOTE(JSON_EXTRACT(i.other_details, '$.price')) AS price,
                JSON_UNQUOTE(JSON_EXTRACT(i.other_details, '$.discount')) AS discount
            FROM 
                inventory i
            JOIN 
                product_list p ON i.product_id = p.id
            WHERE 
                i.type = 2 
                AND i.form_id = " . $_GET['id']
    );
}

?>


</head>

<body>

    <!-- Buttons -->
    <div class="no-print">
        <button class="btn btn-primary" onclick="printInvoice()">🖨️ Print</button>
        <button class="btn btn-danger" onclick="exportPDF()">📄 Export PDF</button>
    </div>

    <!-- Invoice with Letterhead -->
    <div id="invoice" class="invoice-box">
        <style>
            body {
                background: #f8f9fa;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                font-size: 15px;
            }

            .invoice-box {
                background: #fff;
                padding: 40px;
                max-width: 800px;
                margin: 40px auto;
                border: 1px solid #ccc;
                color: #000;
            }

            .letterhead {
                border: 2px solid #28a745;
                padding: 20px;
                margin-bottom: 30px;
                background-color: #fff;
            }

            .header-title {
                font-size: 22px;
                font-weight: bold;
                color: #28a745;
            }

            .subtext {
                font-size: 14px;
                color: #333;
            }

            .divider {
                border-top: 2px solid #28a745;
                margin: 10px 0 20px;
            }

            .invoice-header {
                border-bottom: 2px solid #28a745;
                margin: 10px 0 20px;
                padding-bottom: 10px;
            }

            .invoice-title h2 {
                font-size: 24px;
                margin-bottom: 10px;
                color: #333;
            }

            .invoice-meta {
                font-size: 14px;
            }

            .table th {
                background-color: #f8f8f8;
                color: #333;
                font-weight: 600;
                border: 1px solid #ddd !important;
                padding: 10px;
            }

            .table td {
                border: 1px solid #ddd !important;
                padding: 10px;
                vertical-align: middle;
                font-size: 15px;
            }

            .table tfoot td,
            .table tfoot th {
                background-color: #fafafa;
                font-weight: bold;
                border-top: 2px solid #ccc !important;
            }

            .text-right {
                text-align: right;
            }

            .footer-note {
                font-size: 13px;
                text-align: center;
                margin-top: 40px;
                color: #666;
            }

            .no-print {
                margin: 30px auto;
                text-align: center;
            }

            @media print {
                body {
                    background: #fff !important;
                }

                .no-print {
                    display: none !important;
                }

                .invoice-box {
                    margin: 0;
                    padding: 20mm;
                    border: none;
                    box-shadow: none;
                    width: 100%;
                    page-break-after: auto;
                }

                .table {
                    page-break-inside: auto;
                }

                .table tr {
                    page-break-inside: avoid;
                    page-break-after: auto;
                }

                /* .footer-note {
            position: fixed;
            bottom: 20px;
            width: 100%;
        } */
            }
        </style>
        <!-- Letterhead -->
        <div class="container letterhead">
            <div class="row">
                <div class="col-2 text-center">
                    <div class="header-title"><span style="font-size: 32px;">➕</span></div>
                </div>
                <div class="col-10">
                    <h4 class="mb-1"><?php echo $_SESSION['setting_name']; ?></h4>
                    <p class="subtext mb-0"><?php echo $_SESSION['setting_about_content']; ?></p>
                </div>
            </div>
            <div class="divider"></div>


            <!-- Invoice Meta -->
            <div class="row invoice-header">
                <div class="col-sm-8 invoice-title">
                    <h2>Invoice</h2>
                </div>
                <div class="col-sm-4 text-right invoice-meta">
                    Ref No: <?php echo $invoicDetails['ref_no'] ?><br>
                    Date: <?php echo date("d-m-Y", strtotime($invoicDetails['date_updated'])) ?>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">Sr</th>
                            <th style="width: 45%;">Product</th>
                            <th style="width: 25%;">Payment Type</th>
                            <th class="text-right" style="width: 5%;">Qty</th>
                            <th class="text-right" style="width: 5%;">Price</th>
                            <th class="text-right" style="width: 5%;">Discount(%)</th>
                            <th class="text-right" style="width: 20%;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1;
                        $grand_total = 0;
                        while ($row2 = $sales_tranaction->fetch_assoc()):
                            $total = $row2['quantity'] * $row2['price'];
                            $total = $total - ($total * ($row2['discount'])/100);
                            $grand_total += $total;
                        ?>
                            <tr>
                                <td><?php echo $i;
                                    $i++; ?></td>
                                <td><?php echo $row2['product_name'] ?></td>
                                <td><?php echo $invoicDetails['payment_type'] ?></td>
                                <td class="text-center"><?php echo $row2['quantity'] ?></td>
                                <td class="text-center"><?php echo $row2['price'] ?></td>
                                <td class="text-center"><?php echo $row2['discount'] > 0 ? $row2['discount']."%":"-"; ?></td>
                                <td class="text-right"><?php echo number_format($total) ?></td>

                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="6" class="text-right">Grand Total</th>
                            <td class="text-right">₹ <?php echo number_format($grand_total) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Footer -->
            <div class="footer-note">
                This is a system-generated invoice.
            </div>
        </div>
    </div>
    <!-- Scripts for PDF Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        window.exportPDF = async function() {
            const {
                jsPDF
            } = window.jspdf;
            const invoice = document.getElementById("invoice");

            const canvas = await html2canvas(invoice, {
                scale: 2
            });
            const imgData = canvas.toDataURL("image/png");

            const pdf = new jsPDF("p", "mm", "a4");
            const imgProps = pdf.getImageProperties(imgData);
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

            pdf.addImage(imgData, "PNG", 0, 0, pdfWidth, pdfHeight);
            pdf.save("<?php echo $invoicDetails['ref_no'] ?>.pdf");
        };

        function printInvoice() {
            const invoiceContent = document.getElementById('invoice').innerHTML;
            const originalContent = document.body.innerHTML;

            document.body.innerHTML = invoiceContent;
            window.print();
            document.body.innerHTML = originalContent;
            window.location.reload(); // Optional: refresh to reset scripts
        }
    </script>

</body>

</html>