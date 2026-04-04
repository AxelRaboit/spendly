<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([__DIR__.'/../../app'])
    ->withSkip([__DIR__.'/../../app/Http/Middleware'])
    ->withImportNames(removeUnusedImports: true)
    ->withPhpSets(php83: true)
    ->withPreparedSets(
        codeQuality: true,
        codingStyle: true,
        earlyReturn: true,
    )
    ->withDeadCodeLevel(20)
    ->withCache(__DIR__.'/../../storage/rector', FileCacheStorage::class);
