<?php
require_once 'stripe_api.php';
require_once 'header.php';

// Security checks
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['pending_order'])) {
    header('Location: checkout.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pending_order = $_SESSION['pending_order'];
?>

<div class="heading">
    <h3>Payment Details</h3>
    <p><a href="home.php">home</a> / payment</p>
</div>

<section class="checkout">
    <form action="charge_stripe.php" method="post" id="payment-form">
        <?php if (isset($_SESSION['payment_id'])) { ?>
            <div class="message">
                <span><?php echo 'Payment successful! Transaction ID: ' . htmlspecialchars($_SESSION['payment_id']); ?></span>
                <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
            </div>
            <?php unset($_SESSION['payment_id']); ?>
        <?php } elseif (isset($_SESSION['payment_error'])) { ?>
            <div class="message">
                <span><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['payment_error']); ?></span>
                <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
            </div>
            <?php unset($_SESSION['payment_error']); ?>
        <?php } ?>

        <h3>Order Summary</h3>
        
        <div class="display-order">
            <p>Order ID: <span>#<?php echo htmlspecialchars($pending_order['order_id']); ?></span></p>
            <p class="grand-total">Total Amount: <span>$<?php echo number_format($pending_order['amount'], 2); ?></span></p>
        </div>

        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
        <input type="hidden" name="amount" value="<?php echo htmlspecialchars($pending_order['amount']); ?>" />
        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($pending_order['order_id']); ?>" />
        
        <div class="flex">
            <div class="inputBox">
                <span>Card Information:</span>
                <div id="card-element" class="box">
                    <!-- Stripe Element will be inserted here -->
                </div>
                <div id="card-errors" role="alert" class="empty" style="display: none;"></div>
            </div>
        </div>

        <button type="submit" class="btn" id="submit-button">
            Pay $<?php echo number_format($pending_order['amount'], 2); ?>
        </button>

        <p style="text-align: center; margin-top: 2rem;">
            <i class="fas fa-lock"></i> Secure Payment - We accept all major credit cards
        </p>
    </form>
</section>

<script>
var publishable_key = '<?php echo STRIPE_PUBLISHABLE_KEY; ?>';
var returnUrl = '<?php echo RETURN_URL; ?>';
</script>
<script src="https://js.stripe.com/v3/"></script>
<script src="card.js"></script>

<?php require_once 'footer.php'; ?>