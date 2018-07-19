<?php

namespace Deployer;
require 'recipe/common.php';

# ----- Deployment properties ---
set('forwardAgent', true);
set('git_tty', false); // [Optional] Allocate tty for git clone. Default value is false.
set('ssh_multiplexing', true);
set('php', '/usr/local/bin/php');
set('magerun', '/usr/local/bin/n98-magerun2');
set('composer', '/usr/local/bin/composer');
set('default_timeout', 360);
set('release_name', function (){return date('YmdHis');});

# ----- Magento properties -------
set('is_production', 0);
set('languages', 'en_US');
set('magento_dir', '/');
set('magento_bin', '{{magento_dir}}bin/magento');
set('shared_files', [
    '{{magento_dir}}app/etc/env.php',
    '{{magento_dir}}var/.maintenance.ip',
    '{{magento_dir}}pub/robots.txt',
    '{{magento_dir}}pub/sitemap.xml'
]);
set('shared_dirs', [
    '{{magento_dir}}var/composer_home',
    '{{magento_dir}}var/log',
    '{{magento_dir}}var/session',
    '{{magento_dir}}var/backups',
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
    '{{magento_dir}}var/tmp'
]);


# ----- Magento 2 Tasks -------

desc('Composer Install');
task('composer:install', function () {
    run("cd {{release_path}}{{magento_dir}} && {{composer}} install --no-dev --prefer-dist --optimize-autoloader ");
    run('cd {{release_path}}{{magento_dir}} && {{composer}} dump-autoload --no-dev --no-interaction --optimize 2>&1');
});

desc('Composer update');
task('composer:update', function () {
    run("cd {{release_path}}{{magento_dir}} && {{composer}} update --no-dev --prefer-dist --optimize-autoloader ");
    run('cd {{release_path}}{{magento_dir}} && {{composer}} dump-autoload --no-dev --no-interaction --optimize 2>&1');
});

desc('Compile Magento DI');
task('magento:compile', function () {
    run("{{php}} {{release_path}}{{magento_bin}} setup:di:compile");
});

desc('Deploy assets');
task('magento:deploy:assets', function () {
    run("{{php}} {{release_path}}{{magento_bin}} setup:static-content:deploy");
});

desc('Enable maintenance mode');
task('magento:maintenance:enable', function () {
    run("if [ -d $(echo {{deploy_path}}/current) ]; then {{php}} {{release_path}}{{magento_bin}} maintenance:enable; fi");
});

desc('Disable maintenance mode');
task('magento:maintenance:disable', function () {
    run("if [ -d $(echo {{deploy_path}}/current) ]; then {{php}} {{release_path}}{{magento_bin}} maintenance:disable; fi");
});

desc('Upgrade magento database');
task('magento:upgrade:db', function () {
    run("{{php}} {{release_path}}{{magento_bin}} setup:upgrade --keep-generated");
    run("{{magerun}} sys:setup:downgrade-versions --root-dir={{release_path}}");
});

desc('Flush Magento Cache');
task('magento:cache:flush', function () {
    run("{{php}} {{release_path}}{{magento_bin}} cache:flush");
});

desc('Enable allow symlink config in Magento Panel');
task('magento:config', function () {
    run("{{php}} {{release_path}}{{magento_bin}} config:set dev/template/allow_symlink 1");
});

desc('Set deploy mode set');
task('magento:deploy:mode:set', function () {
    if(get('is_production')){    
        run("{{php}} -f {{release_path}}{{magento_bin}} deploy:mode:set production --skip-compilation");
    }else{
        run("{{php}} -f {{release_path}}{{magento_bin}} deploy:mode:set developer");
    }
});

desc('Set right permissions to folders and files');
task('magento:setup:permissions', function () {
    run("find {{release_path}}{{magento_dir}} -type d ! -perm 2770 -exec chmod 2770 {} +");
    run("find {{release_path}}{{magento_dir}} -type f ! -perm 660 -exec chmod 660 {} +");
    run("chmod +x {{release_path}}{{magento_bin}}");
});

desc('Redis cache flush');
task('redis:flush', function () {
    run("redis-cli -n 0 flushall");
});

desc('OPCache cache flush');
task('opcache:flush', function () {
    run("php -r 'echo opcache_reset();'");
});

desc('Magento2 deployment operations');
task('deploy:magento', [
    'magento:setup:permissions',
    'magento:deploy:mode:set',
    'magento:deploy:assets',
    'magento:compile',
    'magento:maintenance:enable',
    'magento:upgrade:db',
    'magento:maintenance:disable',
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
//    'deploy:writable',
    'deploy:vendors',
    'deploy:clear_paths',
    'composer:install',
    'magento:config',
    'deploy:magento',
    'deploy:symlink',
    'opcache:flush',
    'redis:flush',
    'deploy:unlock',
    'cleanup',
    'success'
]);

after('deploy:failed', 'magento:maintenance:disable');
after('deploy:failed', 'deploy:unlock');

before('rollback', 'rollback:validate');
after('rollback', 'maintenance:disable');
after('rollback', 'cache:clear');