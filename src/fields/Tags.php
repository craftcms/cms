<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\TagQuery;
use craft\elements\ElementCollection;
use craft\elements\Tag;
use craft\gql\arguments\elements\Tag as TagArguments;
use craft\gql\interfaces\elements\Tag as TagInterface;
use craft\gql\resolvers\elements\Tag as TagResolver;
use craft\helpers\Gql;
use craft\helpers\Gql as GqlHelper;
use craft\models\GqlSchema;
use craft\models\TagGroup;
use craft\services\Gql as GqlService;
use DOMElement;
use GraphQL\Type\Definition\Type;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tags represents a Tags field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Tags extends BaseRelationField
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Tags');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): string
    {
        return 'tag';
    }

    /**
     * @inheritdoc
     */
    public static function elementType(): string
    {
        return Tag::class;
    }

    /**
     * @inheritdoc
     */
    public static function defaultSelectionLabel(): string
    {
        return Craft::t('app', 'Add a tag');
    }

    /**
     * @inheritdoc
     */
    public static function phpType(): string
    {
        return sprintf('\\%s|\\%s<\\%s>', TagQuery::class, ElementCollection::class, Tag::class);
    }

    /**
     * @inheritdoc
     */
    public bool $allowMultipleSources = false;

    /**
     * @inheritdoc
     */
    public bool $allowLimit = false;

    /**
     * @var string|false
     * @see _getTagGroupUid()
     */
    private string|false $_tagGroupUid;

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        $html = parent::getSettingsHtml();

        // Remove the “Show the search input” field
        $crawler = new Crawler("<html><body>$html</body></html>");
        /** @var DOMElement $node */
        $node = $crawler->filter('#show-search-input-field')->getNode(0);
        $node->remove();

        return $crawler->filter('body')->first()->html();
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        if ($element !== null && $element->hasEagerLoadedElements($this->handle)) {
            $value = $element->getEagerLoadedElements($this->handle)->all();
        }

        if ($value instanceof ElementQueryInterface) {
            $value = $value
                ->status(null)
                ->all();
        } elseif (!is_array($value)) {
            $value = [];
        }

        $tagGroup = $this->_getTagGroup();

        if ($tagGroup) {
            return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Tags/input.twig',
                [
                    'elementType' => static::elementType(),
                    'id' => $this->getInputId(),
                    'describedBy' => $this->describedBy,
                    'labelId' => $this->getLabelId(),
                    'name' => $this->handle,
                    'elements' => $value,
                    'tagGroupId' => $tagGroup->id,
                    'targetSiteId' => $this->targetSiteId($element),
                    'sourceElementId' => $element?->id,
                    'selectionLabel' => $this->selectionLabel ? Craft::t('site', $this->selectionLabel) : static::defaultSelectionLabel(),
                    'allowSelfRelations' => (bool)$this->allowSelfRelations,
                    'defaultPlacement' => $this->defaultPlacement,
                ]);
        }

        return '<p class="error">' . Craft::t('app', 'This field is not set to a valid source.') . '</p>';
    }

    /**
     * @inheritdoc
     */
    protected function supportedViewModes(): array
    {
        return [
            'list' => Craft::t('app', 'List'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function includeInGqlSchema(GqlSchema $schema): bool
    {
        return Gql::canQueryTags($schema);
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getContentGqlType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => Type::nonNull(Type::listOf(TagInterface::getType())),
            'args' => TagArguments::getArguments(),
            'resolve' => TagResolver::class . '::resolve',
            'complexity' => GqlHelper::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
        ];
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getEagerLoadingGqlConditions(): ?array
    {
        $allowedEntities = Gql::extractAllowedEntitiesFromSchema();
        $tagGroupUids = $allowedEntities['taggroups'] ?? [];

        if (empty($tagGroupUids)) {
            return null;
        }

        $tagsService = Craft::$app->getTags();
        $tagGroupIds = array_filter(array_map(function(string $uid) use ($tagsService) {
            $tagGroup = $tagsService->getTagGroupByUid($uid);
            return $tagGroup->id ?? null;
        }, $tagGroupUids));

        return [
            'groupId' => $tagGroupIds,
        ];
    }

    /**
     * Returns the tag group associated with this field.
     *
     * @return TagGroup|null
     */
    private function _getTagGroup(): ?TagGroup
    {
        $groupUid = $this->_getTagGroupUid();
        return $groupUid ? Craft::$app->getTags()->getTagGroupByUid($groupUid) : null;
    }

    /**
     * Returns the tag group ID this field is associated with.
     *
     * @return string|null
     */
    private function _getTagGroupUid(): ?string
    {
        if (!isset($this->_tagGroupUid)) {
            if (preg_match('/^taggroup:([0-9a-f\-]+)$/', $this->source, $matches)) {
                $this->_tagGroupUid = $matches[1];
            } else {
                $this->_tagGroupUid = false;
            }
        }

        return $this->_tagGroupUid ?: null;
    }
}
