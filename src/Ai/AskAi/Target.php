<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\AskAi;

use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;

/**
 * A field that “Ask AI” can send to the model, and write when it’s editable.
 *
 * @since 6.0.0
 */
readonly class Target
{
    /**
     * @param  string  $key  The field’s key within its element: a custom field’s handle, or a native field’s attribute.
     * @param  mixed  $value  The field’s current value, as the model sees it.
     * @param  bool  $writable  Whether the model may change the field’s value.
     */
    public function __construct(
        public Subject $subject,
        public BaseField $field,
        public string $key,
        public mixed $value,
        public bool $writable,
    ) {}

    /**
     * Returns the field’s key in prompts and responses, such as `summary` or `body.2.heading`.
     */
    public function id(): string
    {
        return $this->subject->keyPrefix.$this->key;
    }

    /**
     * Returns the path of the field’s control in the editor’s form, relative to the edited element’s, joined by dots.
     */
    public function path(): string
    {
        return implode('.', [
            ...$this->subject->path,
            ...($this->field instanceof CustomField ? ['fields', $this->key] : [$this->key]),
        ]);
    }

    /**
     * Returns the field’s label, after its element’s for nested fields.
     */
    public function label(): string
    {
        $label = $this->field->label() ?? $this->key;

        return $this->subject->label !== null ? "{$this->subject->label} › $label" : $label;
    }
}
