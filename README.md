# Retail Rocket Integration 

## Instalation Steps

```
1. composer require improntus/retailrocket:"dev-master"
2. php bin/magento module:enable Improntus_RetailRocket --clear-static-content
3. php bin/magento setup:upgrade
4. rm -rf var/di var/view_preprocessed
5. php bin/magento setup:static-content:deploy
```

## Autor

* Improntus - <http://www.improntus.com>

