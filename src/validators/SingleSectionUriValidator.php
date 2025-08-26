<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use craft\models\Section_SiteSettings;
use CraftCms\Cms\Database\Table;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Function\String\Lower;
use Tpetry\QueryExpressions\Language\Alias;
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
        $query = DB::table(Table::ELEMENTS_SITES, 'elements_sites')
            ->join(new Alias(Table::ELEMENTS, 'elements'), 'elements.id', '=', 'elements_sites.elementId')
            ->where('elements_sites.siteId', $model->siteId)
            ->whereNull(['elements.draftId', 'elements.revisionId', 'elements.dateDeleted'])
            ->where(new Lower('elements_sites.uri'), mb_strtolower($model->uriFormat))
            ->when(
                $section->id,
                fn(Builder $query) => $query->join(new Alias(Table::ENTRIES, 'entries'), 'entries.id', '=', 'elements.id')
                    ->whereNot('entries.sectionId', $section->id),
            );

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
