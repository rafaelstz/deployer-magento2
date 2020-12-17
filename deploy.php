<?php

// Create by Rafael Corrêa Gomes

// Installing deployer and dependencies
//
// curl -LO https://deployer.org/deployer.phar && sudo mv deployer.phar /usr/local/bin/dep && sudo chmod +x /usr/local/bin/dep
// composer require deployer/recipes --dev
// composer require rafaelstz/deployer-magento2 dev-master --dev

namespace Deployer;

require_once 'recipe/common.php';

# ----- Deployment properties ---
set('forwardAgent', true);
set('git_tty', false); // [Optional] Allocate tty for git clone. Default value is false.
set('ssh_multiplexing', true);
set('php', '/usr/local/bin/php');
set('magerun', '/usr/local/bin/n98-magerun2');
set('composer', '/usr/local/bin/composer');
set('keep_releases', 3);
// set('default_timeout', 360);
set('verbose', '--quiet'); // Use --quite or -v or -vvv
set('magerun_params', '--skip-root-check --root-dir={{release_path}}');
set('release_name', function () {
    return date('YmdHis');
});

# ----- Magento properties -------
set('is_production', 0);
set('compile_UAT', 1);
set('languages', 'en_US');
set('magento_dir', '/');
set('magento_bin', 'bin/magento');
set('shared_files', [
    '{{magento_dir}}app/etc/env.php',
    '{{magento_dir}}var/.maintenance.ip'
    // '{{magento_dir}}pub/robots.txt',
    // '{{magento_dir}}pub/sitemap.xml'
]);
set('shared_dirs', [
    '{{magento_dir}}var/composer_home',
    '{{magento_dir}}var/log',
    '{{magento_dir}}var/cache',
    '{{magento_dir}}var/export',
    '{{magento_dir}}var/report',
    '{{magento_dir}}var/import_history',
    '{{magento_dir}}var/session',
    '{{magento_dir}}var/importexport',
    '{{magento_dir}}var/backups',
    '{{magento_dir}}var/tmp',
    '{{magento_dir}}pub/sitemaps',
    '{{magento_dir}}pub/media'
]);
set('writable_dirs', [
    '{{magento_dir}}var',
    '{{magento_dir}}pub/static',
    '{{magento_dir}}pub/media',
    '{{magento_dir}}generation'
]);
set('clear_paths', [
    '{{magento_dir}}pub/static/_cache',
    '{{magento_dir}}var/cache',
    '{{magento_dir}}var/page_cache',
    '{{magento_dir}}var/view_preprocessed'
]);

// Check Magento version
set('magento_version', function (){
    return run("{{magerun}} sys:info version {{magerun_params}} {{verbose}}");
});

# ----- Magento 2 Tasks -------

require_once __DIR__ . '/recipes/backup.php';
require_once __DIR__ . '/recipes/composer.php';
require_once __DIR__ . '/recipes/deploy.php';
require_once __DIR__ . '/recipes/server.php';
require_once __DIR__ . '/recipes/database.php';
require_once __DIR__ . '/recipes/logs.php';
require_once __DIR__ . '/recipes/files.php';

desc('Magento2 deployment operations');
task('deploy:magento', [
    'magento:setup:permissions',
    'magento:config',
    'magento:clean:generated',
    'magento:deploy:mode:set',
    'magento:upgrade:db',
    'magento:deploy:assets',
    'magento:compile',
    'magento:cache:flush',
    'magento:setup:permissions'
]);

desc('Deploying...');
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:vendors',
    'deploy:clear_paths',
    // 'deploy:writable',
    'deploy:magento',
    'deploy:symlink',
    'deploy:unlock',
    // 'deploy:previous', // Use in case you need put the previous release in maintenance
    'cleanup',
    'success'
]);

after('deploy:failed', 'deploy:unlock');
// after('deploy:failed', 'magento:maintenance:disable');

before('rollback', 'rollback:validate');
after('rollback', 'magento:upgrade:db');
after('rollback', 'magento:maintenance:disable');
after('rollback', 'magento:cache:flush');

// composer require deployer/recipes --dev

// ======= Slack
// require 'vendor/deployer/recipes/recipe/slack.php';

//set('slack_webhook', 'https://hooks.slack.com/services/YOUR/REGISTER/HERE');
//before('deploy', 'slack:notify');
//after('success', 'slack:notify:success');
//after('deploy:failed', 'slack:notify:failure');
