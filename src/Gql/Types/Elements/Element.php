<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Elements;

use Craft;
use CraftCms\Cms\Element\Contracts\ElementInterface as BaseElementInterface;
use CraftCms\Cms\Gql\ArgumentManager;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\Interfaces\Element as ElementInterface;
use CraftCms\Cms\Gql\Types\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use Override;

class Element extends ObjectType
{
    public function __construct(array $config)
    {
        if (! array_key_exists('interfaces', $config)) {
            $config['interfaces'] = [];
        }

        $config['interfaces'] = array_merge([ElementInterface::getType()], $config['interfaces']);

        parent::__construct($config);
    }

    #[Override]
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        /** @var BaseElementInterface $source */
        $fieldName = $resolveInfo->fieldName;

        if ($fieldName === Gql::GRAPHQL_COUNT_FIELD && ! empty($arguments['field'])) {
            return $source->getEagerLoadedElementCount($arguments['field']);
        }

        if (in_array($fieldName, ['prev', 'next'])) {
            // we need to prepare arguments for prev/next - otherwise registered argument handlers won't kick in for them
            /** @var ArgumentManager $argumentManager */
            $argumentManager = $context['argumentManager'] ?? Craft::createObject(['class' => ArgumentManager::class]);
            $arguments = $argumentManager->prepareArguments($arguments);

            return $source->{'get'.ucfirst($fieldName)}(empty($arguments) ? false : $arguments);
        }

        if ($fieldName === 'siteHandle') {
            return $source->getSite()->handle;
        }

        if ($fieldName === 'revisionNotes') {
            return $source->revisionNotes;
        }

        return parent::resolve($source, $arguments, $context, $resolveInfo);
    }
}
