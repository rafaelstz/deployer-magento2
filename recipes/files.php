<?php

namespace Deployer;

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

desc('Flush Magento Cache');
task('magento:cache:flush', function () {
    run("{{php}} {{release_path}}{{magento_bin}} cache:flush {{verbose}}");
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
    run("chmod -R 775 {{release_path}}{{magento_dir}}var");
    run("chmod -R 775 {{release_path}}{{magento_dir}}generated");
    run("chmod -R 775 {{release_path}}{{magento_dir}}pub/static");
    run("chmod +x {{release_path}}{{magento_bin}}");
});