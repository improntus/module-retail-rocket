define([
    'ko',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'jquery'
], function (ko, Component, customerData, $) {
    'use strict';
    return Component.extend({
        initialize: function () {
            var self = this;
            self._super();
            customerData.get('improntus-retailrocket-newsletter').subscribe(function(loadedData)
            {
                if (loadedData && "undefined" !== typeof loadedData.events)
                {
                    for (var eventCounter = 0; eventCounter < loadedData.events.length; eventCounter++)
                    {
                        var eventData = loadedData.events[eventCounter];

                        if ("undefined" !== typeof eventData.eventAdditional && eventData.eventAdditional)
                        {
                            var userData = eventData.eventAdditional.user_data;
                            var userAdditional = {};

                            $.each(userData.additional,function(index, value)
                            {
                                userAdditional[index] = value;
                            });

                            try {
                                rrApi.setEmail(userData.email, userAdditional);
                            } catch(e) {}
                        }
                    }
                    customerData.set('improntus-retailrocket-newsletter', {});
                }
            });
        }
    });
});