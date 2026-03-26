<?php

declare(strict_types=1);

namespace craft\gql;

use CraftCms\Cms\Support\Facades\Deprecator;
use Override;

/**
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Gql\ArgumentManager} instead.
 */
class ArgumentManager extends \CraftCms\Cms\Gql\ArgumentManager
{
    public const EVENT_DEFINE_GQL_ARGUMENT_HANDLERS = 'defineGqlArgumentHandlers';

    #[Override]
    public function prepareArguments(array $arguments): array
    {
        if (isset($arguments['relatedToAll'])) {
            Deprecator::log('graphql.arguments.relatedToAll', 'The `relatedToAll` argument has been deprecated. Use the `relatedTo` argument with the `["and", ...ids]` syntax instead.');
            $ids = (array)$arguments['relatedToAll'];
            $ids = array_map(fn($value) => ['element' => $value], $ids);
            $arguments['relatedTo'] = array_merge(['and'], $ids);
            unset($arguments['relatedToAll']);
        }

        return parent::prepareArguments($arguments);
    }
}
