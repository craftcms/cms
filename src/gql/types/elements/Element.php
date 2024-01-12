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
use craft\gql\ArgumentManager;
use craft\gql\base\ObjectType;
use craft\gql\interfaces\Element as ElementInterface;
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
            return $source->{'get' . ucfirst($fieldName)}(empty($arguments) ? false : $arguments);
        }

        if ($fieldName === 'siteHandle') {
            return $source->getSite()->handle;
        }

        if ($fieldName === 'revisionNotes') {
            $revision = $source->getCurrentRevision();
            if (!$revision) {
                return null;
            }

            /** @var RevisionBehavior|null $behavior */
            $behavior = $revision->getBehavior('revision');
            if (!$behavior) {
                return null;
            }
            return $behavior->revisionNotes;
        }

        return parent::resolve($source, $arguments, $context, $resolveInfo);
    }
}
