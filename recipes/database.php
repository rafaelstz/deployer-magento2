<?php

namespace Deployer;

desc('Enable allow symlink config in Magento Panel');
task('magento:config', function () {
    if (test("[ -f {{release_path}}{{magento_dir}}app/etc/env.php ]")) {
        run("cd {{release_path}}{{magento_dir}} && {{php}} {{magerun}} cache:enable {{magerun_params}} {{verbose}}");
        run("cd {{release_path}}{{magento_dir}} && {{php}} {{magerun}} config:store:set dev/template/allow_symlink 1 {{magerun_params}} {{verbose}}");
        if (get('is_production')) {
            run("cd {{release_path}}{{magento_dir}} && {{php}} {{magerun}} config:store:set design/search_engine_robots/default_robots INDEX,FOLLOW {{magerun_params}} {{verbose}}");
        } else {
            run("cd {{release_path}}{{magento_dir}} && {{php}} {{magerun}} config:store:set design/search_engine_robots/default_robots NOINDEX,NOFOLLOW {{magerun_params}} {{verbose}}");
        }
    }
});

desc('Upgrade magento database');
task('magento:upgrade:db', function () {

    $supports = test('(( $(echo "{{magento_version}} 2.1" | awk \'{print ({{magento_version}} > 2.1)}\') ))');

    if (!$supports) {
        invoke('magento:maintenance:enable');
        run("cd {{release_path}}{{magento_dir}} && {{php}} {{magerun}} module:disable Magento_Version {{magerun_params}} {{verbose}}");
        run("cd {{release_path}}{{magento_dir}} && {{php}} {{magento_bin}} setup:upgrade --keep-generated {{magerun_params}} {{verbose}}");
        run("cd {{release_path}}{{magento_dir}} && {{php}} {{magerun}} sys:setup:downgrade-versions {{magerun_params}} {{verbose}}");
        invoke('magento:maintenance:disable');
    } else {
        // Check if need update DB
        $isDbUpdated = test('[ "$({{php}} {{release_path}}{{magento_bin}} setup:db:status --no-ansi -n)" == "All modules are up to date." ]');
        if (!$isDbUpdated) {
            write("All modules are up to date.");
            invoke('magento:maintenance:enable');
            run("cd {{release_path}}{{magento_dir}} && {{php}} {{magerun}} module:disable Magento_Version {{magerun_params}} {{verbose}}");
            run("cd {{release_path}}{{magento_dir}} && {{php}} {{magento_bin}} setup:upgrade --keep-generated {{magerun_params}} {{verbose}}");
//            run("cd {{release_path}}{{magento_dir}} && {{php}} {{magerun}} sys:setup:downgrade-versions  {{magerun_params}} {{verbose}}");
            invoke('magento:maintenance:disable');
        }else{
            write("All modules are up to date.");
        }
    }

});