<?php

declare(strict_types=1);

namespace Tests\Contact\Functional\CreateContact;

class CreateContactValidationDataProvider
{
    public static function invalidSubjects(): array
    {
        return [
            'empty subject' => ['', 'Le sujet ne peut pas être vide'],
            'too short' => ['AB', 'Le sujet doit contenir au moins 3 caractères'],
            'too long' => [str_repeat('A', 101), 'Le sujet ne peut pas dépasser 100 caractères'],
            'invalid chars' => ['Test@Subject#Invalid', 'Le sujet contient des caractères non autorisés'],
        ];
    }

    public static function invalidMessages(): array
    {
        return [
            'empty message' => ['', 'Le message ne peut pas être vide'],
            'too short' => ['Short', 'Le message doit contenir au moins 10 caractères'],
            'too long' => [str_repeat('A', 1001), 'Le message ne peut pas dépasser 1000 caractères'],
            'invalid chars' => ['Test@Message#Invalid', 'Le message contient des caractères non autorisés'],
        ];
    }

    public static function validSubjects(): array
    {
        return [
            'minimum length' => ['ABC'],
            'maximum length' => [str_repeat('A', 100)],
            'with numbers' => ['Test Subject 123'],
            'with special chars' => ['Test Subject with valid chars 123, 456! 789?'],
            'with spaces' => ['Test Subject With Spaces'],
        ];
    }

    public static function validMessages(): array
    {
        return [
            'minimum length' => ['1234567890'],
            'maximum length' => [str_repeat('A', 1000)],
            'with numbers' => ['Test Message 123'],
            'with special chars' => ['Test Message with valid special characters: 123, 456! 789? ; "quotes" and (parentheses)'],
            'with spaces' => ['Test Message With Spaces'],
        ];
    }

    public static function boundaryValues(): array
    {
        return [
            'subject exact minimum' => ['ABC', 'Valid message with enough characters'],
            'subject exact maximum' => [str_repeat('A', 100), 'Valid message with enough characters'],
            'message exact minimum' => ['Valid Subject', '1234567890'],
            'message exact maximum' => ['Valid Subject', str_repeat('A', 1000)],
        ];
    }

    public static function multipleValidationErrors(): array
    {
        return [
            'both fields empty' => [
                ['subject' => '', 'message' => ''],
                ['Le sujet ne peut pas être vide', 'Le message ne peut pas être vide'],
            ],
            'both fields too short' => [
                ['subject' => 'AB', 'message' => 'Short'],
                ['Le sujet doit contenir au moins 3 caractères', 'Le message doit contenir au moins 10 caractères'],
            ],
            'both fields too long' => [
                ['subject' => str_repeat('A', 101), 'message' => str_repeat('A', 1001)],
                ['Le sujet ne peut pas dépasser 100 caractères', 'Le message ne peut pas dépasser 1000 caractères'],
            ],
        ];
    }
}
