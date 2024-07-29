<script>
    window.onload = function() {
        var d = new Date().getTime();
        var g = "GC";
        var res = d + g;
        document.getElementById("tid").value = res;
    };
</script>
<?php 
error_reporting(0);
$orderid = time() . mt_rand() . "GE";
$currency = "INR";
$tid = "CP" . mt_rand() . "GE"; 
$partnerid ='';
if (isset($_GET['partnerid'])) {
    $partnerid = $_GET['partnerid'];
}
$cart = null;
if (isset($_GET['cart'])) {
    $cart = base64_decode($_GET['cart']);
    $cart = json_decode($cart);
} 
if (isset($cart) && count($cart) > 0) {
    $currency = $cart[0]->c;
}

?>
<!doctype html>
<html lang="en">

<head>
    <title>LeadCon Payment Page</title>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
    <link rel="icon" type="image/png" sizes="16x16" href="../channelpartners/imagesnew/fevicon LeadCon1.png">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="LeadCon Channel Partners Meet">
    <meta name="keywords" content="LeadCon,sales,apps">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="LeadCon is the best tool for sales conversion available in the market today.">
    <meta property="og:title" content="LeadCon Your Sales Companion" />
    <meta property="og:url" content="https://leadcon.co/channelpartners/" />
    <meta property="og:description" content="LeadCon">
    <meta property="og:image" content="https://leadcon.co/channelpartners/images/fevicon LeadCon1.png">
    <meta property="og:type" content="website" />
    <link rel="dns-prefetch" href="http://fonts.googleapis.com/">
    <link href="https://fonts.googleapis.com/css?family=Rubik:300,400,500" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css" integrity="sha384-50oBUHEmvpQ+1lW4y57PTFmhCaXp0ML5d60M1M7uH2+nqUivzIebhndOJK28anvf" crossorigin="anonymous">
    <link rel="stylesheet" href="../channelpartners/cssnew/bootstrap.min.css">
    <!-- Themify Icons -->
    <link rel="stylesheet" href="../channelpartners/cssnew/themify-icons.css">
    <!-- Owl carousel -->
    <link rel="stylesheet" href="../channelpartners/cssnew/owl.carousel.min.css">
    <!-- Main css -->
    <link href="../channelpartners/cssnew/style.css" rel="stylesheet">
</head>

