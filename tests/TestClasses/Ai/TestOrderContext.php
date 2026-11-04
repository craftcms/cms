<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Ai;

use CraftCms\Cms\Ai\Contracts\AssistantContextTypeInterface;
use CraftCms\Cms\Ai\Data\AssistantContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request as ToolRequest;

/**
 * A context type as a plugin would add one: orders, with a tool that works with them.
 */
class TestOrderContext implements AssistantContextTypeInterface
{
    public static function handle(): string
    {
        return 'test:order';
    }

    public function resolve(Request $request, array $items): ?AssistantContext
    {
        $ids = array_filter(array_map(static fn (array $item): mixed => $item['orderId'] ?? null, $items), is_int(...));

        if ($ids === []) {
            return null;
        }

        return new AssistantContext(
            implode("\n", array_map(static fn (int $id): string => "Order #$id", $ids)),
            [new class implements Tool
            {
                public function name(): string
                {
                    return 'refund_order';
                }

                public function description(): string
                {
                    return 'Refunds an order in the context.';
                }

                public function handle(ToolRequest $request): string
                {
                    return 'Refunded.';
                }

                public function schema(JsonSchema $schema): array
                {
                    return [];
                }
            }],
        );
    }
}
