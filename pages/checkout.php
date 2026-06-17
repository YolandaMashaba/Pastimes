<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — Pastimes</title>
    <link rel="stylesheet" href="/pastimes-marketplace-v2/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .checkout-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        @media (max-width: 768px) {
            .checkout-layout {
                grid-template-columns: 1fr;
            }
        }
        .order-summary, .checkout-form {
            background: var(--color-bg-secondary);
            padding: 1.5rem;
            border-radius: 8px;
        }
        .cart-items-list {
            margin-bottom: 1.5rem;
        }
        .cart-item-summary {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid var(--color-border);
            align-items: center;
        }
        .cart-item-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
        }
        .cart-item-img-placeholder {
            width: 80px;
            height: 80px;
            background: var(--color-bg);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-text-muted);
        }
        .cart-item-details h4 {
            margin: 0 0 0.5rem 0;
            font-size: 1rem;
        }
        .cart-item-details p {
            margin: 0.25rem 0;
            font-size: 0.9rem;
            color: var(--color-text-muted);
        }
        .cart-item-details .price {
            color: var(--color-primary);
            font-weight: 600;
            font-size: 1.1rem;
        }
        .order-totals {
            border-top: 2px solid var(--color-border);
            padding-top: 1rem;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            font-size: 1rem;
        }
        .total-row-grand {
            font-size: 1.2rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--color-border);
        }
        .checkout-form h2 {
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--color-border);
            border-radius: 6px;
            font-size: 1rem;
        }
        .payment-details {
            display: none;
            margin-top: 1rem;
            padding: 1rem;
            background: var(--color-bg);
            border-radius: 6px;
        }
        .payment-details.active {
            display: block;
        }
        .payment-details .form-group {
            margin-bottom: 0.75rem;
        }
        .btn-large {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            width: 100%;
        }
        .checkout-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .checkout-header h1 {
            margin-bottom: 0.5rem;
        }
        .flash-error {
            background: #fee;
            color: #c33;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            border: 1px solid #fcc;
        }
        .loading {
            text-align: center;
            padding: 2rem;
            color: var(--color-text-muted);
        }
    </style>
</head>
<body>
<div class="container">
    <div style="padding: 1rem 0; border-bottom: 1px solid var(--color-border); margin-bottom: 2rem;">
        <a href="/pastimes-marketplace-v2/pages/dashboard.php?tab=buyer" style="color: var(--color-primary); text-decoration: none; font-weight: 500;">← Back to Dashboard</a>
    </div>
    <div class="checkout-header">
        <h1><i class="fas fa-shopping-cart"></i> Checkout</h1>
        <p>Review your order and complete your purchase.</p>
    </div>

    <div id="error-container"></div>

    <div class="checkout-layout">
        <!-- Order Summary -->
        <div class="order-summary">
            <h2>Order Summary</h2>
            <div class="cart-items-list" id="cart-items">
                <div class="loading">Loading cart items...</div>
            </div>
            <div class="order-totals">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span id="subtotal">R0.00</span>
                </div>
                <div class="total-row">
                    <span>Shipping:</span>
                    <span id="shipping">R60.00</span>
                </div>
                <div class="total-row total-row-grand">
                    <strong>Total:</strong>
                    <strong id="total">R60.00</strong>
                </div>
            </div>
        </div>

        <!-- Checkout Form -->
        <div class="checkout-form">
            <h2>Shipping & Payment</h2>
            <form method="POST" action="/pastimes-marketplace-v2/pages/actions/process-checkout.php" id="checkout-form">
                <div class="form-group">
                    <label for="shipping_address">
                        Shipping Address
                        <span class="field-required">*</span>
                    </label>
                    <textarea 
                        id="shipping_address" 
                        name="shipping_address" 
                        rows="3" 
                        required
                        placeholder="Enter your full shipping address..."
                    ></textarea>
                </div>

                <div class="form-group">
                    <label for="payment_method">
                        Payment Method
                        <span class="field-required">*</span>
                    </label>
                    <select id="payment_method" name="payment_method" required onchange="togglePaymentDetails()">
                        <option value="">Select payment method...</option>
                        <option value="credit_card">Credit Card</option>
                        <option value="eft">EFT (Electronic Funds Transfer)</option>
                        <option value="cash_on_delivery">Cash on Delivery</option>
                    </select>
                </div>

                <!-- Credit Card Details -->
                <div id="credit_card_details" class="payment-details">
                    <div class="form-group">
                        <label for="card_number">Card Number</label>
                        <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                    </div>
                    <div class="form-group">
                        <label for="card_expiry">Expiry Date</label>
                        <input type="text" id="card_expiry" name="card_expiry" placeholder="MM/YY" maxlength="5">
                    </div>
                    <div class="form-group">
                        <label for="card_cvv">CVV</label>
                        <input type="text" id="card_cvv" name="card_cvv" placeholder="123" maxlength="3">
                    </div>
                </div>

                <!-- EFT Details -->
                <div id="eft_details" class="payment-details">
                    <p style="margin-bottom: 1rem;"><strong>Bank Details:</strong></p>
                    <p>Bank: First National Bank</p>
                    <p>Account Name: Pastimes Marketplace</p>
                    <p>Account Number: 1234567890</p>
                    <p>Branch Code: 250655</p>
                    <p style="margin-top: 1rem; color: var(--color-text-muted);">Reference: Your Order ID</p>
                </div>

                <!-- Cash on Delivery Details -->
                <div id="cod_details" class="payment-details">
                    <p><strong>Cash on Delivery</strong></p>
                    <p style="color: var(--color-text-muted);">Please have the exact amount ready when your order is delivered.</p>
                </div>

                <button type="submit" class="btn btn-primary btn-large">
                    <i class="fas fa-lock"></i> Place Order
                </button>

                <a href="/pastimes-marketplace-v2/pages/dashboard.php?tab=buyer" class="btn btn-outline" style="display: block; text-align: center; margin-top: 1rem;">Back to Cart</a>
            </form>
        </div>
    </div>
