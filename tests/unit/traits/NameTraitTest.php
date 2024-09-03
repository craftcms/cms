<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\traits;

use Craft;
use craft\base\NameTrait;
use craft\test\TestCase;

/**
 * Class NameTraitTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 */
class NameTraitTest extends TestCase
{
    private mixed $_class;

    /**
     * @param array $config
     * @param array $expected
     * @param array $suffixes
     * @param array $salutations
     * @param array $lastNamePrefixes
     * @dataProvider namesDataProvider
     */
    public function testNames(array $config, array $expected, array $suffixes = [], array $salutations = [], array $lastNamePrefixes = []): void
    {
        foreach ($config as $attr => $val) {
            $this->_class->$attr = $val;
        }

        Craft::$app->getConfig()->getGeneral()
            ->extraNameSuffixes($suffixes)
            ->extraNameSalutations($salutations)
            ->extraLastNamePrefixes($lastNamePrefixes);

        $this->_class->save();

        foreach ($expected as $attr => $val) {
            self::assertSame($val, $this->_class->$attr);
        }
    }

    /**
     * @return array
     */
    public static function namesDataProvider(): array
    {
        return [
            'onlyFullName' => [
                ['fullName' => 'Dr. Emmett Brown'],
                ['fullName' => 'Dr. Emmett Brown', 'firstName' => 'Emmett', 'lastName' => 'Brown'],
            ],
            'onlyFirstName' => [
                ['fullName' => 'Emmett'],
                ['fullName' => 'Emmett', 'firstName' => 'Emmett', 'lastName' => null],
            ],
            'lastNamePrefix' => [
                ['fullName' => 'Emmett von Brown'],
                ['fullName' => 'Emmett von Brown', 'firstName' => 'Emmett', 'lastName' => 'von Brown'],
            ],
            'lastNamePrefixWithWordingChange' => [
                ['fullName' => 'Emmett von Brown'],
                ['fullName' => 'Emmett von Brown', 'firstName' => 'Emmett', 'lastName' => 'Vonilla Brown'],
                [],
                [],
                ['von' => 'Vonilla'],
            ],
            'joinedFirstAndLast' => [
                ['firstName' => 'Emmett', 'lastName' => 'Brown'],
                ['fullName' => 'Emmett Brown', 'firstName' => 'Emmett', 'lastName' => 'Brown'],
            ],

            'expectedWrongLastName' => [
                ['fullName' => 'Emmett Prefix Brown'],
                ['fullName' => 'Emmett Prefix Brown', 'firstName' => 'Emmett', 'lastName' => 'Brown'],
                // The following test solves this case
            ],
            'suffixFromConfig' => [
                ['fullName' => 'Emmett Brown Suffix'],
                ['fullName' => 'Emmett Brown Suffix', 'firstName' => 'Emmett', 'lastName' => 'Brown'],
                ['Suffix'],
            ],
            'salutationFromConfig' => [
                ['fullName' => 'Salutation Emmett Brown'],
                ['fullName' => 'Salutation Emmett Brown', 'firstName' => 'Emmett', 'lastName' => 'Brown'],
                [],
                ['Salutation'],
            ],
            'prefixFromConfig' => [
                ['fullName' => 'Emmett Prefix Brown'],
                ['fullName' => 'Emmett Prefix Brown', 'firstName' => 'Emmett', 'lastName' => 'Prefix Brown'],
                [],
                [],
                ['Prefix'],
            ],
            // https://github.com/craftcms/cms/issues/14723
            'maintainCasing1' => [
                ['fullName' => 'Eddie Van Halen'],
                ['fullName' => 'Eddie Van Halen', 'firstName' => 'Eddie', 'lastName' => 'Van Halen'],
            ],
            'maintainCasing2' => [
                ['fullName' => 'Vincent van Gogh'],
                ['fullName' => 'Vincent van Gogh', 'firstName' => 'Vincent', 'lastName' => 'van Gogh'],
            ],
        ];
    }

    protected function _before(): void
    {
        $this->_class = new class() {
            use NameTrait;

            public function save(): void
            {
                $this->prepareNamesForSave();
            }
        };
    }

    protected function _after(): void
    {
        Craft::$app->getConfig()->getGeneral()
            ->extraNameSuffixes([])
            ->extraNameSalutations([])
            ->extraLastNamePrefixes([]);
    }
}
