# Retail Rocket Integration 
##### Version 1.0.18

## Instalation Steps

### Developement mode
```
1. composer require improntus/module-retail-rocket
2. php bin/magento module:enable Improntus_RetailRocket --clear-static-content
3. php bin/magento setup:upgrade
4. rm -rf var/di var/view_preprocessed var/cache generated/*
5. php bin/magento setup:static-content:deploy
```

### Production mode
```
1. composer require improntus/module-retail-rocket
2. php bin/magento module:enable Improntus_RetailRocket --clear-static-content
3. php bin/magento setup:upgrade
4. rm -rf var/di var/view_preprocessed var/cache generated/*
5. php bin/magento deploy:mode:set production
```

## User manual

#### [Wiki English](doc:https://github.com/improntus/module-retail-rocket/wiki/User-Manual-English)
#### [Wiki Español](doc:https://github.com/improntus/module-retail-rocket/wiki/Manual-de-uso---Espa%C3%B1ol)

## Author

[Improntus](doc:https://www.improntus.com) - Elevating Digital Experience | Adobe Solution Partner

