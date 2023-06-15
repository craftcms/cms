<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Craft;
use craft\nameparsing\CustomLanguage;
use TheIconic\NameParser\Language\English;
use TheIconic\NameParser\Language\German;
use TheIconic\NameParser\Parser as NameParser;

/**
 * NameTrait implements the common properties for entities with full/first/last names.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
trait NameTrait
{
    /**
     * @var string|null Full name
     * @since 4.0.0
     */
    public ?string $fullName = null;

    /**
     * @var string|null First name
     */
    public ?string $firstName = null;

    /**
     * @var string|null Last name
     */
    public ?string $lastName = null;

    /**
     * Populate fullName, firstName and lastName attributes from the request
     *
     * @return void
     * @since 4.5.0
     */
    public function populateNameAttributes(): void
    {
        $request = Craft::$app->getRequest();
        $editName = (bool)$request->getBodyParam('editName', false);

        /** @var object|NameTrait $this */
        $fullName = $request->getBodyParam('fullName');

        if ($fullName !== null && !$editName) {
            $this->fullName = $fullName ?: null;
        } else {
            // Still check for firstName/lastName in case a front-end form is still posting them
            $firstName = $request->getBodyParam('firstName');
            $lastName = $request->getBodyParam('lastName');

            if ($firstName !== null || $lastName !== null) {
                $this->fullName = null;
                $this->firstName = $firstName ?? $this->firstName;
                $this->lastName = $lastName ?? $this->lastName;
            }
        }
    }

    /**
     * Get parsed first and last names
     *
     * @return array
     * @since 4.5.0
     */
    public function getParsedName(): array
    {
        $firstName = null;
        $lastName = null;

        if ($this->fullName !== null) {
            $languages = $this->_prepNameParser();
            $name = (new NameParser($languages))->parse($this->fullName);
            $firstName = $name->getFirstname() ?: null;
            $lastName = $name->getLastname() ?: null;
        }

        return compact('firstName', 'lastName');
    }
    
    /**
     * Normalizes the name properties.
     */
    protected function normalizeNames(): void
    {
        $properties = ['fullName', 'firstName', 'lastName'];

        foreach ($properties as $property) {
            if (isset($this->$property) && trim($this->$property) === '') {
                $this->$property = null;
            }
        }
    }

    /**
     * Parses `fullName` if set, or sets it based on `firstName` and `lastName`.
     */
    protected function prepareNamesForSave(): void
    {
        if ($this->fullName !== null) {
            $parsedName = $this->getParsedName();
            $this->firstName = $parsedName['firstName'];
            $this->lastName = $parsedName['lastName'];
        } elseif ($this->firstName !== null || $this->lastName !== null) {
            $this->fullName = trim("$this->firstName $this->lastName") ?: null;
        }
    }

    /**
     * Get language settings for the name parser
     *
     * @return array
     * @since 4.5.0
     */
    private function _prepNameParser(): array
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        return [
            // Load our custom language file first so config settings can override the defaults
            new CustomLanguage(
                $generalConfig->extraNameSuffixes,
                $generalConfig->extraNameSalutations,
                $generalConfig->extraLastNamePrefixes,
            ),
            new English(),
            new German(),
        ];
    }
}
