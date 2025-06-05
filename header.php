<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include the DB class
require_once 'payments_config.php';

// Initialize cart count
$cart_rows_number = 0;

// Get user ID from session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Initialize database connection using DB class
try {
    $db = new DB();
    $conn = $db->db;
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    $conn = null;
}

if(isset($message)){
   foreach($message as $message){
      echo '
      <div class="message">
         <span>'.$message.'</span>
         <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
      </div>
      ';
   }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Bookly</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>

<header class="header">

   <div class="header-1">
      <div class="flex">
         <div class="share">
            <a href="#" class="fab fa-facebook-f"></a>
            <a href="#" class="fab fa-twitter"></a>
            <a href="#" class="fab fa-instagram"></a>
            <a href="#" class="fab fa-linkedin"></a>
         </div>
         <?php if (!$user_id): ?>
            <p> new <a href="login.php">login</a> | <a href="register.php">register</a> </p>
         <?php else: ?>
            <p>Welcome back!</p>
         <?php endif; ?>
      </div>
   </div>

   <div class="header-2">
      <div class="flex">
         <a href="home.php" class="logo">Bookly.</a>

         <nav class="navbar">
            <a href="home.php">home</a>
            <a href="about.php">about</a>
            <a href="shop.php">shop</a>
            <a href="contact.php">contact</a>
            <a href="orders.php">orders</a>
         </nav>

         <div class="icons">
            <div id="menu-btn" class="fas fa-bars"></div>
            <a href="search_page.php" class="fas fa-search"></a>
            <div id="user-btn" class="fas fa-user"></div>
            <?php
            if ($user_id && $conn) {
                try {
                    // Use prepared statement to prevent SQL injection
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM `cart` WHERE user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        $cart_rows_number = $row['count'];
                    }
                    $stmt->close();
                } catch (Exception $e) {
                    error_log("Cart query error: " . $e->getMessage());
                }
            }
            ?>
            <a href="cart.php"> <i class="fas fa-shopping-cart"></i> <span>(<?php echo $cart_rows_number; ?>)</span> </a>
         </div>

         <?php if ($user_id): ?>
         <div class="user-box">
            <p>username : <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></span></p>
            <p>email : <span><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></span></p>
            <a href="logout.php" class="delete-btn">logout</a>
         </div>
         <?php endif; ?>
      </div>
   </div>
</header>
