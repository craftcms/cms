<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\elements;

use Craft;
use craft\app\base\Element;
use craft\app\base\ElementInterface;
use craft\app\controllers\ElementIndexesController;
use craft\app\db\Query;
use craft\app\elements\actions\Delete;
use craft\app\elements\actions\Edit;
use craft\app\elements\actions\NewChild;
use craft\app\elements\actions\SetStatus;
use craft\app\elements\actions\View;
use craft\app\elements\db\CategoryQuery;
use craft\app\helpers\Url;
use craft\app\models\CategoryGroup;
use craft\app\records\Category as CategoryRecord;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Category represents a category element.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Category extends Element
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName()
    {
        return Craft::t('app', 'Category');
    }

    /**
     * @inheritdoc
     */
    public static function hasContent()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses()
    {
        return true;
    }

    /**
     * @inheritdoc
     *
     * @return CategoryQuery The newly created [[CategoryQuery]] instance.
     */
    public static function find()
    {
        return new CategoryQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public static function getSources($context = null)
    {
        $sources = [];

        if ($context == 'index') {
            $groups = Craft::$app->getCategories()->getEditableGroups();
        } else {
            $groups = Craft::$app->getCategories()->getAllGroups();
        }

        foreach ($groups as $group) {
            $key = 'group:'.$group->id;

            $sources[$key] = [
                'label' => Craft::t('site', $group->name),
                'data' => ['handle' => $group->handle],
                'criteria' => ['groupId' => $group->id],
                'structureId' => $group->structureId,
                'structureEditable' => Craft::$app->getUser()->checkPermission('editCategories:'.$group->id),
            ];
        }

        // Allow plugins to modify the sources
        Craft::$app->getPlugins()->call('modifyCategorySources',
            [&$sources, $context]);

        return $sources;
    }

    /**
     * @inheritdoc
     */
    public static function getAvailableActions($source = null)
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
                $siteId = $controller->getElementQuery()->siteId ?: Craft::$app->getSites()->currentSite->id;
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

        // Allow plugins to add additional actions
        $allPluginActions = Craft::$app->getPlugins()->call('addCategoryActions',
            [$source], true);

        foreach ($allPluginActions as $pluginActions) {
            $actions = array_merge($actions, $pluginActions);
        }

        return $actions;
    }

    /**
     * @inheritdoc
     */
    public static function defineSortableAttributes()
    {
        $attributes = [
            'title' => Craft::t('app', 'Title'),
            'uri' => Craft::t('app', 'URI'),
            'elements.dateCreated' => Craft::t('app', 'Date Created'),
            'elements.dateUpdated' => Craft::t('app', 'Date Updated'),
        ];

        // Allow plugins to modify the attributes
        Craft::$app->getPlugins()->call('modifyCategorySortableAttributes',
            [&$attributes]);

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public static function defineAvailableTableAttributes()
    {
        $attributes = [
            'title' => ['label' => Craft::t('app', 'Title')],
            'uri' => ['label' => Craft::t('app', 'URI')],
            'link' => ['label' => Craft::t('app', 'Link'), 'icon' => 'world'],
            'id' => ['label' => Craft::t('app', 'ID')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
        ];

        // Allow plugins to modify the attributes
        $pluginAttributes = Craft::$app->getPlugins()->call('defineAdditionalCategoryTableAttributes', [], true);

        foreach ($pluginAttributes as $thisPluginAttributes) {
            $attributes = array_merge($attributes, $thisPluginAttributes);
        }

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultTableAttributes($source = null)
    {
        $attributes = ['link'];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public static function getElementRoute(ElementInterface $element)
    {
        /** @var Category $element */
        // Make sure the category group is set to have URLs for this site
        $siteId = Craft::$app->getSites()->currentSite->id;
        $categoryGroupSiteSettings = $element->getGroup()->getSiteSettings();

        if (isset($categoryGroupSiteSettings[$siteId]) && $categoryGroupSiteSettings[$siteId]->hasUrls) {
            return [
                'templates/render',
                [
                    'template' => $categoryGroupSiteSettings[$siteId]->template,
                    'variables' => [
                        'category' => $element
                    ]
                ]
            ];
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public static function onAfterMoveElementInStructure(ElementInterface $element, $structureId)
    {
        /** @var Category $element */
        // Was the category moved within its group's structure?
        if ($element->getGroup()->structureId == $structureId) {
            // Update its URI
            Craft::$app->getElements()->updateElementSlugAndUri($element, true, true, true);

            // Make sure that each of the category's ancestors are related wherever the category is related
            $newRelationValues = [];

            $ancestorIds = $element->getAncestors()->ids();

            $sources = (new Query())
                ->select(['fieldId', 'sourceId', 'sourceSiteId'])
                ->from('{{%relations}}')
                ->where('targetId = :categoryId',
                    [':categoryId' => $element->id])
                ->all();

            foreach ($sources as $source) {
                $existingAncestorRelations = (new Query())
                    ->select('targetId')
                    ->from('{{%relations}}')
                    ->where([
                        'and',
                        'fieldId = :fieldId',
                        'sourceId = :sourceId',
                        'sourceSiteId = :sourceSiteId',
                        ['in', 'targetId', $ancestorIds]
                    ], [
                        ':fieldId' => $source['fieldId'],
                        ':sourceId' => $source['sourceId'],
                        ':sourceSiteId' => $source['sourceSiteId']
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

            if ($newRelationValues) {
                Craft::$app->getDb()->createCommand()
                    ->batchInsert(
                        '{{%relations}}',
                        ['fieldId', 'sourceId', 'sourceSiteId', 'targetId'],
                        $newRelationValues)
                    ->execute();
            }
        }
    }

    // Properties
    // =========================================================================

    /**
     * @var integer Group ID
     */
    public $groupId;

    /**
     * @var integer New parent ID
     */
    public $newParentId;

    /**
     * @var boolean
     * @see _hasNewParent()
     */
    private $_hasNewParent;

    // Public Methods
    // =========================================================================

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
     * @throws Exception if reasons
     */
    public function beforeSave($isNew)
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
    public function afterSave($isNew)
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
    public function getFieldLayout()
    {
        return $this->getGroup()->getFieldLayout();
    }

    /**
     * @inheritdoc
     */
    public function getUriFormat()
    {
        $categoryGroupSiteSettings = $this->getGroup()->getSiteSettings();

        if (!isset($categoryGroupSiteSettings[$this->siteId])) {
            throw new InvalidConfigException('Category\'s group ('.$this->groupId.') is not enabled for site '.$this->siteId);
        }

        return $categoryGroupSiteSettings[$this->siteId]->uriFormat;
    }

    /**
     * @inheritdoc
     */
    public function getIsEditable()
    {
        return Craft::$app->getUser()->checkPermission('editCategories:'.$this->groupId);
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl()
    {
        $group = $this->getGroup();

        $url = Url::getCpUrl('categories/'.$group->handle.'/'.$this->id.($this->slug ? '-'.$this->slug : ''));

        if (Craft::$app->getIsMultiSite() && $this->siteId != Craft::$app->getSites()->currentSite->id) {
            $url .= '/'.$this->getSite()->handle;
        }

        return $url;
    }

    /**
     * Returns the category's group.
     *
     * @return CategoryGroup
     * @throws InvalidConfigException if [[groupId]] is missing or invalid
     */
    public function getGroup()
    {
        if (!$this->groupId) {
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
    public function getTableAttributeHtml($attribute)
    {
        // First give plugins a chance to set this
        $pluginAttributeHtml = Craft::$app->getPlugins()->callFirst('getCategoryTableAttributeHtml', [$this, $attribute], true);

        if ($pluginAttributeHtml !== null) {
            return $pluginAttributeHtml;
        }

        return parent::getTableAttributeHtml($attribute);
    }

    /**
     * @inheritdoc
     */
    public function getEditorHtml()
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

        $html .= parent::getEditorHtml();

        return $html;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function resolveStructureId()
    {
        return $this->getGroup()->structureId;
    }

    /**
     * Returns whether the category has been assigned a new parent entry.
     *
     * @return boolean
     * @see beforeSave()
     * @see afterSave()
     */
    private function _hasNewParent()
    {
        if (!isset($this->_hasNewParent)) {
            $this->_hasNewParent = $this->_checkForNewParent();
        }

        return $this->_hasNewParent;
    }

    /**
     * Checks if an category was submitted with a new parent category selected.
     *
     * @return boolean
     */
    private function _checkForNewParent()
    {
        // Is it a brand new category?
        if (!$this->id) {
            return true;
        }

        // Was a new parent ID actually submitted?
        if ($this->newParentId === null) {
            return false;
        }

        // Is it set to the top level now, but it hadn't been before?
        if ($this->newParentId === '' && $this->level != 1) {
            return true;
        }

        // Is it set to be under a parent now, but didn't have one before?
        if ($this->newParentId !== '' && $this->level == 1) {
            return true;
        }

        // Is the newParentId set to a different category ID than its previous parent?
        $oldParentId = Category::find()
            ->ancestorOf($this)
            ->ancestorDist(1)
            ->status(null)
            ->siteId($this->siteId)
            ->enabledForSite(false)
            ->select('elements.id')
            ->scalar();

        if ($this->newParentId != $oldParentId) {
            return true;
        }

        // Must be set to the same one then
        return false;
    }
}
