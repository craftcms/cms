<?php

declare(strict_types=1);

namespace craft\mcp;

use craft\base\ElementInterface;
use craft\elements\Address;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\elements\User;
use Mcp\Capability\Formatter\ToolResultFormatter;
use Mcp\Schema\Content\ResourceLink;
use Mcp\Schema\Result\CallToolResult;

/**
 * @since 6.0.0
 */
readonly class ElementResourceLinks
{
    public const Templates = [
        Entry::class => 'craft://entries/{id}/sites/{siteId}',
        Asset::class => 'craft://assets/{id}/sites/{siteId}',
        Address::class => 'craft://addresses/{id}/sites/{siteId}',
        User::class => 'craft://users/{user}',
    ];

    /**
     * @param  array<string, mixed>  $data
     * @param  iterable<ElementInterface>  $elements  Authorized elements returned by the tool.
     */
    public function result(array $data, iterable $elements): CallToolResult
    {
        $content = (new ToolResultFormatter())->format($data);

        foreach ($elements as $element) {
            $template = self::Templates[$element::class] ?? null;

            if ($template === null) {
                continue;
            }

            $uri = strtr($template, [
                '{id}' => (string) $element->id,
                '{siteId}' => (string) $element->siteId,
                '{user}' => (string) $element->id,
            ]);

            $content[] = new ResourceLink(
                uri: $uri,
                name: (string) $element ?: $uri,
                mimeType: 'application/json',
            );
        }

        return new CallToolResult(content: $content, structuredContent: $data);
    }
}
