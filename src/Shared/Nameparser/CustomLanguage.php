<?php

declare(strict_types=1);

namespace CraftCms\Cms\Shared\Nameparser;

use TheIconic\NameParser\LanguageInterface;

final readonly class CustomLanguage implements LanguageInterface
{
    private array $suffixes;

    private array $salutations;

    private array $lastNamePrefixes;

    /**
     * @param  string[]  $suffixes
     * @param  string[]  $salutations
     * @param  string[]  $lastNamePrefixes
     */
    public function __construct(array $suffixes, array $salutations, array $lastNamePrefixes)
    {
        $this->suffixes = $this->normalizeKeys($suffixes);
        $this->salutations = $this->normalizeKeys($salutations);
        $this->lastNamePrefixes = $this->normalizeKeys($lastNamePrefixes);
    }

    private function normalizeKeys(array $strings): array
    {
        $normalized = [];
        foreach ($strings as $key => $string) {
            if (is_int($key)) {
                $key = $string;
            }
            $normalized[mb_strtolower((string) $key)] = $string;
        }

        return $normalized;
    }

    public function getSuffixes(): array
    {
        return $this->suffixes;
    }

    public function getSalutations(): array
    {
        return $this->salutations;
    }

    public function getLastnamePrefixes(): array
    {
        return $this->lastNamePrefixes;
    }
}
