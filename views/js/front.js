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
window.udSearchAddToCart = function(id_product, id_product_attribute, param){
    $.ajax({
        url: udsi_cart_url,
        method: 'POST',
        dataType: 'JSON',
        data: {
            action: 'update',
            id_product: id_product,
            id_customization: 0,
            qty: 1,
            add: 1,
            param
        },
        success: function(result){
            console.log(result);
            if(result.hasError) return;
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
                    id_product_attribute: result.id_product_attribute,
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

$(document).ready(function () {
    session_id = window.UdunySearch.user_session;
    if(session_id){
        $.ajax({
            url: udsi_options.ajax_url,
            type: 'POST',
            data: {
                ajax: 1,
                action: 'sess',
                session_id: session_id,
                token: udsi_options.token
            },
            success: function (response) {
            },
            error: function (xhr, status, error) {
            }
        });
    }
})

function udsSetSession(key, value) {
    const data = {
        value: value,
        expiry: new Date().getTime() + 7200000
    };
    sessionStorage.setItem(key, JSON.stringify(data));
}

function udsGetSession(key) {
    const data = JSON.parse(sessionStorage.getItem(key));
    if (!data) return null;

    const currentTime = new Date().getTime();
    if (currentTime > data.expiry) {
        sessionStorage.removeItem(key);
        return null;
    }

    return data.value;
}
