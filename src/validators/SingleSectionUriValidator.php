<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\validators;

use Aws\CloudFront\Exception\Exception;
use Craft;
use craft\app\db\Query;
use craft\app\helpers\ElementHelper;
use craft\app\models\Section_SiteSettings;
use yii\base\InvalidConfigException;
use yii\validators\Validator;

/**
 * Will validate that the given attribute is a valid URI for a single section.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class SingleSectionUriValidator extends Validator
{
    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        if (!($model instanceof Section_SiteSettings) || $attribute != 'uriFormat') {
            throw new InvalidConfigException('Invalid use of SingleSectionUriValidator');
        }

        /** @var Section_SiteSettings $model */
        $section = $model->getSection();

        // Make sure no other elements are using this URI already
        $query = (new Query())
            ->from('{{%elements_i18n}} elements_i18n')
            ->where(
                [
                    'and',
                    'elements_i18n.siteId = :siteId',
                    'elements_i18n.uri = :uri'
                ],
                [
                    ':siteId' => $model->siteId,
                    ':uri' => $model->uriFormat
                ]
            );

        if ($section->id) {
            $query
                ->innerJoin('{{%entries}} entries', 'entries.id = elements_i18n.elementId')
                ->andWhere('entries.sectionId != :sectionId', [':sectionId' => $section->id]);
        }

        if ($query->exists()) {
            $site = Craft::$app->getSites()->getSiteById($model->siteId);

            if (!$site) {
                throw new Exception('Invalid site ID: '.$model->siteId);
            }

            if ($model->uriFormat == '__home__') {
                $message = '{site} already has a homepage.';
            } else {
                $message = '{site} already has an element with the URI “{value}”.';
            }

            $this->addError($model, $attribute, Craft::t('app', $message, [
                'site' => Craft::t('site', $site->name)
            ]));
        }
    }
}
