# Deployer Magento 2 recipe

[![Build Status](https://travis-ci.org/rafaelstz/deployer-magento2.svg?branch=master)](https://travis-ci.org/rafaelstz/deployer-magento2)
[![Tags](https://img.shields.io/github/tag/rafaelstz/deployer-magento2.svg)](https://github.com/rafaelstz/deployer-magento2/releases)
<a href="https://packagist.org/packages/rafaelstz/deployer-magento2"><img src="https://img.shields.io/packagist/dt/rafaelstz/deployer-magento2.svg" alt="Total Downloads"></a>

Easy tool to deploy and run automated commands in your Magento 2 servers.

How to install
-------

How to install [Deployer](https://deployer.org/):

```
curl -LO https://deployer.org/deployer.phar && sudo mv deployer.phar /usr/local/bin/dep && sudo chmod +x /usr/local/bin/dep
```

How to install this **Magento 2 recipe**:

```
composer require rafaelstz/deployer-magento2 --dev
```

How to use
-----

First of all, go to your project folder, then create a file called `deploy.php`. Inside of this file you can use this example below, modifying the values according with your project and server configurations.

```php
<?php

namespace Deployer;
require_once __DIR__ . '/vendor/rafaelstz/deployer-magento2/deploy.php';

// Project
set('application', 'My Project Name');
set('repository', 'git@bitbucket.org:imagination-media/my-project.git');
set('default_stage', 'staging');
//set('languages', 'en_US pt_BR');
//set('verbose', '-v');

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
    // ->configFile('~/.ssh/config')
    ->identityFile('~/.ssh/id_rsa')
    ->addSshOption('UserKnownHostsFile', '/dev/null')
    ->addSshOption('StrictHostKeyChecking', 'no');
```

**Just add this code below too if you don't want to use releases and symlinks**

```php
set('release_path', "{{deploy_path}}");
desc('Deploying...');
task('deploy', [
    'deploy:info',
    'deploy:lock',
    'magento:maintenance:enable',
    'git:update_code',
    'composer:install',
    'deploy:magento',
    'magento:maintenance:disable',
    'deploy:unlock',
    'success'
]);
```
