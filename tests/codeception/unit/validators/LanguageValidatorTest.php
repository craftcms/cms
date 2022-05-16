<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\validators;

use craft\helpers\ArrayHelper;
use craft\test\mockclasses\models\ExampleModel;
use craft\test\TestCase;
use craft\validators\LanguageValidator;
use UnitTester;
use yii\base\NotSupportedException;

/**
 * Class LanguageValidatorTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class LanguageValidatorTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @var LanguageValidator
     */
    protected LanguageValidator $languageValidator;

    /**
     * @dataProvider validateValueDataProvider
     * @param array|null $expected
     * @param string $value
     * @param bool $onlySiteLangs
     * @throws NotSupportedException
     */
    public function testValidateValue(?array $expected, string $value, bool $onlySiteLangs = true): void
    {
        $this->tester->mockCraftMethods('i18n', ['getSiteLocaleIds' => ['nl', 'en-US']]);
        $this->languageValidator->onlySiteLanguages = $onlySiteLangs;

        self::assertSame($expected, $this->languageValidator->validateValue($value));
    }

    /**
     * @dataProvider validateAttributeDataProvider
     * @param bool $mustValidate
     * @param string $input
     * @param bool $onlySiteLocalIds
     */
    public function testValidateAttribute(bool $mustValidate, string $input, bool $onlySiteLocalIds = true): void
    {
        $this->tester->mockCraftMethods('i18n', ['getSiteLocaleIds' => ['nl', 'en-US']]);

        $model = new ExampleModel(['exampleParam' => $input]);

        $this->languageValidator->onlySiteLanguages = $onlySiteLocalIds;
        $this->languageValidator->validateAttribute($model, 'exampleParam');

        if (!$mustValidate) {
            self::assertArrayHasKey('exampleParam', $model->getErrors());
        } else {
            self::assertSame([], $model->getErrors());
        }
    }

    /**
     * @return array
     */
    public function validateAttributeDataProvider(): array
    {
        $returnArray = [];

        foreach ($this->validateValueDataProvider() as $item) {
            $mustValidate = true;
            if (!empty($item[0])) {
                $mustValidate = false;
            }

            $lang = $item[1];
            $requireOnlySite = true;
            if (isset($item[2]) && $item[2] === false) {
                $requireOnlySite = false;
            }

            $returnArray[] = [
                $mustValidate,
                $lang,
                $requireOnlySite,
            ];
        }

        return ArrayHelper::merge($returnArray, [
            [true, 'en-US'],
            [true, 'EN-US'],
            [false, 'notalang'],
        ]);
    }

    /**
     * @return array
     */
    public function validateValueDataProvider(): array
    {
        return [
            [['{value} is not a valid site language.', []], 'nolang'],
            [null, 'en-US'],
            [null, 'nl'],
            [['{value} is not a valid site language.', []], 'de'],
            [null, 'de', false],
            [['{value} is not a valid site language.', []], 'nolang', false],
        ];
    }

    /**
     *
     */
    protected function _before(): void
    {
        parent::_before();
        $this->languageValidator = new LanguageValidator();
    }
}
