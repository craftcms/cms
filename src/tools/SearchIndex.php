<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\tools;

use Craft;
use craft\app\base\Element;
use craft\app\base\Field;
use craft\app\base\Tool;
use craft\app\base\ElementInterface;
use craft\app\db\Query;

/**
 * SearchIndex represents a Rebuild Search Indexes tool.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class SearchIndex extends Tool
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName()
    {
        return Craft::t('app', 'Rebuild Search Indexes');
    }

    /**
     * @inheritdoc
     */
    public static function iconValue()
    {
        return 'search';
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function performAction($params = [])
    {
        if (!empty($params['start'])) {
            // Truncate the searchindex table
            Craft::$app->getDb()->createCommand()
                ->truncateTable('{{%searchindex}}')
                ->execute();

            // Get all the element IDs ever
            $elements = (new Query())
                ->select(['id', 'type'])
                ->from('{{%elements}}')
                ->all();

            $batch = [];

            foreach ($elements as $element) {
                $batch[] = ['params' => $element];
            }

            return [
                'batches' => [$batch]
            ];
        } else {
            /** @var ElementInterface $class */
            $class = $params['type'];

            if ($class::isLocalized()) {
                $siteIds = Craft::$app->getSites()->getAllSiteIds();
            } else {
                $siteIds = [Craft::$app->getSites()->getPrimarySite()->id];
            }

            $query = $class::find()
                ->id($params['id'])
                ->status(null)
                ->enabledForSite(false);

            foreach ($siteIds as $siteId) {
                $query->siteId($siteId);
                $element = $query->one();

                if ($element) {
                    /** @var Element $element */
                    Craft::$app->getSearch()->indexElementAttributes($element);

                    if ($class::hasContent()) {
                        $fieldLayout = $element->getFieldLayout();
                        $keywords = [];

                        foreach ($fieldLayout->getFields() as $field) {
                            /** @var Field $field */
                            // Set the keywords for the content's site
                            $fieldValue = $element->getFieldValue($field->handle);
                            $fieldSearchKeywords = $field->getSearchKeywords($fieldValue, $element);
                            $keywords[$field->id] = $fieldSearchKeywords;
                        }

                        Craft::$app->getSearch()->indexElementFields($element->id, $siteId, $keywords);
                    }
                }
            }
        }

        return null;
    }
}
