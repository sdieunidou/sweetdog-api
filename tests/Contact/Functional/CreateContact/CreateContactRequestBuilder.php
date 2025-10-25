<?php

declare(strict_types=1);

namespace Tests\Contact\Functional\CreateContact;

class CreateContactRequestBuilder
{
    private array $data = [];

    public function withSubject(string $subject): self
    {
        $this->data['subject'] = $subject;

        return $this;
    }

    public function withMessage(string $message): self
    {
        $this->data['message'] = $message;

        return $this;
    }

    public function withValidData(): self
    {
        return $this->withSubject('Valid Subject')
                    ->withMessage('Valid message with enough characters');
    }

    public function withShortSubject(): self
    {
        return $this->withSubject('AB');
    }

    public function withLongSubject(): self
    {
        return $this->withSubject(str_repeat('A', 101));
    }

    public function withInvalidSubject(): self
    {
        return $this->withSubject('Test@Subject#Invalid');
    }

    public function withShortMessage(): self
    {
        return $this->withMessage('Short');
    }

    public function withLongMessage(): self
    {
        return $this->withMessage(str_repeat('A', 1001));
    }

    public function withInvalidMessage(): self
    {
        return $this->withMessage('Test@Message#Invalid');
    }

    public function withEmptyFields(): self
    {
        return $this->withSubject('')->withMessage('');
    }

    public function withSpecialCharacters(): self
    {
        return $this->withSubject('Test Subject with valid chars 123, 456! 789?')
                    ->withMessage('Test Message with valid special characters: 123, 456! 789? ; "quotes" and (parentheses)');
    }

    public function build(): array
    {
        return $this->data;
    }
}
