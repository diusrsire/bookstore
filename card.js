// Create a Stripe client.
var stripe = Stripe(publishable_key);
  
// Create an instance of Elements.
var elements = stripe.elements();
  
// Custom styling for the card Element
var style = {
    base: {
        color: '#32325d',
        fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
        fontSmoothing: 'antialiased',
        fontSize: '16px',
        '::placeholder': {
            color: '#aab7c4'
        },
        ':-webkit-autofill': {
            color: '#32325d'
        }
    },
    invalid: {
        color: '#dc3545',
        iconColor: '#dc3545',
        '::placeholder': {
            color: '#dc3545'
        }
    }
};
  
// Create an instance of the card Element.
var card = elements.create('card', {
    style: style,
    hidePostalCode: true, // Hide postal code field since we don't need it
    iconStyle: 'solid'
});
  
// Add an instance of the card Element into the `card-element` <div>.
card.mount('#card-element');

// Store original button text
var form = document.getElementById('payment-form');
var submitButton = form.querySelector('button[type="submit"]');
var originalButtonText = submitButton.textContent;
  
// Handle real-time validation errors from the card Element.
card.addEventListener('change', function(event) {
    var displayError = document.getElementById('card-errors');
    if (event.error) {
        showError(event.error.message);
        submitButton.disabled = true;
    } else {
        clearError();
        submitButton.disabled = false;
    }
});

// Function to show loading state
function setLoading(isLoading) {
    if (isLoading) {
        // Disable the submit button and show a loading spinner
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner"></span> Processing...';
    } else {
        // Re-enable the submit button and restore the original text
        submitButton.disabled = false;
        submitButton.textContent = originalButtonText;
    }
}

// Function to show error
function showError(message) {
    var displayError = document.getElementById('card-errors');
    displayError.textContent = message;
    displayError.style.display = 'block';
    setLoading(false);
}

// Function to clear error
function clearError() {
    var displayError = document.getElementById('card-errors');
    displayError.textContent = '';
    displayError.style.display = 'none';
}

// Handle form submission
form.addEventListener('submit', function(event) {
    event.preventDefault();
    
    // Show loading state
    setLoading(true);

    // Create payment method and confirm payment intent
    stripe.createPaymentMethod({
        type: 'card',
        card: card,
        billing_details: {
            // Add billing details if needed
        }
    }).then(function(result) {
        if (result.error) {
            showError(result.error.message);
        } else {
            // Handle successful payment method creation
            handlePaymentMethodCreated(result.paymentMethod);
        }
    });
});

// Function to handle successful payment method creation
function handlePaymentMethodCreated(paymentMethod) {
    // Insert the payment method ID into the form
    var hiddenInput = document.createElement('input');
    hiddenInput.setAttribute('type', 'hidden');
    hiddenInput.setAttribute('name', 'payment_method_id');
    hiddenInput.setAttribute('value', paymentMethod.id);
    form.appendChild(hiddenInput);

    // Submit the form
    form.submit();
}