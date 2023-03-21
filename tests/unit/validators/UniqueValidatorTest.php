<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\validators;

use craft\models\UserGroup;
use craft\records\UserGroup as UserGroupRecord;
use craft\test\TestCase;
use craft\validators\UniqueValidator;
use crafttests\fixtures\UserGroupsFixture;

/**
 * Class UniqueValidatorTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 4.4.x
 */
class UniqueValidatorTest extends TestCase
{
    /**
     * @var UniqueValidator
     */
    protected UniqueValidator $uniqueValidator;

    public function _fixtures(): array
    {
        return [
            'user-groups' => [
                'class' => UserGroupsFixture::class,
            ],
        ];
    }

    /**
     * @dataProvider uniqueValidatorValidateDataProvider
     * @param string $attribute
     * @param array $config
     * @param bool $mustValidate
     */
    public function testValidation(string $attribute, ?int $id = null, array $config = [], bool $mustValidate = true, array $modelConfig = []): void
    {
        $uniqueValidator = new UniqueValidator($config);
        if ($id) {
            $model = \Craft::$app->getUserGroups()->getGroupById($id);
        } else {
            $model = new UserGroup($modelConfig);
        }

        $uniqueValidator->validateAttribute($model, $attribute);

        if ($mustValidate) {
            self::assertArrayNotHasKey($attribute, $model->getErrors());
        } else {
            self::assertArrayHasKey($attribute, $model->getErrors());
        }
    }

    /**
     * @return array
     */
    public function uniqueValidatorValidateDataProvider(): array
    {
        return [
            'existing' => ['handle', 1000, ['targetClass' => UserGroupRecord::class], true],
            'new-unique' => ['name', null, ['targetClass' => UserGroupRecord::class], true, ['name' => 'Test', 'handle' => 'test']],
            'new-not-unique' => ['name', null, ['targetClass' => UserGroupRecord::class], false, ['name' => 'Group 1', 'handle' => 'group1']],
            // Add extra filter to make sure it passes validation
            'new-not-unique-extra-filter' => ['name', null, ['targetClass' => UserGroupRecord::class, 'filter' => ['not', ['description' => null]]], true, ['name' => 'Group 1', 'handle' => 'group1']],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        $this->uniqueValidator = new UniqueValidator();
    }
}
