# Deployer Magento 2
Deployer recipe for Magento 2 project. This adds some useful tasks and operations.

Install
-------

How to install Deployer:

```
curl -LO https://deployer.org/deployer.phar && sudo mv deployer.phar /usr/local/bin/dep && sudo chmod +x /usr/local/bin/dep
```

How to install this package:

```
composer require deployer/recipes --dev
composer require rafaelstz/deployer-magento2 dev-master --dev
```

Usage
-----

Create in your **root** folder a file called `deploy.php` and configure your project:

```php
<?php

namespace Deployer;
require __DIR__ . '/vendor/rafaelstz/deployer-magento2/deploy.php';

// Project
set('application', 'My Project Name');
set('repository', 'git@bitbucket.org:imagination-media/my-project.git');
set('default_stage', 'staging');

// Env Configurations
set('php', '/usr/bin/php70');
set('magerun', '/usr/bin/n98-magerun2');
set('composer', '/usr/bin/composer');

// Project Configurations
host('my-store.com')
    ->hostname('iuse.magemojo.com')
    ->user('my-user')
    ->port(22)
    ->set('deploy_path', '/home/my-project-folder')
    ->set('branch', 'master')
    ->set('is_production', 1)
    ->stage('staging')
    ->roles('master')
    ->configFile('~/.ssh/config')
    ->identityFile('~/.ssh/id_rsa')
    ->addSshOption('UserKnownHostsFile', '/dev/null')
    ->addSshOption('StrictHostKeyChecking', 'no');

// Slack Configurations
//set('slack_webhook', 'https://hooks.slack.com/services/YOUR/REGISTER/HERE');
//before('deploy', 'slack:notify');
//after('success', 'slack:notify:success');
//after('deploy:failed', 'slack:notify:failure');


```

