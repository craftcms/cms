<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use CraftCms\Yii2Adapter\ModelWrapper;
use CraftCms\Yii2Adapter\Validation\LegacyYiiRules;

/**
 * ElementAction is the base class for classes representing element actions in terms of objects.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
abstract class ElementAction extends \CraftCms\Cms\Element\Actions\ElementAction implements ElementActionInterface
{
    public function getRules(): array
    {
        return LegacyYiiRules::mergeAttributeRules(
            rules: parent::getRules(),
            target: $this,
            yiiRules: $this->defineRules(),
            validatorTarget: fn() => new ModelWrapper($this),
            allowMethodValidators: true,
        );
    }

    /**
     * @return array<int, array|string>
     */
    protected function defineRules(): array
    {
        return [];
    }
}
