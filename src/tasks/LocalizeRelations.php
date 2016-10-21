<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\tasks;

use Craft;
use craft\app\base\Task;
use craft\app\db\Query;

/**
 * LocalizeRelations represents a Localize Relations background task.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class LocalizeRelations extends Task
{
    // Properties
    // =========================================================================

    /**
     * @var integer The field ID whose data should be localized
     */
    public $fieldId;

    /**
     * @var
     */
    private $_relations;

    /**
     * @var
     */
    private $_allSiteIds;

    /**
     * @var
     */
    private $_workingSiteId;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTotalSteps()
    {
        $this->_relations = (new Query())
            ->select('id, sourceId, sourceSiteId, targetId, sortOrder')
            ->from('{{%relations}}')
            ->where(['and', 'fieldId = :fieldId', 'sourceSiteId is null'],
                [':fieldId' => $this->fieldId])
            ->all();

        $this->_allSiteIds = Craft::$app->getSites()->getAllSiteIds();

        return count($this->_relations);
    }

    /**
     * @inheritdoc
     */
    public function runStep($step)
    {
        $db = Craft::$app->getDb();
        try {
            $this->_workingSiteId = $this->_allSiteIds[0];

            // Update the existing one.
            $db->createCommand()
                ->update(
                    '{{%relations}}',
                    ['sourceSiteId' => $this->_workingSiteId],
                    ['id' => $this->_relations[$step]['id']])
                ->execute();

            for ($counter = 1; $counter < count($this->_allSiteIds); $counter++) {
                $this->_workingSiteId = $this->_allSiteIds[$counter];

                $db->createCommand()
                    ->insert(
                        '{{%relations}}',
                        [
                            'fieldid' => $this->fieldId,
                            'sourceId' => $this->_relations[$step]['sourceId'],
                            'sourceSiteId' => $this->_workingSiteId,
                            'targetId' => $this->_relations[$step]['targetId'],
                            'sortOrder' => $this->_relations[$step]['sortOrder'],
                        ])
                    ->execute();
            }

            return true;
        } catch (\Exception $e) {
            return 'An exception was thrown while trying to save relation for the field with Id '.$this->_relations[$step]['id'].' into the site  “'.$this->_workingSiteId.'”: '.$e->getMessage();
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function getDefaultDescription()
    {
        return Craft::t('app', 'Localizing relations');
    }
}
