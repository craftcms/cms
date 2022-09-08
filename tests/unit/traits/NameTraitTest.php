<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\traits;

use craft\base\NameParserLanguage;
use craft\base\NameTrait;
use craft\events\DefineLastNamePrefixesEvent;
use craft\events\DefineNameSalutationsEvent;
use craft\events\DefineNameSuffixesEvent;
use craft\test\TestCase;
use yii\base\Event;

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
     * @return void
     * @dataProvider namesDataProvider
     */
    public function testNames(array $config, array $expected, mixed $eventFn = null): void
    {
        foreach ($config as $attr => $val) {
            $this->_class->$attr = $val;
        }

        if ($eventFn) {
            $eventFn();
        }

        $this->_class->save();

        foreach ($expected as $attr => $val) {
            $this->assertSame($val, $this->_class->$attr);
        }
    }

    /**
     * @return array
     */
    public function namesDataProvider(): array
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
            'joinedFirstAndLast' => [
                ['firstName' => 'Emmett', 'lastName' => 'Brown'],
                ['fullName' => 'Emmett Brown', 'firstName' => 'Emmett', 'lastName' => 'Brown'],
            ],

            'expectedWrongLastName' => [
                ['fullName' => 'Emmett Prefix Brown'],
                ['fullName' => 'Emmett Prefix Brown', 'firstName' => 'Emmett', 'lastName' => 'Brown'],
                // The following test solves this case
            ],
            'prefixFromEvent' => [
                ['fullName' => 'Emmett Prefix Brown'],
                ['fullName' => 'Emmett Prefix Brown', 'firstName' => 'Emmett', 'lastName' => 'Prefix Brown'],
                fn() => Event::on(
                    NameParserLanguage::class,
                    NameParserLanguage::EVENT_DEFINE_LASTNAME_PREFIXES,
                    static fn(DefineLastNamePrefixesEvent$event) => $event->lastNamePrefixes['prefix'] = 'Prefix',
                ),
            ],
            'salutationFromEvent' => [
                ['fullName' => 'Salutation Emmett Brown'],
                ['fullName' => 'Salutation Emmett Brown', 'firstName' => 'Emmett', 'lastName' => 'Brown'],
                fn() => Event::on(
                    NameParserLanguage::class,
                    NameParserLanguage::EVENT_DEFINE_SALUTATIONS,
                    static fn(DefineNameSalutationsEvent $event) => $event->salutations['salutation'] = 'Salutation',
                ),
            ],
            'suffixFromEvent' => [
                ['fullName' => 'Emmett Brown Suffix'],
                ['fullName' => 'Emmett Brown Suffix', 'firstName' => 'Emmett', 'lastName' => 'Brown'],
                fn() => Event::on(
                    NameParserLanguage::class,
                    NameParserLanguage::EVENT_DEFINE_SUFFIXES,
                    static fn(DefineNameSuffixesEvent $event) => $event->suffixes['suffix'] = 'Suffix',
                ),
            ],
        ];
    }

    public function _before(): void
    {
        $this->_class = new class() {
            use NameTrait;

            public function save(): void
            {
                $this->prepareNamesForSave();
            }
        };
    }
}
