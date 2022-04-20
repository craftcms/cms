<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use craft\base\ElementInterface;
use craft\db\Connection;
use craft\db\Query;
use craft\db\Table;
use craft\events\ElementContentEvent;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use yii\base\Component;
use yii\base\Exception;
use yii\di\Instance;

/**
 * Content service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getContent()|`Craft::$app->content`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Content extends Component
{
    /**
     * @event ElementContentEvent The event that is triggered before an element’s content is saved.
     */
    public const EVENT_BEFORE_SAVE_CONTENT = 'beforeSaveContent';

    /**
     * @event ElementContentEvent The event that is triggered after an element’s content is saved.
     */
    public const EVENT_AFTER_SAVE_CONTENT = 'afterSaveContent';

    /**
     * @var Connection|array|string The database connection to use
     * @since 3.5.6
     */
    public string|array|Connection $db = 'db';

    /**
     * @var string
     */
    public string $contentTable = Table::CONTENT;

    /**
     * @var string|null
     */
    public ?string $fieldColumnPrefix = 'field_';

    /**
     * @var string
     */
    public string $fieldContext = 'global';

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::class);
    }

    /**
     * Saves an element’s content.
     *
     * @param ElementInterface $element The element whose content we’re saving.
     * @return bool Whether the content was saved successfully. If it wasn’t, any validation errors will be saved on the
     * element and its content model.
     * @throws Exception if $element has not been saved yet
     */
    public function saveContent(ElementInterface $element): bool
    {
        if (!$element->id) {
            throw new Exception('Cannot save the content of an unsaved element.');
        }

        // Serialize the values before we start futzing with the content table & col prefix
        $serializedFieldValues = [];
        $fields = [];
        $fieldLayout = $element->getFieldLayout();

        if ($fieldLayout) {
            foreach ($fieldLayout->getCustomFields() as $field) {
                if (
                    (!$element->contentId || $element->isFieldDirty($field->handle)) &&
                    $field::hasContentColumn()
                ) {
                    $serializedFieldValues[$field->uid] = $field->serializeValue($element->getFieldValue($field->handle), $element);
                    $fields[$field->uid] = $field;
                }
            }
        }

        $originalContentTable = $this->contentTable;
        $originalFieldColumnPrefix = $this->fieldColumnPrefix;
        $originalFieldContext = $this->fieldContext;

        $this->contentTable = $element->getContentTable();
        $this->fieldColumnPrefix = $element->getFieldColumnPrefix();
        $this->fieldContext = $element->getFieldContext();

        // Fire a 'beforeSaveContent' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_CONTENT)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_CONTENT, new ElementContentEvent([
                'element' => $element,
            ]));
        }

        // Prepare the data to be saved
        $values = [
            'elementId' => $element->id,
            'siteId' => $element->siteId,
        ];
        if ($element->hasTitles() && ($title = (string)$element->title) !== '') {
            $values['title'] = $title;
        }

        foreach ($serializedFieldValues as $fieldUid => $value) {
            $field = $fields[$fieldUid];
            $type = $field->getContentColumnType();

            if (is_array($type)) {
                foreach (array_keys($type) as $i => $key) {
                    $column = ElementHelper::fieldColumnFromField($field, $i !== 0 ? $key : null);
                    $values[$column] = Db::prepareValueForDb($value[$key] ?? null);
                }
            } else {
                $column = ElementHelper::fieldColumnFromField($field);
                $values[$column] = Db::prepareValueForDb($value);
            }
        }

        if (!$element->contentId) {
            // It could be a draft that's getting published
            $element->contentId = (new Query())
                ->select(['id'])
                ->from([$this->contentTable])
                ->where([
                    'elementId' => $element->id,
                    'siteId' => $element->siteId,
                ])
                ->scalar();
        }

        // Insert/update the DB row
        if ($element->contentId) {
            // Update the existing row
            Db::update($this->contentTable, $values, [
                'id' => $element->contentId,
            ], [], true, $this->db);
        } else {
            // Insert a new row and store its ID on the element
            Db::insert($this->contentTable, $values, $this->db);
            $element->contentId = (int)$this->db->getLastInsertID($this->contentTable);
        }

        // Fire an 'afterSaveContent' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_CONTENT)) {
            $this->trigger(self::EVENT_AFTER_SAVE_CONTENT, new ElementContentEvent([
                'element' => $element,
            ]));
        }

        $this->contentTable = $originalContentTable;
        $this->fieldColumnPrefix = $originalFieldColumnPrefix;
        $this->fieldContext = $originalFieldContext;

        return true;
    }
}
