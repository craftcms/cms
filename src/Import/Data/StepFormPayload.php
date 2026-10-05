<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Data;

use CraftCms\Cms\Form\FormPayload;
use JsonSerializable;

/**
 * The response to a request for a draft import step's settings form.
 *
 * `canMap` says whether the step has settled enough to be mapped, and `sourceError` why its
 * data source can't be used, if it can't.
 *
 * @since 6.0.0
 */
readonly class StepFormPayload implements JsonSerializable
{
    public function __construct(
        public FormPayload $form,
        public bool $canMap,
        public ?string $sourceError,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'form' => $this->form,
            'canMap' => $this->canMap,
            'sourceError' => $this->sourceError,
        ];
    }
}
