<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use yii\validators\Validator;

/**
 * Will validate that the given attribute is a valid site ID.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SiteIdValidator extends Validator
{
    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $siteId = $model->$attribute;

        if ($siteId && !in_array($siteId, Craft::$app->getSites()->getAllSiteIds(), false)) {
            $message = Craft::t('app', 'Your system isn’t set up to save content for the site “{site}”.', ['site' => $siteId]);
            $this->addError($model, $attribute, $message);
        }
    }
}
