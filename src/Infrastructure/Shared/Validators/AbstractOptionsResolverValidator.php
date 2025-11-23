<?php

declare(strict_types=1);

namespace Infrastructure\Shared\Validators;

use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractOptionsResolverValidator
{
    public function validate(array $data, bool $ignoreUndefined = true): array
    {
        $resolver = new OptionsResolver();
        $resolver->setIgnoreUndefined($ignoreUndefined);

        $this->configureResolver($resolver);

        try {
            return $resolver->resolve($data);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException(sprintf('Validation failed: %s', $e->getMessage()), 0, $e);
        }
    }

    abstract protected function configureResolver(OptionsResolver $resolver): void;
}
