<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\validators;

use Codeception\Test\Unit;
use craft\helpers\ArrayHelper;
use craft\test\mockclasses\models\ExampleModel;
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
class LanguageValidatorTest extends Unit
{
    // Public Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var LanguageValidator
     */
    protected $languageValidator;

    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     * @dataProvider validateValueDataProvider
     *
     * @param $result
     * @param $input
     * @param bool $onlySiteLangs
     * @throws NotSupportedException
     */
    public function testValidateValue($result, $input, $onlySiteLangs = true)
    {
        $this->tester->mockCraftMethods('i18n', ['getSiteLocaleIds' => ['nl', 'en-US']]);
        $this->languageValidator->onlySiteLanguages = $onlySiteLangs;
        $validated = $this->languageValidator->validateValue($input);

        $this->assertSame($result, $validated);
    }

    /**
     * @dataProvider validateAttributeDataProvider
     *
     * @param $mustValidate
     * @param $input
     * @param bool $onlySiteLocalIds
     */
    public function testValidateAtrribute($mustValidate, $input, $onlySiteLocalIds = true)
    {
        $this->tester->mockCraftMethods('i18n', ['getSiteLocaleIds' => ['nl', 'en-US']]);

        $model = new ExampleModel(['exampleParam' => $input]);

        $this->languageValidator->onlySiteLanguages = $onlySiteLocalIds;
        $this->languageValidator->validateAttribute($model, 'exampleParam');

        if (!$mustValidate) {
            $this->assertArrayHasKey('exampleParam', $model->getErrors());
        } else {
            $this->assertSame([], $model->getErrors());
        }
    }

    // Data Providers
    // =========================================================================

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
                $requireOnlySite
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
            [['{value} is not a valid site language.', []], 'nolang', false]
        ];
    }

    // Protected Methods
    // =========================================================================

    /**
     *
     */
    protected function _before()
    {
        parent::_before();
        $this->languageValidator = new LanguageValidator();
    }
}
