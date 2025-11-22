function validateOrderForm() {
    const name = document.forms["orderForm"]["customer_name"].value.trim();
    const pizza = document.forms["orderForm"]["pizza_id"].value;
    const qty = document.forms["orderForm"]["quantity"].value;

    if (name === "" || pizza === "" || qty <= 0) {
        alert("Please fill out all fields correctly.");
        return false;
    }
    return true;
}

// jQuery enhancements for better user experience
$(document).ready(function() {
    // Add to cart animation
    $('.add-to-cart-btn').on('click', function(e) {
        e.preventDefault();
        const button = $(this);
        const originalText = button.html();
        
        // Show loading state
        button.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...');
        button.prop('disabled', true);
        
        // Submit form after a short delay to show animation
        setTimeout(function() {
            button.closest('form').submit();
        }, 800);
    });
    
    // Real-time quantity validation
    $('input[name="quantity"]').on('change keyup', function() {
        const val = parseInt($(this).val());
        if (val < 1) {
            $(this).val(1);
        } else if (val > 20) {
            $(this).val(20);
            alert("Maximum quantity per item is 20");
        }
    });
    
    // Enhanced form validation
    $('#order-form').on('submit', function(e) {
        const name = $('#customer_name').val().trim();
        if (name.length < 2) {
            e.preventDefault();
            alert("Please enter a valid name (at least 2 characters)");
            $('#customer_name').focus();
            return false;
        }
    });
    
    // Form submission with loading state
    $('form').on('submit', function() {
        const submitBtn = $(this).find('button[type="submit"]');
        if (submitBtn.length) {
            submitBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
            submitBtn.prop('disabled', true);
        }
    });
});