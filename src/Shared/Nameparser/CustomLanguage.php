<?php

declare(strict_types=1);

namespace CraftCms\Cms\Shared\Nameparser;

use TheIconic\NameParser\LanguageInterface;

readonly class CustomLanguage implements LanguageInterface
{
    /** @var array<string, string> */
    private array $suffixes;

    /** @var array<string, string> */
    private array $salutations;

    /** @var array<string, string> */
    private array $lastNamePrefixes;

    /**
     * @param  array<int|string, string>  $suffixes
     * @param  array<int|string, string>  $salutations
     * @param  array<int|string, string>  $lastNamePrefixes
     */
    public function __construct(array $suffixes, array $salutations, array $lastNamePrefixes)
    {
        $this->suffixes = $this->normalizeKeys($suffixes);
        $this->salutations = $this->normalizeKeys($salutations);
        $this->lastNamePrefixes = $this->normalizeKeys($lastNamePrefixes);
    }

    /**
     * @param  array<int|string, string>  $strings
     * @return array<string, string>
     */
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

    /** @return array<string, string> */
    public function getSuffixes(): array
    {
        return $this->suffixes;
    }

    /** @return array<string, string> */
    public function getSalutations(): array
    {
        return $this->salutations;
    }

    /** @return array<string, string> */
    public function getLastnamePrefixes(): array
    {
        return $this->lastNamePrefixes;
    }
}
