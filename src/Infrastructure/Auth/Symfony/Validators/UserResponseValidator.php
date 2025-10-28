<?php

declare(strict_types=1);

namespace Infrastructure\Auth\Symfony\Validators;

use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserResponseValidator
{
    public function validate(array $data): void
    {
        $resolver = new OptionsResolver();
        $resolver->setIgnoreUndefined(true);

        $resolver->setRequired([
            'user',
        ]);

        $resolver->setAllowedTypes('user', 'array');
        $resolver->setNormalizer('user', function ($options, $value) {
            $userResolver = new OptionsResolver();
            $userResolver->setIgnoreUndefined(true);

            $userResolver->setRequired([
                'active',
                'email',
                'preferredLanguages',
            ]);

            $userResolver->setDefined([
                'birthDate',
                'lastName',
                'firstName',
            ]);

            $userResolver->setDefaults([
                'birthDate' => null,
                'lastName' => null,
                'firstName' => null,
            ]);

            return $userResolver->resolve($value);
        });

        $resolver->resolve($data);
    }
}
