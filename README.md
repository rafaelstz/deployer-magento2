# Deployer Magento 2
Deployer recipe for Magento 2 project. This adds some useful tasks and operations.

Install
-------

Install it using Composer:

```
composer require --dev rafaelstz/deployer-magento2 dev-master
```

Usage
-----

Require the recipe in your `deploy.php`:

```php

namespace Deployer;

require __DIR__ . '/vendor/rafaelstz/deployer-magento2/deploy.php';

// ... usual Deployer configuration
```

