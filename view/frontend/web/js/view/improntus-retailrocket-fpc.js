define([
    'ko',
    'uiComponent',
    'Magento_Customer/js/customer-data'
], function (ko, Component, customerData) {
    'use strict';
    return Component.extend({
        initialize: function () {
            var self = this;
            self._super();
            customerData.get('improntus-retailrocket-fpc').subscribe(function(loadedData)
            {
                if (loadedData && "undefined" !== typeof loadedData.events)
                {
                    for (var eventCounter = 0; eventCounter < loadedData.events.length; eventCounter++)
                    {
                        var eventData = loadedData.events[eventCounter];

                        if ("undefined" !== typeof eventData.eventAdditional && eventData.eventAdditional)
                        {
                            var productId = eventData.eventAdditional.productId;
                            var stockId = eventData.eventAdditional.stockId;

                            try {
                                if(stockId)
                                {
                                    rrApi.addToBasket(productId,{'stockId': stockId})
                                }
                                else{
                                    rrApi.addToBasket(productId)
                                }
                            } catch(e) {console.log(e)}
                        }
                    }
                    customerData.set('improntus-retailrocket-fpc', {});
                }
            });
        }
    });
});