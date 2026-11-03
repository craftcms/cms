<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use function CraftCms\Cms\t;

/**
 * An element action the control panel performs in the browser.
 *
 * Choosing it dispatches `$event` on the window, with the selected elements'
 * IDs and type merged into `$detail`, for a listener on the page to handle.
 * Nothing is performed on the server, so unlike {@see ElementAction}, there's
 * no class to write.
 *
 * ```php
 * $actions[] = new ClientAction(
 *     key: 'my-plugin:open-in-figma',
 *     label: t('Open in Figma'),
 *     event: 'my-plugin:open-in-figma',
 *     bulk: false,
 * );
 * ```
 *
 * @since 6.0.0
 */
final readonly class ClientAction
{
    /**
     * @param  string  $key  Identifies the action among the index's actions.
     * @param  string  $event  The window event the action dispatches.
     * @param  array<string, mixed>  $detail  Extra detail for the event.
     * @param  bool  $bulk  Whether the action can be performed on more than one element at once.
     * @param  string|null  $selectionAttribute  A capability every selected element needs for the action, such as `hasUrl`.
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $event,
        public array $detail = [],
        public bool $bulk = true,
        public bool $destructive = false,
        public ?string $selectionAttribute = null,
    ) {}

    /**
     * Copies the selected elements, for pasting into nested element fields.
     */
    public static function copy(): self
    {
        return new self(
            key: 'copy',
            label: t('Copy'),
            event: 'craft:copy-elements',
            selectionAttribute: 'copyable',
        );
    }

    /**
     * Opens the selected element's editor.
     */
    public static function edit(?string $label = null): self
    {
        return new self(
            key: 'edit',
            label: $label ?? t('Edit'),
            event: 'craft:edit-element',
            bulk: false,
        );
    }

    /**
     * Opens the selected element's URL.
     */
    public static function view(?string $label = null): self
    {
        return new self(
            key: 'view',
            label: $label ?? t('View'),
            event: 'craft:view-element',
            bulk: false,
        );
    }

    /**
     * The action's descriptor for the control panel's bulk actions bar.
     *
     * @return array<string, mixed>
     */
    public function toItem(): array
    {
        return array_filter([
            'key' => $this->key,
            'label' => $this->label,
            'destructive' => $this->destructive,
            'variant' => $this->destructive ? 'danger' : null,
            'bulk' => $this->bulk ? null : false,
            'selectionAttribute' => $this->selectionAttribute,
            'action' => array_filter([
                'type' => 'event',
                'name' => $this->event,
                'detail' => $this->detail ?: null,
            ], fn (mixed $value): bool => $value !== null),
        ], fn (mixed $value): bool => $value !== null);
    }
}
