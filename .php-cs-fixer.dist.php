<?php

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__ . '/packages', __DIR__ . '/tests'])
    ->notPath('vendor');

return (new PhpCsFixer\Config())
    ->setCacheFile('var/.php-cs-fixer.cache')
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PSR12' => true,
        '@PSR12:risky' => true,
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        '@PHP82Migration' => true,
        'strict_param' => true,
        'strict_comparison' => true,
        'declare_strict_types' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha', 'imports_order' => ['class', 'function', 'const']],
        'single_line_empty_body' => false,
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => false, 'remove_inheritdoc' => true],
        'phpdoc_order' => true,
        'phpdoc_separation' => true,
        'phpdoc_align' => ['align' => 'vertical'],
        'phpdoc_trim' => true,
        'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'alpha'],
        'concat_space' => ['spacing' => 'one'],
        'class_definition' => ['single_line' => true],
        'increment_style' => ['style' => 'post'],
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        'blank_line_before_statement' => ['statements' => ['return', 'throw', 'try']],
        'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
        'native_function_invocation' => ['include' => ['@compiler_optimized'], 'scope' => 'namespaced', 'strict' => true],
        'global_namespace_import' => ['import_classes' => true, 'import_constants' => true, 'import_functions' => true],
        'nullable_type_declaration_for_default_null_value' => true,
        'void_return' => true,
        'phpdoc_to_comment' => false,
        'trailing_comma_in_multiline' => ['elements' => ['arguments', 'arrays', 'match', 'parameters']],
        'php_unit_test_class_requires_covers' => false,
    ])
    ->setFinder($finder);
