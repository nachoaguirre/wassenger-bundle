<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->exclude('config')
;

return (new PhpCsFixer\Config())
    ->setParallelConfig(
        PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect()
    )
    ->setRules([
        '@Symfony' => true,
        'strict_param' => true,
        'declare_strict_types' => true,
        'void_return' => true,
        'no_superfluous_elseif' => true,
        'global_namespace_import' => ['import_classes' => true],
        'concat_space' => ['spacing' => 'one'],
        'array_syntax' => ['syntax' => 'short'],
        'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
;
