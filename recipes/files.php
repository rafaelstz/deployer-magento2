<?php

namespace Deployer;

desc('Compile Magento DI');
task('magento:compile', function () {
    if (get('is_production') || get('compile_UAT')) {
        run("cd {{release_path}}{{magento_dir}} && {{php}} {{magento_bin}} setup:di:compile {{verbose}}");
    } else {
        write("Not running the DI Compile for UAT");
    }
});

desc('Deploy assets');
task('magento:deploy:assets', function () {
    if (get('is_production')) {
        run("cd {{release_path}}{{magento_dir}} && {{php}} {{magento_bin}} setup:static-content:deploy {{languages}} {{verbose}}");
    } elseif (get('compile_UAT')) {
        run("cd {{release_path}}{{magento_dir}} && {{php}} {{magento_bin}} setup:static-content:deploy {{languages}} --force {{verbose}}");
    } else {
        write("Not running the Static Content deploy for UAT");
    }
});

desc('Enable maintenance mode');
task('magento:maintenance:enable', function () {
    run("if [ -d $(echo {{release_path}}{{magento_dir}}bin) ]; then cd {{release_path}}{{magento_dir}} && {{php}} {{magento_bin}} maintenance:enable {{verbose}}; fi");
});

desc('Disable maintenance mode');
task('magento:maintenance:disable', function () {
    run("if [ -d $(echo {{release_path}}{{magento_dir}}bin) ]; then cd {{release_path}}{{magento_dir}} && {{php}} {{magento_bin}} maintenance:disable {{verbose}}; fi");
});

desc('Flush Magento Cache');
task('magento:cache:flush', function () {
    run("cd {{release_path}}{{magento_dir}} && {{php}} {{magento_bin}} cache:flush {{verbose}}");
});

desc('Remove the content of the generated folder');
task('magento:clean:generated', function () {
    run("cd {{release_path}}; rm -rf generated/*");
});

desc('Set deploy mode set');
task('magento:deploy:mode:set', function () {
    if (get('is_production')) {
        run("cd {{release_path}}{{magento_dir}} && {{php}} {{magento_bin}} deploy:mode:set production --skip-compilation {{verbose}}");
    } else {
        run("cd {{release_path}}{{magento_dir}} && {{php}} {{magento_bin}} deploy:mode:set developer {{verbose}}");
    }
});

desc('Set right permissions to folders and files');
task('magento:setup:permissions', function () {
    // run("find {{release_path}}{{magento_dir}} -type d -exec chmod 755 {} \;");
    // run("find {{release_path}}{{magento_dir}} -type f -exec chmod 644 {} \;");
    run("chmod -R 755 {{release_path}}");
    run("cd {{release_path}}{{magento_dir}} && chmod -R 775 var");
    run("cd {{release_path}}{{magento_dir}} && chmod -R 775 generated");
    run("cd {{release_path}}{{magento_dir}} && chmod -R 775 pub/static");
    run("cd {{release_path}}{{magento_dir}} && chmod +x {{magento_bin}}");
});