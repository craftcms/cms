<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use craft\db\Query;
use craft\models\Section_SiteSettings;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\validators\Validator;

/**
 * Will validate that the given attribute is a valid URI for a single section.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
        if (!($model instanceof Section_SiteSettings) || $attribute !== 'uriFormat') {
            throw new InvalidConfigException('Invalid use of SingleSectionUriValidator');
        }

        /** @var Section_SiteSettings $model */
        // Make sure it's a valid URI
        if (!(new UriValidator())->validate($model->uriFormat)) {
            $this->addError($model, $attribute, Craft::t('app', '{attribute} is not a valid URI'));
        }

        $section = $model->getSection();

        // Make sure no other elements are using this URI already
        $query = (new Query())
            ->from(['{{%elements_sites}} elements_sites'])
            ->where([
                'elements_sites.siteId' => $model->siteId,
                'elements_sites.uri' => $model->uriFormat
            ]);

        if ($section->id) {
            $query
                ->innerJoin('{{%entries}} entries', '[[entries.id]] = [[elements_sites.elementId]]')
                ->andWhere(['not', ['entries.sectionId' => $section->id]]);
        }

        if ($query->exists()) {
            $site = Craft::$app->getSites()->getSiteById($model->siteId);

            if (!$site) {
                throw new Exception('Invalid site ID: '.$model->siteId);
            }

            if ($model->uriFormat === '__home__') {
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
