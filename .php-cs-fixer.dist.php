<?php

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/tests']);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2.0'                            => true,
        '@PHP82Migration'                       => true,
        'declare_strict_types'                  => true,
        'ordered_imports'                       => ['sort_algorithm' => 'alpha'],
        'no_unused_imports'                     => true,
        'global_namespace_import'               => ['import_classes' => false],
    ])
    ->setFinder($finder);
