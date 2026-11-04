<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Data;

use CraftCms\Cms\Ui\UiPayload;
use Illuminate\Contracts\Support\Arrayable;

/**
 * How a field on a draft, revision, or nested element differs from what it was based on: text as a diff, and
 * anything else as read-only previews of the field, as the AI assistant shows its proposed changes.
 *
 * @implements Arrayable<string, mixed>
 *
 * @since 6.0.0
 */
readonly class FieldChangeComparison implements Arrayable
{
    /**
     * @param  string  $fromLabel  What the field is compared with, such as “Current” or “Revision 4”.
     * @param  string  $toLabel  What has the field, such as a draft’s name or “Revision 5”.
     * @param  bool  $hasBase  Whether there is anything to compare with, such as an earlier revision.
     * @param  array{current: string, proposed: string}|null  $diff
     * @param  array{current: UiPayload|null, proposed: UiPayload}|null  $preview  The current preview is `null` when
     *                                                                             there’s nothing to compare with.
     */
    public function __construct(
        public string $fromLabel,
        public string $toLabel,
        public bool $hasBase,
        public bool $unchanged,
        public ?array $diff = null,
        public ?array $preview = null,
    ) {}

    /**
     * @return array{fromLabel: string, toLabel: string, hasBase: bool, unchanged: bool, diff: array{current: string, proposed: string}|null, preview: array{current: UiPayload|null, proposed: UiPayload}|null}
     */
    public function toArray(): array
    {
        return [
            'fromLabel' => $this->fromLabel,
            'toLabel' => $this->toLabel,
            'hasBase' => $this->hasBase,
            'unchanged' => $this->unchanged,
            'diff' => $this->diff,
            'preview' => $this->preview,
        ];
    }
}
