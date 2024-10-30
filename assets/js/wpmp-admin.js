jQuery(document).ready(function($){

    if ($('#woocommerce_mpwp_payment_gateway').length) {
        $('#woocommerce_mpwp_payment_gateway + .form-table').addClass('mpwp-custom-payment_gateway');
        $('#woocommerce_mpwp_payment_gateway + .form-table').show();
    }

});