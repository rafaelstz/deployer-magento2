<?php

// Installing deployer and dependencies
//
// curl -LO https://deployer.org/deployer.phar && sudo mv deployer.phar /usr/local/bin/dep && sudo chmod +x /usr/local/bin/dep
// composer require deployer/recipes --dev
// composer require rafaelstz/deployer-magento2 dev-master --dev

namespace Deployer;
require 'recipe/common.php';
require 'vendor/deployer/recipes/recipe/cachetool.php';
require 'vendor/deployer/recipes/recipe/slack.php';

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
    '{{magento_dir}}var/export',
    '{{magento_dir}}var/report',
    '{{magento_dir}}var/import_history',
    '{{magento_dir}}var/session',
    '{{magento_dir}}var/importexport',
    '{{magento_dir}}var/backups',
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
    '{{magento_dir}}var/tmp'
]);

set('magento_version', function (){
    return run("{{magerun}} sys:info version --root-dir={{release_path}}");
});

# ----- Magento 2 Tasks -------

desc('Composer Install');
task('composer:install', function () {
    run("cd {{release_path}}{{magento_dir}} && {{composer}} install --prefer-dist --optimize-autoloader --quiet");
    run('cd {{release_path}}{{magento_dir}} && {{composer}} dump-autoload --no-interaction --optimize 2>&1');
});

desc('Composer update');
task('composer:update', function () {
    run("cd {{release_path}}{{magento_dir}} && {{composer}} update --prefer-dist --optimize-autoloader --quiet -vvvv");
    run('cd {{release_path}}{{magento_dir}} && {{composer}} dump-autoload --no-interaction --optimize 2>&1');
});

desc('Compile Magento DI');
task('magento:compile', function () {
    run("{{php}} {{release_path}}{{magento_bin}} setup:di:compile");
});

desc('Deploy assets');
task('magento:deploy:assets', function () {
    if(get('is_production')){
        run("{{php}} {{release_path}}{{magento_bin}} setup:static-content:deploy");
    }else{
        run("{{php}} {{release_path}}{{magento_bin}} setup:static-content:deploy -f");
    }
});

desc('Enable maintenance mode');
task('magento:maintenance:enable', function () {
    run("if [ -d $(echo {{release_path}}/current/bin) ]; then {{php}} {{release_path}}{{magento_bin}} maintenance:enable; fi");
});

desc('Disable maintenance mode');
task('magento:maintenance:disable', function () {
    run("if [ -d $(echo {{release_path}}/current/bin) ]; then {{php}} {{release_path}}{{magento_bin}} maintenance:disable; fi");
});

desc('Upgrade magento database');
task('magento:upgrade:db', function () {
    run("{{php}} {{release_path}}{{magento_bin}} setup:upgrade --keep-generated");
    run("{{php}} {{magerun}} sys:setup:downgrade-versions --root-dir={{release_path}}");
});

desc('Flush Magento Cache');
task('magento:cache:flush', function () {
    run("{{php}} {{release_path}}{{magento_bin}} cache:flush");
});

desc('Enable allow symlink config in Magento Panel');
task('magento:config', function () {
    run("cd {{release_path}} && {{php}} {{magerun}} config:set dev/template/allow_symlink 1");
});

desc('Remove the content of the generated folder');
task('magento:clean:generated', function () {
    run("cd {{release_path}}; rm -rf generated/*");
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
    run("find {{release_path}}{{magento_dir}} -type d -exec chmod 755 {} \;");
    run("find {{release_path}}{{magento_dir}} -type f -exec chmod 644 {} \;");
    run("chmod -R 775 {{release_path}}{{magento_dir}}var/");
    run("chmod -R 775 {{release_path}}{{magento_dir}}generated/");
    run("chmod -R 775 {{release_path}}{{magento_dir}}pub/static/");
    run("chmod +x {{release_path}}{{magento_bin}}");
});

desc('Lock the previous release with the maintenance flag');
task('deploy:previous', function () {
    $releases = get('releases_list');
    if($releases[1]){
        run("{{php}} {{deploy_path}}/releases/{$releases[1]}{{magento_bin}} maintenance:enable");
    }
});

desc('Redis cache flush');
task('redis:flush', function () {
    run("redis-cli -n 0 flushall");
});

desc('OPCache cache flush');
task('opcache:flush', function () {
    run("{{php}} -r 'opcache_reset();'");
});

desc('Magento2 deployment operations');
task('deploy:magento', [
    'magento:setup:permissions',
    'magento:config',
    'magento:deploy:mode:set',
    'magento:deploy:assets',
    'magento:clean:generated',
    'magento:maintenance:enable',
    'magento:upgrade:db',
    'magento:maintenance:disable',
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
    //    'deploy:writable',
    'composer:update',
    'deploy:magento',
    'deploy:symlink',
    'opcache:flush',
    'redis:flush',
    'deploy:unlock',
    'deploy:previous',
    'cleanup',
    'success'
]);

after('deploy:failed', 'deploy:unlock');
// after('deploy:failed', 'deploy:magento');
after('deploy:failed', 'magento:maintenance:disable');

before('rollback', 'rollback:validate');
after('rollback', 'deploy:magento');
after('rollback', 'magento:maintenance:disable');
after('rollback', 'cache:clear');

// composer require deployer/recipes --dev

// ======= Cachetool
// after('deploy:symlink', 'cachetool:clear:opcache');
// or
// after('deploy:symlink', 'cachetool:clear:apc');

// ======= Slack
//before('deploy', 'slack:notify');
//after('success', 'slack:notify:success');
//after('deploy:failed', 'slack:notify:failure');
