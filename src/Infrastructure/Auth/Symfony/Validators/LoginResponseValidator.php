<?php

declare(strict_types=1);

namespace Infrastructure\Auth\Symfony\Validators;

use Symfony\Component\OptionsResolver\OptionsResolver;

final class LoginResponseValidator
{
    public function validate(array $data): void
    {
        $resolver = new OptionsResolver();
        $resolver->setIgnoreUndefined(true);

        $resolver->setRequired([
            'token',
            'refreshToken',
            'tokenExpirationInstant',
        ]);

        $resolver->resolve($data);

        (new UserResponseValidator())->validate($data);
    }
}
