<?php

namespace Deployer;

// OPCache and Redis

desc('Redis cache flush');
task('redis:flush', function () {
    run("redis-cli -n 0 flushall");
});

desc('OPCache cache flush');
task('opcache:flush', function () {
    run("{{php}} -r 'opcache_reset();'");
});
