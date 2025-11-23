<?php

declare(strict_types=1);

namespace Infrastructure\Auth\Symfony\Validators;

use Infrastructure\Shared\Validators\AbstractOptionsResolverValidator;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class LoginResponseValidator extends AbstractOptionsResolverValidator
{
    public function __construct(
        private readonly UserResponseValidator $userResponseValidator,
    ) {}

    public function validate(array $data, bool $ignoreUndefined = true): array
    {
        $validatedData = parent::validate($data, $ignoreUndefined);

        $this->userResponseValidator->validate($data, $ignoreUndefined);

        return $validatedData;
    }

    protected function configureResolver(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            'token',
            'refreshToken',
            'tokenExpirationInstant',
        ]);
    }
}
