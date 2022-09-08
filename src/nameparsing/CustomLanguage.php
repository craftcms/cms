<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\nameparsing;

use TheIconic\NameParser\LanguageInterface;

/**
 * Custom language for the name parser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 */
class CustomLanguage implements LanguageInterface
{
    private array $suffixes;
    private array $salutations;
    private array $lastNamePrefixes;

    /**
     * Constructor
     *
     * @param string[] $suffixes
     * @param string[] $salutations
     * @param string[] $lastNamePrefixes
     */
    public function __construct(array $suffixes, array $salutations, array $lastNamePrefixes)
    {
        $this->suffixes = array_combine(array_map('mb_strtolower', $suffixes), $suffixes);
        $this->salutations = array_combine(array_map('mb_strtolower', $salutations), $salutations);
        $this->lastNamePrefixes = array_combine(array_map('mb_strtolower', $lastNamePrefixes), $lastNamePrefixes);
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
