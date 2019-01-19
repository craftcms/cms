<?php
/**
 * @copyright Copyright (c) Global Network Group
 */


namespace craftunit\validators;


use Codeception\Test\Unit;
use craft\validators\LanguageValidator;
use craftunit\fixtures\SitesFixture;

/**
 * Class LanguageValidatorTest
 *
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 */
class LanguageValidatorTest extends Unit
{
    /**
     * @var \UnitTester $tester
     */
    protected $tester;

    /**
     * @var LanguageValidator $languageValidator
     */
    protected $languageValidator;

    public function _before()
    {
        parent::_before();
        $this->languageValidator = new LanguageValidator();
    }

    /**
     * @throws \yii\base\NotSupportedException
     * @dataProvider validateValueData
     */
    public function testValidateValue($result, $input, $onlySiteLangs = true)
    {
        $this->tester->mockCraftMethods('i18n', ['getSiteLocaleIds' => ['nl', 'en-US']]);
        $this->languageValidator->onlySiteLanguages = $onlySiteLangs;
        $validated = $this->languageValidator->validateValue($input);

        $this->assertSame($result, $validated);
    }
    public function validateValueData()
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
}