<body id='payment-page-body' currency="<?php echo $currency ?>" data-spy="scroll" data-target="#navbar" data-offset="30">
    <div class="section light-bg" style="padding: 0px; padding-bottom: 25px;">
        <div class="col-md-12 text-center">
            <img src="../channelpartners/imagesnew/LeadCon Logo Website_00000.png" class="img-fluid moblogo" alt="logo">
        </div>
        <div id="paymentCheckOut" class="container">
            <div class="section-title text-center">
                <h3>CheckOut</h3>
            </div>
            <div class="card" style="box-shadow: 0px 5px 23px 0px #ddd;">
                <div class="card-body">
                    <form method="post" name="customerData" action="">
                        <div class="col-md-12" style="float: left;">
                            <?php echo $result; ?>
                        </div>
                        <div class="col-md-12" style="float: left; text-align: left">
                            <div class="col-md-6" style="float: left;">
                                <input type="hidden" name="razorpay_order_id" id="razorpay_order_id" value="" readonly style="display:none" />
                                <input type="text" name="tid" id="tid" value="<?php echo $tid; ?>" readonly style="display:none" />
                                <input type="text" id='promo_code' name="promo_code" value="" style="display:none" />
                                <input id='merchant_param3' name="merchant_param3" value="<?php echo $quantity; ?>" style="display:none" readonly>
                                <h4 class="card-title">Billing Details</h4>
                                <div class="form-group">
                                    <label>Billing Name<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="billing_name" name="billing_name" value="" required>
                                </div>
                                <div class="form-group">
                                    <label>Billing Mobile<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="billing_tel" name="billing_tel" value="" required>
                                </div>
                                <div class="form-group">
                                    <label>Billing Address<span class="text-danger">*</span></label></br>
                                    <textarea rows="4" cols="50" id="billing_address" name="billing_address" value="" required></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Billing City<span class="text-danger">*</span></label>
                                    <input type="text" id="billing_city" name="billing_city" class="form-control" value="">
                                </div>
                                <div class="form-group">
                                    <label>Billing Postalcode<span class="text-danger">*</span></label>
                                    <input type="text" id="billing_zip" name="billing_zip" class="form-control" value="">
                                </div>
                                <div class="form-group">
                                    <label>Billing State<span class="text-danger">*</span></label>
                                    <input type="text" id="billing_state" name="billing_state" class="form-control" value="">
                                </div>
                                <div class="form-group">
                                    <label>Billing Country<span class="text-danger">*</span></label>
                                    <input type="text" id="billing_country" name="billing_country" class="form-control" value="">
                                </div>
                                <div class="form-group">
                                    <label>Currency<span class="text-danger">*</span></label>
                                    <input type="text" id="currency" name="currency" class="form-control" value="<?php echo $currency ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Email Address<span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="billing_email" name="billing_email" value="" required>
                                </div>
                                <h4 class="card-title">Additional Details</h4>
                                <div class="form-group" style="display:none">
                                    <label>Partner ID<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="merchant_param1" name="merchant_param1" value="<?php echo $partnerid; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>GSTIN (optional)</label>
                                    <input type="text" class="form-control" id="merchant_param4" name="merchant_param4">
                                    <input type="hidden" id="merchant_id" name="merchant_id" value="82480" />
                                    <input type="hidden" id="order_id" name="order_id" value="<?php echo $orderid; ?>" />
                                </div>
                                <div class="form-group">
                                    <label>Additional Notes (If Any)</label></br>
                                    <textarea rows="4" cols="50" id="billing_notes" name="billing_notes"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6" style="float: left;">
                                <h4 class="card-title">Order Details

                                </h4>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <th>Product</th>
                                            <th>Total</th>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $subtotal = 0;
                                            $tax = 0;
                                            $amount = 0;
                                            for ($row = 0; $row < count($cart); $row++) {
                                                echo "<tr>";
                                                echo '<td>' . $cart[$row]->name . " X " . $cart[$row]->qty . "</td>";
                                                echo '<td>' . $currency . ' ' . ($cart[$row]->price * $cart[$row]->qty) . "</td>";
                                                echo "</tr>";
                                                $subtotal = $subtotal + ($cart[$row]->price * $cart[$row]->qty);
                                            }
                                            $tax = (18 / 100) * $subtotal;
                                            $amount = $tax + $subtotal;
                                            ?>
                                            <tr>
                                                <td>SubTotal</td>
                                                <td><?php echo $currency ?> <span id="order_detail_subtotal"><?php echo round($subtotal,2); ?></span></td>
                                            </tr>
                                            <?php echo $ifdtext;
                                            ?>

                                            <tr id="discount_applied" hidden>
                                                <td>Discount</td>
                                                <td>- <?php echo $currency ?> <span id="order_details_discount_amount"></span> </td>
                                            </tr>
                                            <tr>
                                                <td>Tax (GST +18%)</td>
                                                <td><?php echo $currency ?> <span id="order_details_tax"> <?php echo round($tax,2); ?></span></td>
                                            </tr>

                                            <tr>
                                                <td>Final Total</td>
                                                <td><?php echo $currency ?> <input type="text" id="amount" name="amount" value="<?php echo round($amount,2); ?>" style="width: 30%; border: none;" readonly></td>
                                            </tr>

                                        </tbody>
                                    </table>
                                </div>
                                <div class="row mb-2 mr-2">
                                    <div class="col-md-4">
                                        <p style="font-size:12px;cursor:pointer" id="coupon-handler" onclick="hideShowCoupon()"> Have coupon code?</p>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" value="" hidden placeholder="Enter coupon code" id="couponCode" />
                                        <span id="couponcodeInvalidMessage" style="color:red;font-size:10px"></span>
                                    </div>
                                    <div class="col-md-4">
                                        <button type='button' id="ApplyCoupon" class="btn btn-color btn-primary" hidden onclick="applyCoupon()">Apply Coupon</button>
                                    </div>

                                </div>
                                <button type='button' id="payWithRazor" value="guestccavResponseHandler.php" class="btn btn-color btn-block">Place Order</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div id="paymentAcknowlagement">
            <div class="container">
                <div class="section-title text-center">
                    <h3>
                        Payment Status: <span id="payment_order_status" style="font-weight:700;text-decoration:underline">Failed</span>
                    </h3>
                </div>
                <div class="card" style="box-shadow: 0px 5px 23px 0px #ddd;">
                    <div class="card-body">
                        <h5 id="payment_message"></h5>
                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="card-title">Order Details</h4>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <th>Product</th>
                                            <th>Total</th>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $subtotal = 0;
                                            $tax = 0;
                                            $amount = 0;
                                            for ($row = 0; $row < count($cart); $row++) {

                                                echo "<tr>";
                                                echo '<td>' . $cart[$row]->name . " X " . $cart[$row]->qty . "</td>";
                                                echo '<td> ' . $currency . ' ' . ($cart[$row]->price * $cart[$row]->qty) . "</td>";
                                                echo "</tr>";
                                                $subtotal = $subtotal + ($cart[$row]->price * $cart[$row]->qty);
                                            }
                                            $tax = (18 / 100) * $subtotal;
                                            $amount = $tax + $subtotal;
                                            ?>
                                            <tr>
                                                <td>SubTotal</td>
                                                <td><?php echo $currency ?> <?php echo round($subtotal,2); ?></td>
                                            </tr>
                                            <?php echo $ifdtext;
                                            ?>
                                            <tr id="paymentdiscount_applied" hidden>
                                                <td>Discount</td>
                                                <td>- <?php echo $currency ?> <span id="paymentorder_details_discount_amount"></span> </td>
                                            </tr>
                                            <tr>
                                                <td>Tax (GST +18%)</td>
                                                <td> <?php echo $currency ?> <span id="paymentorder_details_tax"> <?php echo round($tax,2); ?></span></td>
                                            </tr>

                                            <tr>
                                                <td>Final Total</td>
                                                <td> <?php echo $currency ?> <input type="text" id="paymentamount" name="amount" value="<?php echo round($amount,2); ?>" style="width: 30%; border: none;" readonly></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <h4 class="card-title">Payment Details</h4>
                                <div class="table-responsive">
                                    <table class="table">
                                        <tbody>
                                            <tr>
                                                <td>Order Id</td>
                                                <td> <span id="payment_OrderId"></span></td>
                                            </tr>
                                            <tr>
                                                <td>Transaction Id</td>
                                                <td> <span id="payment_tid"></span></td>
                                            </tr>
                                            <tr>
                                                <td>Payment Id</td>
                                                <td> <span id="payment_PaymentId"></span></td>
                                            </tr>
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
    <!-- // end .section -->

    <footer class="my-5 text-center">
        <!-- Copyright removal is not prohibited! -->
        <p class="mb-2"><small>COPYRIGHT © 2020. ALL RIGHTS RESERVED. <a href="https://leadcon.co/">LEADCON</a></small></p>
    </footer>

    <!-- jQuery and Bootstrap -->
    <script data-cfasync="false" src="../channelpartners/jsnew/email-decode.min.js"></script>
    <script src="../channelpartners/jsnew/jquery-3.2.1.min.js" type="44fcf0bba18ba848b773285d-text/javascript"></script>
    <script src="../channelpartners/jsnew/bootstrap.bundle.min.js" type="44fcf0bba18ba848b773285d-text/javascript"></script>
    <!-- Plugins JS -->
    <script src="../channelpartners/jsnew/owl.carousel.min.js" type="44fcf0bba18ba848b773285d-text/javascript"></script>
    <!-- Custom JS -->
    <script src="../channelpartners/jsnew/script.js" type="44fcf0bba18ba848b773285d-text/javascript"></script>

    <script src="../channelpartners/jsnew/rocket-loader.min.js" data-cf-settings="44fcf0bba18ba848b773285d-|49" defer=""></script>
</body>
<script src="js/payment.js" type="text/javascript"></script>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>


</body>

</html>