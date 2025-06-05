<?php
include 'config.php';
require_once 'payments_config.php';
require_once 'logger.php';  // Add logger

// Session is already started in config.php
$user_id = $_SESSION['user_id'] ?? null;

if(!$user_id){
   header('location:login.php');
   exit();
}

$dbClass = new DB(); // Initialize DB class

if(isset($_POST['order_btn'])){

   $name = mysqli_real_escape_string($conn, $_POST['name']);
   $number = mysqli_real_escape_string($conn, $_POST['number']);
   $email = mysqli_real_escape_string($conn, $_POST['email']);
   $method = mysqli_real_escape_string($conn, $_POST['method']);
   $address = mysqli_real_escape_string($conn, $_POST['country']);
   $placed_on = date('Y-m-d');

   $cart_total = 0;
   $cart_products = [];

   $cart_query = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id = '$user_id'") or die('Query failed');
   if(mysqli_num_rows($cart_query) > 0){
      while($cart_item = mysqli_fetch_assoc($cart_query)){
         $cart_products[] = $cart_item['name'].' ('.$cart_item['quantity'].')';
         $sub_total = ($cart_item['price'] * $cart_item['quantity']);
         $cart_total += $sub_total;
      }
   }

   $total_products = implode(', ', $cart_products);

   $order_query = mysqli_query($conn, "SELECT * FROM `orders` WHERE name = '$name' AND number = '$number' AND email = '$email' AND method = '$method' AND address = '$address' AND total_products = '$total_products' AND total_price = '$cart_total'") or die('Query failed');

   if($cart_total == 0){
      $message[] = 'Your cart is empty';
   } else {
      try {
         // Generate unique order ID
         $order_id = uniqid('order_');
         
         if($method == 'stripe') {
            $_SESSION['pending_order'] = [
               'order_id' => $order_id,
               'amount' => $cart_total,
               'products' => $total_products,
               'customer' => [
                  'name' => $name,
                  'email' => $email,
                  'address' => $address
               ]
            ];
            header('Location: stripe_form.php');
            exit;
         }
         
         if(mysqli_num_rows($order_query) > 0){
            $message[] = 'Order already placed!';
         } else {
            $stmt = $conn->prepare("INSERT INTO `orders`(user_id, name, number, email, method, address, total_products, total_price, placed_on) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssds", $user_id, $name, $number, $email, $method, $address, $total_products, $cart_total, $placed_on);
            $stmt->execute();

            // Simulated Payment ID (replace with actual ID from Stripe/PayPal)
            $payment_id = uniqid('pay_');

            $dbClass->insert_payment_details([
               'payment_id'     => $payment_id,
               'user_id'        => $user_id,
               'payer_name'     => $name,
               'payer_email'    => $email,
               'amount'         => $cart_total,
               'currency'       => 'USD',
               'payment_status' => 'pending',
               'created'        => date('Y-m-d H:i:s')
            ]);

            $message[] = 'Order placed successfully!';
            mysqli_query($conn, "DELETE FROM `cart` WHERE user_id = '$user_id'") or die('Failed to clear cart');
         }
      } catch (Exception $e) {
         $logger = new PaymentLogger();
         $logger->log($e->getMessage(), 'ERROR');
         $message[] = 'An error occurred. Please try again.';
      }
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Checkout</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'header.php'; ?>

<div class="heading">
   <h3>Checkout</h3>
   <p><a href="home.php">Home</a> / Checkout</p>
</div>

<section class="display-order">
   <?php  
      $grand_total = 0;
      $select_cart = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id = '$user_id'") or die('Query failed');
      if(mysqli_num_rows($select_cart) > 0){
         while($fetch_cart = mysqli_fetch_assoc($select_cart)){
            $total_price = ($fetch_cart['price'] * $fetch_cart['quantity']);
            $grand_total += $total_price;
   ?>
   <p><?php echo htmlspecialchars($fetch_cart['name']); ?> 
      <span>(<?php echo '$'.htmlspecialchars($fetch_cart['price']).' x '.htmlspecialchars($fetch_cart['quantity']); ?>)</span>
   </p>
   <?php
         }
      } else {
         echo '<p class="empty">Your cart is empty</p>';
      }
   ?>
   <div class="grand-total">Grand total: <span>$<?php echo number_format($grand_total, 2); ?></span></div>
</section>

<section class="checkout">
   <form action="" method="post">
      <h3>Place your order</h3>
      <div class="flex">
         <div class="inputBox">
            <span>Your name :</span>
            <input type="text" name="name" required placeholder="Enter your name">
         </div>
         <div class="inputBox">
            <span>Your number :</span>
            <input type="number" name="number" required placeholder="Enter your number">
         </div>
         <div class="inputBox">
            <span>Your email :</span>
            <input type="email" name="email" required placeholder="Enter your email">
         </div>
         <div class="inputBox">
            <span>Payment method :</span>
            <select name="method" required>
               <option value="credit card">Credit Card</option>
               <option value="paypal">PayPal</option>
               <option value="stripe">Stripe</option>
            </select>
         </div>
         <div class="inputBox">
            <span>Country :</span>
            <input type="text" name="country" required placeholder="e.g. United States">
         </div>
      </div>
      <input type="submit" value="Order now" class="btn" name="order_btn">
   </form>
</section>

<?php include 'footer.php'; ?>

<script src="js/script.js"></script>
</body>
</html>
