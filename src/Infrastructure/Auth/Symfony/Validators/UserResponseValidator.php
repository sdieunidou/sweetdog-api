<?php

declare(strict_types=1);

namespace Infrastructure\Auth\Symfony\Validators;

use Infrastructure\Shared\Validators\AbstractOptionsResolverValidator;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserResponseValidator extends AbstractOptionsResolverValidator
{
    protected function configureResolver(OptionsResolver $resolver): void
    {
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
    }
}
