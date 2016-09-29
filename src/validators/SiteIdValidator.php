<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\validators;

use Craft;
use yii\validators\Validator;

/**
 * Will validate that the given attribute is a valid site ID.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class SiteIdValidator extends Validator
{
    // Protected Methods
    // =========================================================================

    /**
     * @param $object
     * @param $attribute
     *
     * @return void
     */
    public function validateAttribute($object, $attribute)
    {
        $siteId = $object->$attribute;

        if ($siteId && !in_array($siteId, Craft::$app->getSites()->getAllSiteIds())) {
            $message = Craft::t('app', 'Your site isn’t set up to save content for the site “{site}”.', ['site' => $siteId]);
            $this->addError($object, $attribute, $message);
        }
    }
}
