<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use Craft;
use craft\base\Element;
use craft\controllers\ElementIndexesController;
use craft\db\Query;
use craft\elements\actions\Delete;
use craft\elements\actions\Edit;
use craft\elements\actions\NewChild;
use craft\elements\actions\SetStatus;
use craft\elements\actions\View;
use craft\elements\db\CategoryQuery;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\UrlHelper;
use craft\models\CategoryGroup;
use craft\records\Category as CategoryRecord;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Category represents a category element.
 *
 * @property CategoryGroup $group the category's group
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Category extends Element
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Category');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle()
    {
        return 'category';
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasUris(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     * @return CategoryQuery The newly created [[CategoryQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new CategoryQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context = null): array
    {
        $sources = [];

        if ($context === 'index') {
            $groups = Craft::$app->getCategories()->getEditableGroups();
        } else {
            $groups = Craft::$app->getCategories()->getAllGroups();
        }

        foreach ($groups as $group) {
            $sources[] = [
                'key' => 'group:'.$group->id,
                'label' => Craft::t('site', $group->name),
                'data' => ['handle' => $group->handle],
                'criteria' => ['groupId' => $group->id],
                'structureId' => $group->structureId,
                'structureEditable' => Craft::$app->getRequest()->getIsConsoleRequest() ? true : Craft::$app->getUser()->checkPermission('editCategories:'.$group->id),
            ];
        }

        return $sources;
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source = null): array
    {
        // Get the group we need to check permissions on
        if (preg_match('/^group:(\d+)$/', $source, $matches)) {
            $group = Craft::$app->getCategories()->getGroupById($matches[1]);
        }

        // Now figure out what we can do with it
        $actions = [];

        if (!empty($group)) {
            // Set Status
            $actions[] = SetStatus::class;

            // View
            // They are viewing a specific category group. See if it has URLs for the requested site
            $controller = Craft::$app->controller;
            if ($controller instanceof ElementIndexesController) {
                $siteId = $controller->getElementQuery()->siteId ?: Craft::$app->getSites()->getCurrentSite()->id;
                if (isset($group->siteSettings[$siteId]) && $group->siteSettings[$siteId]->hasUrls) {
                    $actions[] = Craft::$app->getElements()->createAction([
                        'type' => View::class,
                        'label' => Craft::t('app', 'View category'),
                    ]);
                }
            }

            // Edit
            $actions[] = Craft::$app->getElements()->createAction([
                'type' => Edit::class,
                'label' => Craft::t('app', 'Edit category'),
            ]);

            // New Child
            $structure = Craft::$app->getStructures()->getStructureById($group->structureId);

            if ($structure) {
                $actions[] = Craft::$app->getElements()->createAction([
                    'type' => NewChild::class,
                    'label' => Craft::t('app', 'Create a new child category'),
                    'maxLevels' => $structure->maxLevels,
                    'newChildUrl' => 'categories/'.$group->handle.'/new',
                ]);
            }

            // Delete
            $actions[] = Craft::$app->getElements()->createAction([
                'type' => Delete::class,
                'confirmationMessage' => Craft::t('app', 'Are you sure you want to delete the selected categories?'),
                'successMessage' => Craft::t('app', 'Categories deleted.'),
            ]);
        }

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'uri' => Craft::t('app', 'URI'),
            'elements.dateCreated' => Craft::t('app', 'Date Created'),
            'elements.dateUpdated' => Craft::t('app', 'Date Updated'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'title' => ['label' => Craft::t('app', 'Title')],
            'uri' => ['label' => Craft::t('app', 'URI')],
            'link' => ['label' => Craft::t('app', 'Link'), 'icon' => 'world'],
            'id' => ['label' => Craft::t('app', 'ID')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'link',
        ];
    }

    // Properties
    // =========================================================================

    /**
     * @var int|null Group ID
     */
    public $groupId;

    /**
     * @var int|null New parent ID
     */
    public $newParentId;

    /**
     * @var bool|null
     * @see _hasNewParent()
     */
    private $_hasNewParent;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        $names = parent::extraFields();
        $names[] = 'group';
        return $names;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['groupId', 'newParentId'], 'number', 'integerOnly' => true];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getUriFormat()
    {
        $categoryGroupSiteSettings = $this->getGroup()->getSiteSettings();

        if (!isset($categoryGroupSiteSettings[$this->siteId])) {
            throw new InvalidConfigException('Category’s group ('.$this->groupId.') is not enabled for site '.$this->siteId);
        }

        return $categoryGroupSiteSettings[$this->siteId]->uriFormat;
    }

    /**
     * @inheritdoc
     */
    protected function route()
    {
        // Make sure the category group is set to have URLs for this site
        $siteId = Craft::$app->getSites()->getCurrentSite()->id;
        $categoryGroupSiteSettings = $this->getGroup()->getSiteSettings();

        if (!isset($categoryGroupSiteSettings[$siteId]) || !$categoryGroupSiteSettings[$siteId]->hasUrls) {
            return null;
        }

        return [
            'templates/render', [
                'template' => $categoryGroupSiteSettings[$siteId]->template,
                'variables' => [
                    'category' => $this,
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function getIsEditable(): bool
    {
        return Craft::$app->getUser()->checkPermission('editCategories:'.$this->groupId);
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl()
    {
        $group = $this->getGroup();

        $url = UrlHelper::cpUrl('categories/'.$group->handle.'/'.$this->id.($this->slug ? '-'.$this->slug : ''));

        if (Craft::$app->getIsMultiSite() && $this->siteId != Craft::$app->getSites()->getCurrentSite()->id) {
            $url .= '/'.$this->getSite()->handle;
        }

        return $url;
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout()
    {
        return parent::getFieldLayout() ?? $this->getGroup()->getFieldLayout();
    }

    /**
     * Returns the category's group.
     *
     * @return CategoryGroup
     * @throws InvalidConfigException if [[groupId]] is missing or invalid
     */
    public function getGroup(): CategoryGroup
    {
        if ($this->groupId === null) {
            throw new InvalidConfigException('Category is missing its group ID');
        }

        $group = Craft::$app->getCategories()->getGroupById($this->groupId);

        if (!$group) {
            throw new InvalidConfigException('Invalid category group ID: '.$this->groupId);
        }

        return $group;
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function getEditorHtml(): string
    {
        $html = Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textField', [
            [
                'label' => Craft::t('app', 'Title'),
                'siteId' => $this->siteId,
                'id' => 'title',
                'name' => 'title',
                'value' => $this->title,
                'errors' => $this->getErrors('title'),
                'first' => true,
                'autofocus' => true,
                'required' => true
            ]
        ]);

        $html .= Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textField', [
            [
                'label' => Craft::t('app', 'Slug'),
                'siteId' => $this->siteId,
                'id' => 'slug',
                'name' => 'slug',
                'value' => $this->slug,
                'errors' => $this->getErrors('slug'),
                'required' => true
            ]
        ]);

        // Render the custom fields
        $html .= parent::getEditorHtml();

        return $html;
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function beforeSave(bool $isNew): bool
    {
        if ($this->_hasNewParent()) {
            if ($this->newParentId) {
                $parentCategory = Craft::$app->getCategories()->getCategoryById($this->newParentId, $this->siteId);

                if (!$parentCategory) {
                    throw new Exception('Invalid category ID: '.$this->newParentId);
                }
            } else {
                $parentCategory = null;
            }

            $this->setParent($parentCategory);
        }

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function afterSave(bool $isNew)
    {
        $group = $this->getGroup();

        // Get the category record
        if (!$isNew) {
            $record = CategoryRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid category ID: '.$this->id);
            }
        } else {
            $record = new CategoryRecord();
            $record->id = $this->id;
        }

        $record->groupId = $this->groupId;
        $record->save(false);

        // Has the parent changed?
        if ($this->_hasNewParent()) {
            if (!$this->newParentId) {
                Craft::$app->getStructures()->appendToRoot($group->structureId, $this);
            } else {
                Craft::$app->getStructures()->append($group->structureId, $this, $this->getParent());
            }
        }

        // Update the category's descendants, who may be using this category's URI in their own URIs
        Craft::$app->getElements()->updateDescendantSlugsAndUris($this, true, true);

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterMoveInStructure(int $structureId)
    {
        // Was the category moved within its group's structure?
        if ($this->getGroup()->structureId == $structureId) {
            // Update its URI
            Craft::$app->getElements()->updateElementSlugAndUri($this, true, true, true);

            // Make sure that each of the category's ancestors are related wherever the category is related
            $newRelationValues = [];

            $ancestorIds = $this->getAncestors()->ids();

            $sources = (new Query())
                ->select(['fieldId', 'sourceId', 'sourceSiteId'])
                ->from(['{{%relations}}'])
                ->where(['targetId' => $this->id])
                ->all();

            foreach ($sources as $source) {
                $existingAncestorRelations = (new Query())
                    ->select(['targetId'])
                    ->from(['{{%relations}}'])
                    ->where([
                        'fieldId' => $source['fieldId'],
                        'sourceId' => $source['sourceId'],
                        'sourceSiteId' => $source['sourceSiteId'],
                        'targetId' => $ancestorIds,
                    ])
                    ->column();

                $missingAncestorRelations = array_diff($ancestorIds, $existingAncestorRelations);

                foreach ($missingAncestorRelations as $categoryId) {
                    $newRelationValues[] = [
                        $source['fieldId'],
                        $source['sourceId'],
                        $source['sourceSiteId'],
                        $categoryId
                    ];
                }
            }

            if (!empty($newRelationValues)) {
                Craft::$app->getDb()->createCommand()
                    ->batchInsert(
                        '{{%relations}}',
                        ['fieldId', 'sourceId', 'sourceSiteId', 'targetId'],
                        $newRelationValues)
                    ->execute();
            }
        }

        parent::afterMoveInStructure($structureId);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns whether the category has been assigned a new parent entry.
     *
     * @return bool
     * @see beforeSave()
     * @see afterSave()
     */
    private function _hasNewParent(): bool
    {
        if ($this->_hasNewParent !== null) {
            return $this->_hasNewParent;
        }

        return $this->_hasNewParent = $this->_checkForNewParent();
    }

    /**
     * Checks if an category was submitted with a new parent category selected.
     *
     * @return bool
     */
    private function _checkForNewParent(): bool
    {
        // Is it a brand new category?
        if ($this->id === null) {
            return true;
        }

        // Was a new parent ID actually submitted?
        if ($this->newParentId === null) {
            return false;
        }

        // Is it set to the top level now, but it hadn't been before?
        if (!$this->newParentId && $this->level != 1) {
            return true;
        }

        // Is it set to be under a parent now, but didn't have one before?
        if ($this->newParentId && $this->level == 1) {
            return true;
        }

        // Is the newParentId set to a different category ID than its previous parent?
        $oldParentQuery = self::find();
        $oldParentQuery->ancestorOf($this);
        $oldParentQuery->ancestorDist(1);
        $oldParentQuery->status(null);
        $oldParentQuery->siteId($this->siteId);
        $oldParentQuery->enabledForSite(false);
        $oldParentQuery->select('elements.id');
        $oldParentId = $oldParentQuery->scalar();

        return $this->newParentId != $oldParentId;
    }
}
