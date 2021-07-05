(function ($) {

    $(".cart .quantity input[name='quantity']").change(function () {
        var quantity = $('.cart .quantity input[name="quantity"]').val();
        var es_qt = $('#es_qt_product').val();
        if (es_qt == undefined) {
            $('<input>', {
                type: 'hidden',
                id: 'es_qt_product',
                name: 'es_qt_product',
                value: quantity
            }).appendTo('#enviosimples_shipping_forecast');
        }else{
            $('#es_qt_product').val(quantity);
        }
    });
    $( document ).ready(function() {
        var quantity = $('.cart .quantity input[name="quantity"]').val();
        $('<input>', {
            type: 'hidden',
            id: 'es_qt_product',
            name: 'es_qt_product',
            value: quantity
        }).appendTo('#enviosimples_shipping_forecast');
    });

})(jQuery);