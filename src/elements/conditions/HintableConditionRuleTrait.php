<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\conditions;

use Craft;

/**
 * HintableConditionRuleTrait
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.9.0
 */
trait HintableConditionRuleTrait
{
    /**
     * @inheritdoc
     */
    public function showLabelHint(): bool
    {
        return Craft::$app->getUser()->getIdentity()?->getPreference('showFieldHandles') ?? false;
    }
}
