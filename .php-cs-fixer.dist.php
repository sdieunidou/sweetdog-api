<?php

$rules = [
    '@Symfony' => true,
    '@PhpCsFixer' => true,
    'declare_strict_types' => true,
    'final_class' => false, // tu peux forcer final en Domain avec Arkitect
    'ordered_imports' => true,
    'no_unused_imports' => true,
];

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setFinder(
        PhpCsFixer\Finder::create()->in([__DIR__.'/src', __DIR__.'/tests'])
    );