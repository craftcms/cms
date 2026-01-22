<?php

declare(strict_types=1);

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\elements\db\TagQuery;
use craft\elements\Tag;
use craft\gql\arguments\elements\Tag as TagArguments;
use craft\gql\interfaces\elements\Tag as TagInterface;
use craft\gql\resolvers\elements\Tag as TagResolver;
use craft\helpers\Gql;
use craft\helpers\Gql as GqlHelper;
use craft\models\GqlSchema;
use craft\models\TagGroup;
use craft\services\Gql as GqlService;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use DOMElement;
use GraphQL\Type\Definition\Type;
use Symfony\Component\DomCrawler\Crawler;
use function CraftCms\Cms\t;

/**
 * Tags represents a Tags field.
 *
 * @deprecated in 6.0.0
 */
final class Tags extends \CraftCms\Cms\Field\BaseRelationField
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function displayName(): string
    {
        return t('Tags', category: 'yii2-adapter');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function icon(): string
    {
        return 'tag';
    }

    /**
     * {@inheritdoc}
     */
    public static function elementType(): string
    {
        return Tag::class;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function defaultSelectionLabel(): string
    {
        return t('Add a tag', category: 'yii2-adapter');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function phpType(): string
    {
        return sprintf('\\%s|\\%s<\\%s>', TagQuery::class, ElementCollection::class, Tag::class);
    }

    /**
     * {@inheritdoc}
     */
    public bool $allowMultipleSources = false;

    /**
     * {@inheritdoc}
     */
    public bool $allowLimit = false;

    /**
     * @see _getTagGroupUid()
     */
    private string|false $_tagGroupUid;

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getSettingsHtml(): string
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
     * {@inheritdoc}
     */
    #[\Override]
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
            return Craft::$app->getView()->renderTemplate('yii2-adapter/_components/fieldtypes/Tags/input.twig',
                [
                    'elementType' => self::elementType(),
                    'id' => $this->getInputId(),
                    'describedBy' => $this->describedBy,
                    'labelId' => $this->getLabelId(),
                    'name' => $this->handle,
                    'elements' => $value,
                    'tagGroupId' => $tagGroup->id,
                    'targetSiteId' => $this->targetSiteId($element),
                    'sourceElementId' => $element?->id,
                    'selectionLabel' => $this->selectionLabel ? t($this->selectionLabel, category: 'site') : self::defaultSelectionLabel(),
                    'allowSelfRelations' => (bool) $this->allowSelfRelations,
                    'defaultPlacement' => $this->defaultPlacement,
                ]);
        }

        return '<p class="error">' . t('This field is not set to a valid source.') . '</p>';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function supportedViewModes(): array
    {
        return [
            'list' => t('List'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function includeInGqlSchema(GqlSchema $schema): bool
    {
        return Gql::canQueryTags($schema);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getContentGqlType(): array
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
     * {@inheritdoc}
     */
    #[\Override]
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
     */
    private function _getTagGroup(): ?TagGroup
    {
        $groupUid = $this->_getTagGroupUid();

        return $groupUid ? Craft::$app->getTags()->getTagGroupByUid($groupUid) : null;
    }

    /**
     * Returns the tag group ID this field is associated with.
     */
    private function _getTagGroupUid(): ?string
    {
        if (!isset($this->_tagGroupUid)) {
            if (preg_match('/^taggroup:([0-9a-f\-]+)$/', (string) $this->source, $matches)) {
                $this->_tagGroupUid = $matches[1];
            } else {
                $this->_tagGroupUid = false;
            }
        }

        return $this->_tagGroupUid ?: null;
    }
}
