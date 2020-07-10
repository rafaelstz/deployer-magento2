<?php

namespace Deployer;

desc('Lock the previous release with the maintenance flag');
task('deploy:previous', function () {
    $releases = get('releases_list');
    if ($releases[1]) {
        run("{{php}} {{deploy_path}}/releases/{$releases[1]}{{magento_dir}}{{magento_bin}} maintenance:enable {{verbose}}");
    }
});
