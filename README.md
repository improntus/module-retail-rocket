# Retail Rocket Integration 
##### Version 1.0.19

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

#### [Wiki English](https://github.com/improntus/module-retail-rocket/wiki/User-Manual-English)
#### [Wiki Español](https://github.com/improntus/module-retail-rocket/wiki/Manual-de-uso---Espa%C3%B1ol)

## Author

[Improntus](https://www.improntus.com) - Elevating Digital Experience | [Adobe Gold Solution Partner](https://solutionpartners.adobe.com/s/directory/detail/improntus#expertise)
