<?php

namespace Deployer;

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