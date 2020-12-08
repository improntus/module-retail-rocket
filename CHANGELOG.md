CHANGELOG
---------

### 1.0.8

- Added "Remove pub from image url" possibility
- Now, configurable products are not sent in "stock id mode". It only sends simple products using its parent url 
- The "stock id mode" prices had been modified 

### 1.0.7

- Added acl permissions
- Fixed configurable items in xml feed
- The method of getting prices has been modified. "addPriceData" is not used anymore 
- Now small image in feed is set to 380px
- Fixed category ids in feed when a product has disabled categories   

### 1.0.6

- Now, if store code is added to urls it is set in stockId mode
- Fixed visibility issue
- Added "product creation start date filter"
- Added configurable crontab expression

### 1.0.5

- Now xml feed can be enabled by store  
- Added multiple "add to cart" 
- Fixed stockId by store 

### 1.0.4

- Added "Stock Id" integration 
- Added visibility param
- Added console command "retailrocket:feed:generate" (only for development purposes)
- Added optional filter for special chars on "description" attribute
- Updated crontab group

### 1.0.3

- XSS prevention fixes in templates

### 1.0.2

- Minor bug fixes

### 1.0.1

- Fixes in xml feed generation

### 1.0.0-RC

- Initial version
