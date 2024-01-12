<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\models\Section_SiteSettings;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Will validate that the given attribute is a valid URI for a single section.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SingleSectionUriValidator extends UriFormatValidator
{
    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute): void
    {
        if (!$model instanceof Section_SiteSettings || $attribute !== 'uriFormat') {
            throw new InvalidConfigException('Invalid use of SingleSectionUriValidator');
        }

        parent::validateAttribute($model, $attribute);

        /** @var Section_SiteSettings $model */
        $section = $model->getSection();

        // Make sure no other elements are using this URI already
        $query = (new Query())
            ->from(['elements_sites' => Table::ELEMENTS_SITES])
            ->innerJoin(['elements' => Table::ELEMENTS], '[[elements.id]] = [[elements_sites.elementId]]')
            ->where([
                'elements_sites.siteId' => $model->siteId,
                'elements.draftId' => null,
                'elements.revisionId' => null,
                'elements.dateDeleted' => null,
            ]);

        if (Craft::$app->getDb()->getIsMysql()) {
            $query->andWhere([
                'elements_sites.uri' => $model->uriFormat,
            ]);
        } else {
            $query->andWhere([
                'lower([[elements_sites.uri]])' => mb_strtolower($model->uriFormat),
            ]);
        }

        if ($section->id) {
            $query
                ->innerJoin(['entries' => Table::ENTRIES], '[[entries.id]] = [[elements.id]]')
                ->andWhere(['not', ['entries.sectionId' => $section->id]]);
        }

        if ($query->exists()) {
            $site = Craft::$app->getSites()->getSiteById($model->siteId);

            if (!$site) {
                throw new Exception('Invalid site ID: ' . $model->siteId);
            }

            if ($model->uriFormat === '__home__') {
                $message = '{site} already has a homepage.';
            } else {
                $message = '{site} already has an element with the URI “{value}”.';
            }

            $this->addError($model, $attribute, Craft::t('app', $message, [
                'site' => Craft::t('site', $site->getName()),
            ]));
        }
    }
}
