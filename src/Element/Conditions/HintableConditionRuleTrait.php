<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace CraftCms\Cms\Element\Conditions;

use Illuminate\Support\Facades\Auth;

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
        return Auth::user()?->getPreference('showFieldHandles') ?? false;
    }
}
