/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a trade license awared by
 * PT. Jawa Kreatif Design.
 *
 * Any use, reproduction, modification or distribution
 * of this source file without the written consent of
 * PT. Jawa Kreatif Design It Is prohibited.
 *
 *  @author    UnusualDope <info@unusualdope.com>
 *  @copyright 2022 PT. Jawa Kreatif Design
 *  @license   Commercial license
 */

window.udSearchAddToCart = function(id_product, id_product_attribute, quantity, param){
    $.ajax({
        url: udsi_cart_url,
        method: 'POST',
        dataType: 'JSON',
        data: {
            action: 'update',
            id_product: id_product,
            id_customization: 0,
            qty: quantity,
            add: 1,
            param
        },
        success: function(result){
            console.log(result);
            prestashop.blockcart = prestashop.blockcart || {};

            var showModal = prestashop.blockcart.showModal || function (modal) {
                var $body = $('body');
                $body.append(modal);
                $body.one('click', '#blockcart-modal', function (event) {
                if (event.target.id === 'blockcart-modal') {
                    $(event.target).remove();
                }
                });
            };
            
            $.ajax({
                url: $('.blockcart').data('refresh-url'),
                method: 'POST',
                dataType: 'JSON',
                data: {
                    action: 'add-to-cart',
                    id_product: id_product,
                    id_product_attribute: id_product_attribute,
                    id_customization: 0,
                },
                success: function(result){
                    var html = $('<div />').append($.parseHTML(result.preview));
                    $('.blockcart').replaceWith($(result.preview).find('.blockcart'));
                    if (result.modal) {
                        showModal(result.modal);
                    }
                }
            });
        }
    });
}
