<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\elements\actions;

use Craft;
use craft\app\base\ElementAction;
use craft\app\base\Element;
use craft\app\elements\db\ElementQuery;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\events\SetStatusEvent;

/**
 * SetStatus represents a Set Status element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class SetStatus extends ElementAction
{
    // Properties
    // =========================================================================

    /**
     * @var string The status elements should be set to
     */
    public $status;

    // Constants
    // =========================================================================

    /**
     * @event SetStatusEvent The event that is triggered after the statuses have been updated.
     */
    const EVENT_AFTER_SET_STATUS = 'afterSetStatus';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTriggerLabel()
    {
        return Craft::t('app', 'Set Status');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['status'], 'required'];
        $rules[] = [
            ['status'],
            'in',
            'range' => [Element::STATUS_ENABLED, Element::STATUS_DISABLED]
        ];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml()
    {
        return Craft::$app->getView()->renderTemplate('_components/elementactions/SetStatus/trigger');
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query)
    {
        /** @var ElementQuery $query */
        // Figure out which element IDs we need to update
        if ($this->status == Element::STATUS_ENABLED) {
            $sqlNewStatus = '1';
        } else {
            $sqlNewStatus = '0';
        }

        $elementIds = $query->ids();

        // Update their statuses
        Craft::$app->getDb()->createCommand()
            ->update(
                '{{%elements}}',
                ['enabled' => $sqlNewStatus],
                ['in', 'id', $elementIds])
            ->execute();

        if ($this->status == Element::STATUS_ENABLED) {
            // Enable for the site as well
            Craft::$app->getDb()->createCommand()
                ->update(
                    '{{%elements_i18n}}',
                    ['enabled' => $sqlNewStatus],
                    [
                        'and',
                        ['in', 'elementId', $elementIds],
                        ['siteId' => $query->siteId]
                    ])
                ->execute();
        }

        // Clear their template caches
        Craft::$app->getTemplateCaches()->deleteCachesByElementId($elementIds);

        // Fire an 'afterSetStatus' event
        $this->trigger(self::EVENT_AFTER_SET_STATUS, new SetStatusEvent([
            'elementQuery' => $query,
            'elementIds' => $elementIds,
            'status' => $this->status,
        ]));

        $this->setMessage(Craft::t('app', 'Statuses updated.'));

        return true;
    }
}
