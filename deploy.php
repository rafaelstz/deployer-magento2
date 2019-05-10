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
set('release_name', function () {
    return date('YmdHis');
});

# ----- Magento properties -------
set('is_production', 0);
set('compile_UAT', 1);
set('languages', 'en_US');
set('magento_dir', '/');
set('magento_bin', '{{magento_dir}}bin/magento');
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
    return run("{{magerun}} sys:info version --root-dir={{release_path}}");
});

# ----- Magento 2 Tasks -------

require_once __DIR__ . '/recipes/backup.php';
require_once __DIR__ . '/recipes/composer.php';
require_once __DIR__ . '/recipes/deploy.php';
require_once __DIR__ . '/recipes/server.php';

desc('Compile Magento DI');
task('magento:compile', function () {
    if (get('is_production') || get('compile_UAT')) {
        run("{{php}} {{release_path}}{{magento_bin}} setup:di:compile {{verbose}}");
    } else {
        write("Not running the DI Compile for UAT");
    }
});

desc('Deploy assets');
task('magento:deploy:assets', function () {
    if (get('is_production')) {
        run("{{php}} {{release_path}}{{magento_bin}} setup:static-content:deploy {{verbose}}");
    } elseif (get('compile_UAT')) {
        run("{{php}} {{release_path}}{{magento_bin}} setup:static-content:deploy --force {{verbose}}");
    } else {
        write("Not running the Static Content deploy for UAT");
    }
});

desc('Enable maintenance mode');
task('magento:maintenance:enable', function () {
    run("if [ -d $(echo {{release_path}}{{magento_dir}}bin) ]; then {{php}} {{release_path}}{{magento_bin}} maintenance:enable {{verbose}}; fi");
});

desc('Disable maintenance mode');
task('magento:maintenance:disable', function () {
    run("if [ -d $(echo {{release_path}}{{magento_dir}}bin) ]; then {{php}} {{release_path}}{{magento_bin}} maintenance:disable {{verbose}}; fi");
});

desc('Upgrade magento database');
task('magento:upgrade:db', function () {

    $supports = test('(( $(echo "{{magento_version}} 2.1" | awk \'{print ({{magento_version}} > 2.1)}\') ))');

    if (!$supports) {
        invoke('magento:maintenance:enable');
        run("{{php}} {{release_path}}{{magento_bin}} setup:upgrade --keep-generated {{verbose}}");
        run("{{php}} {{magerun}} sys:setup:downgrade-versions --root-dir={{release_path}} {{verbose}}");
        invoke('magento:maintenance:disable');
    } else {
        // Check if need update DB
        $isDbUpdated = test('[ "$({{php}} {{release_path}}{{magento_bin}} setup:db:status --no-ansi -n)" == "All modules are up to date." ]');
        if (!$isDbUpdated) {
            write("All modules are up to date.");
            invoke('magento:maintenance:enable');
            run("{{php}} {{release_path}}{{magento_bin}} setup:upgrade --keep-generated {{verbose}}");
            run("{{php}} {{magerun}} sys:setup:downgrade-versions --root-dir={{release_path}} {{verbose}}");
            invoke('magento:maintenance:disable');
        }else{
            write("All modules are up to date.");
        }
    }

});

desc('Flush Magento Cache');
task('magento:cache:flush', function () {
    run("{{php}} {{release_path}}{{magento_bin}} cache:flush {{verbose}}");
});

desc('Enable allow symlink config in Magento Panel');
task('magento:config', function () {
    if (test("[ -f {{release_path}}{{magento_dir}}app/etc/env.php ]")) {
        run("cd {{release_path}} && {{php}} {{magerun}} config:store:set dev/template/allow_symlink 1 {{verbose}}");
        run("cd {{release_path}} && {{php}} {{release_path}}{{magento_bin}} module:disable Magento_Version {{verbose}}");
        if (get('is_production')) {
            run("cd {{release_path}} && {{php}} {{magerun}} config:store:set design/search_engine_robots/default_robots INDEX,FOLLOW {{verbose}}");
        } else {
            run("cd {{release_path}} && {{php}} {{magerun}} config:store:set design/search_engine_robots/default_robots NOINDEX,NOFOLLOW {{verbose}}");
        }
    }
});

desc('Remove the content of the generated folder');
task('magento:clean:generated', function () {
    run("cd {{release_path}}; rm -rf generated/*");
});

desc('Set deploy mode set');
task('magento:deploy:mode:set', function () {
    if (get('is_production')) {
        run("{{php}} -f {{release_path}}{{magento_bin}} deploy:mode:set production --skip-compilation {{verbose}}");
    } else {
        run("{{php}} -f {{release_path}}{{magento_bin}} deploy:mode:set developer {{verbose}}");
    }
});

desc('Set right permissions to folders and files');
task('magento:setup:permissions', function () {
    // run("find {{release_path}}{{magento_dir}} -type d -exec chmod 755 {} \;");
    // run("find {{release_path}}{{magento_dir}} -type f -exec chmod 644 {} \;");
    run("chmod -R 755 {{release_path}}");
    run("chmod -R 777 {{release_path}}{{magento_dir}}var");
    run("chmod -R 777 {{release_path}}{{magento_dir}}generated");
    run("chmod -R 777 {{release_path}}{{magento_dir}}pub/static");
    run("chmod +x {{release_path}}{{magento_bin}}");
});

// Magento 2 Logs

desc('Check Magento system log');
task('magento:log:system', function () {
    write(run("tail -n 50 {{deploy_path}}/shared/var/log/system.log"));
});

desc('Check Magento debug log');
task('magento:log:debug', function () {
    write(run("tail -n 50 {{deploy_path}}/shared/var/log/debug.log"));
});

desc('Check Magento exception log');
task('magento:log:exception', function () {
    write(run("tail -n 50 {{deploy_path}}/shared/var/log/exception.log"));
});

desc('Clear the Magento logs');
task('magento:log:clear', function () {
    run("rm -rf {{deploy_path}}/shared/var/log/*.log");
});

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
    //    'deploy:writable',
    'composer:update',
    'deploy:magento',
    'deploy:symlink',
    'deploy:unlock',
    'deploy:previous',
    'cleanup',
    'success'
]);

after('deploy:failed', 'deploy:unlock');
// after('deploy:failed', 'magento:maintenance:disable');

// before('rollback', 'rollback:validate');
after('rollback', 'deploy:magento');
after('rollback', 'magento:maintenance:disable');
after('rollback', 'magento:cache:flush');

// composer require deployer/recipes --dev

// ======= Cachetool
// require 'vendor/deployer/recipes/recipe/cachetool.php';

// after('deploy:symlink', 'cachetool:clear:opcache');
// or
// after('deploy:symlink', 'cachetool:clear:apc');

// ======= Slack
// require 'vendor/deployer/recipes/recipe/slack.php';

//set('slack_webhook', 'https://hooks.slack.com/services/YOUR/REGISTER/HERE');
//before('deploy', 'slack:notify');
//after('success', 'slack:notify:success');
//after('deploy:failed', 'slack:notify:failure');
