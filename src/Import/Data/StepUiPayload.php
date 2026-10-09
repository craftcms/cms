<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Data;

use CraftCms\Cms\Ui\UiPayload;
use JsonSerializable;

/**
 * The response to a request for a draft import step's settings UI.
 *
 * `canMap` says whether the step has settled enough to be mapped, and `sourceError` why its
 * data source can't be used, if it can't.
 *
 * @since 6.0.0
 */
readonly class StepUiPayload implements JsonSerializable
{
    public function __construct(
        public UiPayload $ui,
        public bool $canMap,
        public ?string $sourceError,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'ui' => $this->ui,
            'canMap' => $this->canMap,
            'sourceError' => $this->sourceError,
        ];
    }
}
