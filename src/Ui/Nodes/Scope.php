<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Nodes;

use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiPayload;

/**
 * Prefixes child Control paths without adding presentation markup.
 *
 * @since 6.0.0
 */
class Scope extends Container
{
    /** @var string|list<string>|null */
    private string|array|null $deltaGroup = null;

    /**
     * @param  string|list<string>  $path
     * @param  list<Node>  $children
     */
    private function __construct(
        private readonly string|array $path,
        array $children,
        string $uid,
    ) {
        parent::__construct($uid, $children);
    }

    /**
     * @param  string|list<string>  $path
     * @param  list<Node>  $children
     */
    public static function make(string|array $path, array $children = [], ?string $uid = null): self
    {
        return new self($path, $children, $uid ?? 'scope:'.Json::encode($path, JSON_THROW_ON_ERROR));
    }

    /** @return string|list<string> */
    public function path(): string|array
    {
        return $this->path;
    }

    /**
     * Groups child changes under a path relative to this scope.
     * Use deltaGroupAtNamespace() to submit the scoped settings together.
     *
     * @param  string|list<string>|null  $path
     */
    public function deltaGroup(string|array|null $path): static
    {
        $this->deltaGroup = $path;

        return $this;
    }

    /** Submits all child changes together at this scope's namespace, including its path prefix. */
    public function deltaGroupAtNamespace(): static
    {
        return $this->deltaGroup([]);
    }

    /** @return string|list<string>|null */
    public function getDeltaGroup(): string|array|null
    {
        return $this->deltaGroup;
    }

    public function component(): string
    {
        return 'craft:scope';
    }

    public function props(): array
    {
        return [];
    }

    public static function renderHtml(NodePayload $node, UiPayload $payload, UiHtmlRenderer $renderer): string
    {
        return $renderer->renderNodes($node->children ?? [], $payload);
    }
}
