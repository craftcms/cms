<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use Craft;
use craft\base\ElementInterface;
use craft\elements\actions\ChangeSortOrder;
use craft\elements\db\ElementQueryInterface;
use craft\enums\PropagationMethod;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\models\Site;
use yii\base\BaseObject;

/**
 * Nested Element Manager
 *
 * This can be used by elements or fields to manage nested elements, such as users → addresses,
 * or Matrix fields → nested entries.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class NestedElementManager extends BaseObject
{
    /**
     * Constructor
     *
     * @param class-string<ElementInterface> $elementType The nested element type.
     * @param string $attribute The attribute name that nested elements are accessible by, from owner elements.
     * If this is for a custom field, it should be in the format of `field:<fieldHandle>`.
     * The attribute should return either an element query or a collection.
     * @param array $config name-value pairs that will be used to initialize the object properties.
     */
    public function __construct(
        public readonly string $elementType,
        public readonly string $attribute,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    /**
     * @var string The name of the element query param that nested elements use to associate with the owner’s ID
     */
    public string $ownerIdParam = 'ownerId';

    /**
     * @var string The name of the element attribute that identifies the owner element,
     * which should be set on newly-created nested elements.
     */
    public string $ownerIdAttribute = 'ownerId';

    /**
     * @var array Additional element query params that should be set when fetching nested elements.
     */
    public array $criteria = [];

    /**
     * @var string The “Create” button label
     */
    public string $createButtonLabel;

    /**
     * @var PropagationMethod The propagation method that the nested elements should use.
     *
     *  This can be set to one of the following:
     *
     *  - [[PropagationMethod::None]] – Only save elements in the site they were created in
     *  - [[PropagationMethod::SiteGroup]] – Save elements to other sites in the same site group
     *  - [[PropagationMethod::Language]] – Save elements to other sites with the same language
     *  - [[PropagationMethod::Custom]] – Save elements to other sites based on a custom [[$propagationKeyFormat|propagation key format]]
     *  - [[PropagationMethod::All]] – Save elements to all sites supported by the owner element
     */
    public PropagationMethod $propagationMethod = PropagationMethod::All;

    /**
     * @var string|null The propagation key format that the nested elements should use,
     * if [[$propagationMethod]] is set to [[PropagationMethod::Custom]].
     */
    public ?string $propagationKeyFormat = null;

    /**
     * Returns whether the field or attribute should be shown as translatable in the UI, for the given owner element.
     *
     * @param ElementInterface|null $owner
     * @return bool
     */
    public function getIsTranslatable(?ElementInterface $owner = null): bool
    {
        if ($this->propagationMethod === PropagationMethod::Custom) {
            return (
                $owner === null ||
                Craft::$app->getView()->renderObjectTemplate($this->propagationKeyFormat, $owner) !== ''
            );
        }

        return $this->propagationMethod !== PropagationMethod::All;
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (!isset($this->createButtonLabel)) {
            /** @var ElementInterface|string $elementType */
            $elementType = $this->elementType;
            $this->createButtonLabel = Craft::t('app', 'New {type}', [
                'type' => $elementType::lowerDisplayName(),
            ]);
        }
    }

    /**
     * Returns an element query used to fetch the nested elements for a given owner element,
     * or a collection of the nested elements if they were already eager-loaded.
     *
     * @param ElementInterface $owner
     * @return ElementQueryInterface|ElementCollection
     */
    public function getNestedElements(ElementInterface $owner): ElementQueryInterface|ElementCollection
    {
        return $owner->{$this->attribute};
    }

    /**
     * Returns the description of this field or attribute’s translation support, for the given owner element.
     *
     * @param ElementInterface|null $owner
     * @return string|null
     */
    public function getTranslationDescription(?ElementInterface $owner = null): ?string
    {
        if (!$owner) {
            return null;
        }

        /** @var ElementInterface|string $elementType */
        $elementType = $this->elementType;

        switch ($this->propagationMethod) {
            case PropagationMethod::None:
                return Craft::t('app', '{type} will only be saved in the {site} site.', [
                    'type' => $elementType::pluralDisplayName(),
                    'site' => Craft::t('site', $owner->getSite()->getName()),
                ]);
            case PropagationMethod::SiteGroup:
                return Craft::t('app', '{type} will be saved across all sites in the {group} site group.', [
                    'type' => $elementType::pluralDisplayName(),
                    'group' => Craft::t('site', $owner->getSite()->getGroup()->getName()),
                ]);
            case PropagationMethod::Language:
                $language = Craft::$app->getI18n()->getLocaleById($owner->getSite()->language)
                    ->getDisplayName(Craft::$app->language);
                return Craft::t('app', '{type} will be saved across all {language}-language sites.', [
                    'type' => $elementType::pluralDisplayName(),
                    'language' => $language,
                ]);
            default:
                return null;
        }
    }

    /**
     * Returns the site IDs that are supported by nested elements for the given owner element.
     *
     * @param ElementInterface $owner
     * @return int[]
     * @since 5.0.0
     */
    public function getSupportedSiteIds(ElementInterface $owner): array
    {
        /** @var Site[] $allSites */
        $allSites = ArrayHelper::index(Craft::$app->getSites()->getAllSites(), 'id');
        $ownerSiteIds = ArrayHelper::getColumn(ElementHelper::supportedSitesForElement($owner), 'siteId');
        $siteIds = [];

        $view = Craft::$app->getView();
        $elementsService = Craft::$app->getElements();

        if ($this->propagationMethod === PropagationMethod::Custom && $this->propagationKeyFormat !== null) {
            $propagationKey = $view->renderObjectTemplate($this->propagationKeyFormat, $owner);
        }

        foreach ($ownerSiteIds as $siteId) {
            switch ($this->propagationMethod) {
                case PropagationMethod::None:
                    $include = $siteId == $owner->siteId;
                    break;
                case PropagationMethod::SiteGroup:
                    $include = $allSites[$siteId]->groupId == $allSites[$owner->siteId]->groupId;
                    break;
                case PropagationMethod::Language:
                    $include = $allSites[$siteId]->language == $allSites[$owner->siteId]->language;
                    break;
                case PropagationMethod::Custom:
                    if (!isset($propagationKey)) {
                        $include = true;
                    } else {
                        $siteOwner = $elementsService->getElementById($owner->id, get_class($owner), $siteId);
                        $include = $siteOwner && $propagationKey === $view->renderObjectTemplate($this->propagationKeyFormat, $siteOwner);
                    }
                    break;
                default:
                    $include = true;
                    break;
            }

            if ($include) {
                $siteIds[] = $siteId;
            }
        }

        return $siteIds;
    }

    /**
     * Returns element index HTML for administering the nested elements.
     *
     * @param ElementInterface $owner
     * @param array $config
     * @return string
     */
    public function getIndexHtml(ElementInterface $owner, array $config = []): string
    {
        $config += [
            'allowedViewModes' => null,
            'pageSize' => 50,
            'sortable' => false,
            'canCreate' => false,
            'createAttributes' => null,
            'minElements' => null,
            'maxElements' => null,
        ];

        /** @var ElementInterface|string $elementType */
        $elementType = $this->elementType;

        if (!$owner->id) {
            $message = Craft::t('app', '{nestedType} can only be created after the {ownerType} has been saved.', [
                'nestedType' => $elementType::pluralDisplayName(),
                'ownerType' => $owner::lowerDisplayName(),
            ]);
            return Html::tag('div', $message, ['class' => 'pane hairline zilch small']);
        }

        Craft::$app->getSession()->authorize("editNestedElements::$owner->id::$this->attribute");

        $view = Craft::$app->getView();
        return $view->namespaceInputs(function() use ($elementType, $view, $owner, $config) {
            $id = sprintf('element-index-%s', mt_rand());

            $settings = [
                'indexSettings' => [
                    'namespace' => $view->getNamespace(),
                    'allowedViewModes' => $config['allowedViewModes']
                        ? array_map(fn($mode) => StringHelper::toString($mode), $config['allowedViewModes'])
                        : null,
                    'criteria' => array_merge([
                        $this->ownerIdParam => $owner->id,
                    ], $this->criteria),
                    'batchSize' => $config['pageSize'],
                    'sortable' => $config['sortable'],
                    'actions' => [],
                    'canHaveDrafts' => $elementType::hasDrafts(),
                ],
                'canCreate' => $config['canCreate'],
                'minElements' => $config['minElements'],
                'maxElements' => $config['maxElements'],
                'createButtonLabel' => $this->createButtonLabel,
                'baseCreateAttributes' => array_filter([
                    'elementType' => $elementType,
                    $this->ownerIdAttribute => $owner->id,
                    'siteId' => $elementType::isLocalized() ? $owner->siteId : null,
                ]),
            ];

            if (!empty($config['createAttributes'])) {
                $settings['createAttributes'] = $config['createAttributes'];
                if (count($settings['createAttributes']) === 1 && ArrayHelper::isIndexed($settings['createAttributes'])) {
                    $settings['createAttributes'] = ArrayHelper::firstValue($settings['createAttributes'])['params'];
                }
            }

            if ($config['sortable']) {
                $view->startJsBuffer();
                $config = ElementHelper::actionConfig(new ChangeSortOrder($owner, $this->attribute));
                $config['bodyHtml'] = $view->clearJsBuffer();
                $settings['indexSettings']['actions'][] = $config;
            }

            $view->registerJsWithVars(fn($id, $elementType, $settings, $reorderParams) => <<<JS
(() => {
  const settings = $settings;
  const index = new Craft.EmbeddedElementIndex('#' + $id, $elementType, Object.assign(settings, {
    indexSettings: Object.assign(settings.indexSettings, {
      onSortChange: (draggee) => {
        const elementIndex = index.elementIndex;
        const id = parseInt(draggee.find('.element').data('id'));
        const allIds = elementIndex.view.getAllElements()
           .toArray()
           .map(container => $(container).find('.element:first').data('id'))
           .filter(id => id)
           .map(id => parseInt(id));

        const data = Object.assign($reorderParams, {
          elementIds: [id],
          offset: (elementIndex.settings.batchSize * (elementIndex.page - 1)) + allIds.indexOf(id),
        });
        Craft.sendActionRequest('POST', 'nested-elements/reorder', {data})
          .then(({data}) => {
            Craft.cp.displayNotice(data.message);
          })
          .catch(({response}) => {
            Craft.cp.displayError(response.data && response.data.error);
          });
      },
    }),
  }));
})();
JS, [
                $view->namespaceInputId($id),
                $this->elementType,
                $settings,
                [
                    'ownerElementType' => $owner::class,
                    'ownerId' => $owner->id,
                    'ownerSiteId' => $owner->siteId,
                    'attribute' => $this->attribute,
                ],
            ]);

            return Cp::elementIndexHtml($this->elementType, [
                'context' => 'embedded-index',
                'id' => $id,
                'sources' => false,
                'registerJs' => false,
            ]);
        }, Html::id($this->attribute));
    }
}
