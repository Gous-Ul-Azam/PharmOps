<?php include('db_connect.php'); ?>

<div class="container-fluid">

    <div class="col-md-12">

        <div class="card">
            <div class="card-header">
                Sales Report
            </div>
            <div class="card-body">
                <form action="index.php" method="get" id="manage-customer">
                    <input type="hidden" name="page" value="sales_report">
                    <div class="row">
                        <div class="form-group col-9">
                            <label class="control-label">Date</label>
                            <input type="date" class="form-control" name="date" value="<?php echo isset($_GET['date']) ? $_GET['date'] : ''; ?>">
                        </div>
                        <div class=" col-3">
                            <br>
                            <button class="float-left btn btn-sm btn-primary mt-2"> Submit</button>
                        </div>
                    </div>

                </form>
                <?php if(isset($_GET['date'])){ ?>
                    <style>
    .letterhead {
      border: 2px solid #28a745;
      padding: 20px;
      margin: 20px auto;
    }
    .logo {
      font-size: 24px;
      font-weight: bold;
      color: #28a745;
    }
    .subtext {
      font-size: 14px;
      color: #555;
    }
    .divider {
      border-top: 2px solid #28a745;
      margin: 10px 0;
    }
    .contact {
      font-size: 14px;
    }
  </style>
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
                    <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <th class="text-center">#</th>
                            <th class="text-center">Date</th>
                            <th class="text-center">Invoice No</th>
                            <th class="text-center">Payment Type</th>
                            <th class="text-center">Product</th>
                            <th class="text-center">Quantity</th>
                            <th class="text-center">Price</th>
                            <th class="text-center">Discount(%)</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Action</th>
                        </thead>
                        <tbody>
                            <?php
                            $client_id = $_SESSION['login_client_id'];
                            $date = $_GET['date'];
                            $cus_arr[0] = "GUEST";
                            $i = 1;
                            $sales = $conn->query("SELECT * FROM sales_list where client_id='$client_id' AND date_updated LIKE '%$date%'   order by date(date_updated) desc");
                            $grand_total = 0;
                            while ($row = $sales->fetch_assoc()):
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
                                            AND i.form_id = " . $row['id']
                                );
                                $trcheck=0;
                                $rowSpan = $sales_tranaction->num_rows;
                                ?>
                                <tr>
                                        <td class="text-center" rowspan="<?php echo $rowSpan ?>"><?php echo $i++ ?></td>
                                        <td class="text-center" rowspan="<?php echo $rowSpan ?>"><?php echo date("d-m-Y", strtotime($row['date_updated'])) ?></td>
                                        <td class="text-center" rowspan="<?php echo $rowSpan ?>"><?php echo $row['ref_no'] ?></td>
                                        <td class="text-center" rowspan="<?php echo $rowSpan ?>"><?php echo $row['payment_type'] ?></td>
                                <?php while ($row2 = $sales_tranaction->fetch_assoc()):
                                    $total = $row2['quantity'] * $row2['price'];
                                    $total = $total - ($total * ($row2['discount'])/100);
                                    $grand_total += $total;
                            ?>
                                    <?php if($trcheck > 0){ echo "<tr>"; }  ?>
                                        <td class="text-center"><?php echo $row2['product_name'] ?></td>
                                        <td class="text-center"><?php echo $row2['quantity'] ?></td>
                                        <td class="text-center"><?php echo $row2['price'] ?></td>
                                        <td class="text-center"><?php echo $row2['discount'] > 0 ? $row2['discount']."%":"-"; ?></td>
                                        <td class="text-right"><?php echo number_format($total) ?></td>
                                        <?php if($trcheck == 0){ ?>
                                            <td class="text-center" rowspan="<?php echo $rowSpan ?>">
                                        <a href="index.php?page=invoice&id=<?php echo $row['id'] ?>" target="_blank">
                                        📄
                                        </a>
                                        </td> 
                                            <?php } $trcheck++; ?>
                                        </tr>
                            <?php endwhile; ?>
                            <?php endwhile; if($grand_total==0){ ?>
                            <tr>
                                <th colspan="9" class="text-center">No Data Available</th>
                            </tr>
                            <?php } ?>
                        </tbody>
                        <?php if($grand_total>0){ ?>
                        <tfoot>
                            <tr>
                                <th colspan="8" class="text-right">Grand Total</th>
                                <th class="text-right"><?php echo number_format($grand_total) ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                        <?php } ?>
                    </table>
                    </div>
                </div>
                <?php } ?> 
            </div>
        </div>

    </div>

</div>
<style>
    td {
        vertical-align: middle !important;
    }

    td p {
        margin: unset;
    }
</style>