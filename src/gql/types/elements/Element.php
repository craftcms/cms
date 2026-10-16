<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\elements;

use Craft;
use craft\base\ElementInterface as BaseElementInterface;
use craft\behaviors\RevisionBehavior;
use craft\elements\db\ElementQuery;
use craft\gql\ArgumentManager;
use craft\gql\base\ElementResolver;
use craft\gql\base\ObjectType;
use craft\gql\interfaces\Element as ElementInterface;
use craft\helpers\ElementHelper;
use craft\services\Gql;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class Element
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Element extends ObjectType
{
    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        if (!array_key_exists('interfaces', $config)) {
            $config['interfaces'] = [];
        }

        $config['interfaces'] = array_merge([ElementInterface::getType()], $config['interfaces']);

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        /** @var BaseElementInterface $source */
        $fieldName = $resolveInfo->fieldName;

        if ($fieldName === Gql::GRAPHQL_COUNT_FIELD && !empty($arguments['field'])) {
            return $source->getEagerLoadedElementCount($arguments['field']);
        }

        if (in_array($fieldName, ['prev', 'next'])) {
            // we need to prepare arguments for prev/next - otherwise registered argument handlers won't kick in for them
            /** @var ArgumentManager $argumentManager */
            $argumentManager = $context['argumentManager'] ?? Craft::createObject(['class' => ArgumentManager::class]);
            $arguments = $argumentManager->prepareArguments($arguments);

            // With no criteria, getPrev()/getNext() fall back to the cached sibling from the
            // (already-scoped) result set the element was populated from, so that's safe as-is.
            // Criteria rebuilds an entirely new query from scratch, so route that through this
            // type's own GQL resolver to keep it scoped to what the active schema can read.
            // Types that haven't declared a resolver keep the old, unscoped behavior.
            $resolverClass = empty($arguments) ? null : static::elementResolverClass();

            if ($resolverClass === null) {
                return $source->{'get' . ucfirst($fieldName)}(empty($arguments) ? false : $arguments);
            }

            // Match the query-building keys getPrev()/getNext() have always silently ignored
            // when given a plain criteria array (see Element::_getRelativeElement()), so this
            // doesn't newly enable arguments (like `orderBy`) that were previously no-ops here.
            $query = $resolverClass::prepareRootQuery(ElementHelper::cleanseQueryCriteria($arguments));

            if (!$query instanceof ElementQuery) {
                return null;
            }

            $query->siteId($source->siteId);
            return $source->{'get' . ucfirst($fieldName)}($query);
        }

        if ($fieldName === 'siteHandle') {
            return $source->getSite()->handle;
        }

        if ($fieldName === 'revisionNotes') {
            /** @var RevisionBehavior|null $behavior */
            $behavior = $source->getBehavior('revision') ?? $source->getCurrentRevision()?->getBehavior('revision');
            return $behavior?->revisionNotes;
        }

        return parent::resolve($source, $arguments, $context, $resolveInfo);
    }

    /**
     * Returns the [[ElementResolver]] class that should be used to build a schema-scoped
     * element query for this type’s `prev` and `next` fields.
     *
     * Subclasses that add `prev`/`next` fields to their GraphQL interface (see
     * [[\craft\gql\interfaces\elements\Entry]] for an example) should override this to
     * return the [[ElementResolver]] class used to resolve their own top-level queries,
     * so element navigation stays scoped to what the active schema is allowed to read.
     *
     * Subclasses that don’t override this (the default) fall back to the unscoped
     * `getPrev()`/`getNext()` behavior, for backwards compatibility with element types
     * that predate this method.
     *
     * @return class-string<ElementResolver>|null
     */
    protected static function elementResolverClass(): ?string
    {
        return null;
    }
}
