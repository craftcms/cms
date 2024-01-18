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
use yii\db\ActiveQueryInterface;

/**
 * Class UniqueValidatorTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 4.5.0
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
     * @param int|null $id
     * @param array $config
     * @param bool $mustValidate
     * @param array $modelConfig
     */
    public function testValidation(string $attribute, ?int $id = null, array $config = [], bool $mustValidate = true, array $modelConfig = []): void
    {
        $uniqueValidator = new UniqueValidator($config);
        if ($id) {
            $model = \Craft::$app->getUserGroups()->getGroupById($id);

            if (!empty($modelConfig)) {
                $model->setAttributes($modelConfig);
            }
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
    public static function uniqueValidatorValidateDataProvider(): array
    {
        return [
            'existing' => ['handle', 1000, ['targetClass' => UserGroupRecord::class], true],
            'unique' => ['handle', 1000, ['targetClass' => UserGroupRecord::class], true, ['handle' => 'group99']],
            'not-unique' => ['handle', 1000, ['targetClass' => UserGroupRecord::class], false, ['handle' => 'group2']],
            'new-unique' => ['handle', null, ['targetClass' => UserGroupRecord::class], true, ['handle' => 'group99']],
            'new-not-unique' => ['handle', null, ['targetClass' => UserGroupRecord::class], false, ['handle' => 'group1']],

            // Add extra filter to make sure it passes validation
            'not-unique-extra-filter' => ['handle', 1000, ['targetClass' => UserGroupRecord::class, 'filter' => ['not', ['description' => null]]], true, ['handle' => 'group2']],
            'not-unique-closure-filter' => ['handle', 1000, ['targetClass' => UserGroupRecord::class, 'filter' => fn(ActiveQueryInterface $query) => $query->andWhere(['not', ['description' => null]])], true, ['handle' => 'group2']],
            'new-not-unique-extra-filter' => ['handle', null, ['targetClass' => UserGroupRecord::class, 'filter' => ['not', ['description' => null]]], true, ['handle' => 'group2']],
            'new-not-unique-closure-filter' => ['handle', null, ['targetClass' => UserGroupRecord::class, 'filter' => fn(ActiveQueryInterface $query) => $query->andWhere(['not', ['description' => null]])], true, ['handle' => 'group2']],
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
