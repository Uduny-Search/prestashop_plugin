$(document).ready(function () {
  if (typeof prestashop !== 'undefined') {
    prestashop.on('updateCart', function (e) {
      var products = {};
      e.resp.cart.products.forEach(function(item){
        if(e.resp.id_product == item.id){
          products = {
            product_id: item.id,
            product_name: item.name,
            item_price: item.price_amount,
          };
        }
      });
      
      if($('#ud-src-frame').length > 0){
        const iframe = document.getElementById('ud-src-frame'); // Get a reference to your iframe element
        if (!iframe || !iframe.contentWindow) {
          console.error('Main Website: Iframe not found or not accessible.');
          return;
        }

        // message payload
        const message = {
          action: 'uds_tracking',
          event: {
            // Add your tracking data here
            // Example:
            event_name: 'add_to_cart',
            product_id: products.product_id,
            product_name: products.product_name,
          }
        };
        
        // Send the message to the iframe's content window
        iframe.contentWindow.postMessage(message, '*');
        console.log(message);
      }
    });
  }

  if($('#udsi_confirmed').length > 0){
    const iframe = document.getElementById('ud-src-frame'); // Get a reference to your iframe element
    if (!iframe || !iframe.contentWindow) {
      console.error('Main Website: Iframe not found or not accessible.');
      return;
    }

    // message payload
    const message = {
      action: 'uds_tracking',
      event: {
        // Add your tracking data here
        // Example:
        event_name: 'purchase',
        order_id: $('#udsi_confirmed').data('id'),
        products: $('#udsi_confirmed').data('products'),
        order_amount: $('#udsi_confirmed').data('total'),
      }
    };
    
    // Send the message to the iframe's content window
    iframe.contentWindow.postMessage(message, '*');
    console.log(message);
  }
});