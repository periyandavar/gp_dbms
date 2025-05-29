<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__ . '/src') 
    ->in(__DIR__ . '/tests') 
    ->name('*.php');

return (new Config())
    ->setRules([
        '@PSR2' => true, // Use PSR-2 coding style
        '@PSR12' => true, // Use PSR-2 coding style
        'array_syntax' => ['syntax' => 'short'], // Use short array syntax
        'no_unused_imports' => true,
       'binary_operator_spaces' => [
            'default' => 'single_space', // Default spacing for binary operators
        ],
        'blank_line_after_namespace' => true, // Ensure a blank line after namespace declaration
        'blank_line_before_statement' => [ // Ensure a blank line before specific statements
            'statements' => ['return'],
        ],
        'braces' => [ // Control brace placement
            'position_after_control_structures' => 'next',
            'position_after_functions_and_oop_constructs' => 'next',
            'position_after_anonymous_constructs' => 'same',
            'allow_single_line_anonymous_class_with_empty_body' => true,
            'allow_single_line_closure' => true,
        ],
        'cast_spaces' => true, // Ensure spaces around casts
        'class_definition' => [ // Control class definition formatting
            'multi_line_extends_each_single_line' => true,
            'single_line' => true,
        ],
        'concat_space' => ['spacing' => 'one'], // Ensure one space around concatenation
        'declare_equal_normalize' => true, // Normalize declare statements
        'function_declaration' => [ // Control function declaration formatting
            'closure_function_spacing' => 'none',
        ],
        'indentation_type' => true, // Ensure consistent indentation
        'lowercase_keywords' => true, // Convert keywords to lowercase
        'method_argument_space' => [ // Control method argument spacing
            'on_multiline' => 'ignore',
            'keep_multiple_spaces_after_comma' => false,
        ],
        'no_empty_statement' => true, // Remove empty statements
        'no_extra_blank_lines' => [ // Control blank lines
            'tokens' => ['extra', 'throw', 'use', 'return', 'case', 'default'],
        ],
        'no_trailing_whitespace' => true, // Remove trailing whitespace
        'no_whitespace_before_comma_in_array' => true, // Remove whitespace before commas in arrays
        'ordered_imports' => [ // Control import ordering
            'sort_algorithm' => 'alpha',
        ],
        'phpdoc_align' => true, // Align PHPDoc tags
        'phpdoc_scalar' => true, // Use scalar types in PHPDoc
        'return_type_declaration' => true, // Enforce return type declarations
        'single_quote' => true, // Use single quotes for strings
        'ternary_to_null_coalescing' => true, // Convert ternary to null coalescing operator
        'trim_array_spaces' => true, // Trim spaces in array declarations
    ])
    ->setFinder($finder);