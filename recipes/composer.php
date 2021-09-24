<?php

namespace Deployer;

desc('Composer Install');
task('composer:install', function () {
    if (get('is_production')) {
        run("cd {{release_path}}{{magento_dir}} && {{composer}} install --prefer-dist --no-dev --optimize-autoloader {{verbose}}");
    } else {
        run("cd {{release_path}}{{magento_dir}} && {{composer}} install --prefer-dist --optimize-autoloader {{verbose}}");
    }
});

desc('Composer Update');
task('composer:update', function () {
    if (get('is_production')) {
        run("cd {{release_path}}{{magento_dir}} && {{composer}} update --prefer-dist --no-dev --optimize-autoloader {{verbose}}");
    } else {
        run("cd {{release_path}}{{magento_dir}} && {{composer}} update --prefer-dist --optimize-autoloader {{verbose}}");
    }
});

desc('Composer Clear Cache');
task('composer:clearcache', function () {
        run("cd {{release_path}}{{magento_dir}} && {{composer}} clearcache");
        run("cd {{release_path}}{{magento_dir}} && rm -rf var/composer_home/cache/");
        run("cd {{release_path}}{{magento_dir}} && rm -r composer.lock");
});
