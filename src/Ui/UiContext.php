<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui;

use CraftCms\Cms\Ui\Enums\ControlMode;

/**
 * @since 6.0.0
 */
readonly class UiContext
{
    public ControlMode $mode;

    /**
     * @param  string|list<string>  $namespace
     * @param  array<string, mixed>  $values
     * @param  array<string, string|list<string>>  $errors
     * @param  list<string>  $globalErrors
     * @param  bool  $preview  Whether the UI previews values on their own, such as “Compare changes” and the AI
     *                         assistant’s proposed changes, rather than being part of a form.
     * @param  bool  $reuseUnchanged  Whether the client keeps the nested UIs it already shows, so a control can send
     *                                a placeholder for each nested UI this request didn't change instead of building
     *                                it again. See {@see NestedUiPayload::$reused}.
     */
    public function __construct(
        public string|array $namespace = [],
        public array $values = [],
        public array $errors = [],
        public array $globalErrors = [],
        ControlMode|string $mode = ControlMode::Editable,
        public bool $refreshable = false,
        public bool $preview = false,
        public bool $reuseUnchanged = false,
    ) {
        $this->mode = is_string($mode) ? ControlMode::from($mode) : $mode;
    }
}
