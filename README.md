# Retail Rocket Integration 
##### Version 1.0.0

## Instalation Steps

### Developement mode
```
1. composer require improntus/retailrocket:"dev-master"
2. php bin/magento module:enable Improntus_RetailRocket --clear-static-content
3. php bin/magento setup:upgrade
4. rm -rf var/di var/view_preprocessed var/cache generated/*
5. php bin/magento setup:static-content:deploy
```

### Production mode
```
1. composer require improntus/retailrocket:"dev-master"
2. php bin/magento module:enable Improntus_RetailRocket --clear-static-content
3. php bin/magento setup:upgrade
4. rm -rf var/di var/view_preprocessed var/cache generated/*
5. php bin/magento deploy:mode:set production
```

## Author

* Improntus - <http://www.improntus.com> <_Ecommerce done right_/>

