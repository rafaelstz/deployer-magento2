<?php

namespace Deployer;

desc('Create a database inside the var/ folder');
task('magento:db:backup', function () {
    $remoteDump = "var/";
    run("cd {{release_path}}{{magento_dir}} && {{magerun}} db:dump -s @stripped -c gz $(date +%Y%m%d%H%M%S) {{magerun_params}} {{verbose}}");
    run("cd {{release_path}}{{magento_dir}} && mv *.sql.gz ". $remoteDump);
});

desc('Download a database dump');
task('magento:db:download', function () {

    $remoteDump = "var/";
    $localDump = runLocally('pwd');
    $timeout = 300;
    $config = [
        'timeout' => $timeout
    ];

    write('Creating the database dump...');
    run("cd {{release_path}}{{magento_dir}} && {{magerun}} db:dump -s @stripped -c gz bkp {{magerun_params}} {{verbose}}");
    run("cd {{release_path}}{{magento_dir}} && mv bkp.sql.gz ". $remoteDump);

    write('Downloading the SQL file...');
    download('{{release_path}}{{magento_dir}}'.$remoteDump.'bkp.sql.gz', $localDump.'/', $config);
    runLocally('mv bkp.sql.gz deployer_database_backup.sql.gz');
    run('rm {{release_path}}{{magento_dir}}'.$remoteDump.'bkp.sql.gz');

    write('Your database dump is called: deployer_database_backup.sql.gz');
});

desc('Create a backup of the media folder inside of var/ folder');
task('magento:media:backup', function () {
    $remoteDump = "var/";
    run("cd {{release_path}}{{magento_dir}} && {{magerun}} media:dump --strip media-$(date +%Y%m%d%H%M%S).zip {{magerun_params}} {{verbose}}");
    run("cd {{release_path}}{{magento_dir}} && mv media-* ". $remoteDump);
});

desc('Download a copy of the media folder in a ZIP file');
task('magento:media:download', function () {

    $remoteDump = "var/";
    $localDump = runLocally('pwd');
    $timeout = 300;
    $config = [
        'timeout' => $timeout
    ];

    write('Creating a Media ZIP backup...');
    run("cd {{release_path}}{{magento_dir}} && {{magerun}} media:dump --strip media_dump.zip {{magerun_params}} {{verbose}}");
    run("cd {{release_path}}{{magento_dir}} && mv media_dump.zip ". $remoteDump);

    write('Downloading the Media ZIP file...');
    download('{{release_path}}{{magento_dir}}'.$remoteDump.'media_dump.zip', $localDump.'/', $config);
    runLocally('mv media_dump.zip deployer_media_dump.zip');
    run('rm {{release_path}}{{magento_dir}}'.$remoteDump.'media_dump.zip');

    write('Your Media ZIP file is called: deployer_media_dump.zip');
});