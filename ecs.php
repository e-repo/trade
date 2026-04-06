<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Basic\SingleLineEmptyBodyFixer;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withFileExtensions(['php'])

    // Фиксит весь проект
//    ->withPaths([
//        __DIR__ . '/src',
//        __DIR__ . '/migrations',
//        __DIR__ . '/tests',
//    ])

    // add a single rule
    ->withRules([
        DeclareStrictTypesFixer::class,
        OrderedImportsFixer::class,
        NoUnusedImportsFixer::class,
        SingleLineEmptyBodyFixer::class,
    ])

    ->withConfiguredRule(GlobalNamespaceImportFixer::class, [
        'import_classes' => true,
        'import_constants' => true,
        'import_functions' => true,
    ])

    // add sets - group of rules
    ->withPreparedSets(
        psr12: true,
        arrays: true,
        comments: true,
        spaces: true,
        namespaces: true,
    );
