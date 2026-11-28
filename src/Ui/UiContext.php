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
     * @param  list<string>  $requiredScope  A scope the response has to show in full, such as the one a refresh asked
     *                                       for. Controls always build the nested UIs on its path. Those inside it can
     *                                       still be placeholders.
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
        public array $requiredScope = [],
    ) {
        $this->mode = is_string($mode) ? ControlMode::from($mode) : $mode;
    }

    /**
     * A copy of this context with the given properties changed, such as `$context->with(errors: $errors)`.
     */
    public function with(mixed ...$properties): static
    {
        return clone ($this, $properties);
    }

    /**
     * A context for a nested UI at `$namespace`, such as a Matrix block’s field layout. It keeps the settings that
     * apply to the whole request, and leaves out this context’s values and errors.
     *
     * @param  list<string>  $namespace
     */
    public function nested(array $namespace, ControlMode $mode = ControlMode::Editable): self
    {
        return new self(
            namespace: $namespace,
            mode: $mode,
            preview: $this->preview,
            reuseUnchanged: $this->reuseUnchanged,
            requiredScope: $this->requiredScope,
        );
    }
}