</div>

<script>
// Fetch cart items via AJAX
fetch('/pastimes-marketplace-v2/pages/actions/get-cart-items.php')
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            window.location.href = '/pastimes-marketplace-v2/pages/dashboard.php?tab=buyer';
            return;
        }
        
        if (data.items.length === 0) {
            window.location.href = '/pastimes-marketplace-v2/pages/dashboard.php?tab=buyer';
            return;
        }
        
        displayCartItems(data.items);
    })
    .catch(error => {
        console.error('Error fetching cart items:', error);
        document.getElementById('cart-items').innerHTML = '<div class="flash-error">Failed to load cart items. Please try again.</div>';
    });

function displayCartItems(items) {
    const container = document.getElementById('cart-items');
    let subtotal = 0;
    
    container.innerHTML = items.map(item => {
        const itemTotal = item.price * item.quantity;
        subtotal += itemTotal;
        
        return `
            <div class="cart-item-summary">
                ${item.image_path ? `<img src="${item.image_path}" alt="${item.title}" class="cart-item-img">` : '<div class="cart-item-img-placeholder">No Image</div>'}
                <div class="cart-item-details">
                    <h4>${item.title}</h4>
                    <p>Quantity: ${item.quantity}</p>
                    <p class="price">R${itemTotal.toFixed(2)}</p>
                </div>
            </div>
        `;
    }).join('');
    
    const shipping = 60.00;
    const total = subtotal + shipping;
    
    document.getElementById('subtotal').textContent = `R${subtotal.toFixed(2)}`;
    document.getElementById('total').textContent = `R${total.toFixed(2)}`;
}

function togglePaymentDetails() {
    const paymentMethod = document.getElementById('payment_method').value;
    const creditCardDetails = document.getElementById('credit_card_details');
    const eftDetails = document.getElementById('eft_details');
    const codDetails = document.getElementById('cod_details');

    creditCardDetails.classList.remove('active');
    eftDetails.classList.remove('active');
    codDetails.classList.remove('active');

    if (paymentMethod === 'credit_card') {
        creditCardDetails.classList.add('active');
    } else if (paymentMethod === 'eft') {
        eftDetails.classList.add('active');
    } else if (paymentMethod === 'cash_on_delivery') {
        codDetails.classList.add('active');
    }
}

// Check for URL error parameters
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('error') === 'missing_fields') {
    document.getElementById('error-container').innerHTML = '<div class="flash-error">Please fill in all required fields.</div>';
}
if (urlParams.get('error') === 'processing_failed') {
    document.getElementById('error-container').innerHTML = '<div class="flash-error">Failed to process your order. Please try again.</div>';
}
</script>

</body>
</html>
