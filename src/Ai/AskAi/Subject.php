<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\AskAi;

use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * An element whose fields “Ask AI” can work on: the element being edited, or an element nested inside it whose fields
 * are edited inline, such as a Matrix block.
 *
 * @since 6.0.0
 */
readonly class Subject
{
    /**
     * @param  list<string>  $path  Where the element’s values are in the editor’s form, relative to the edited element’s.
     * @param  string  $keyPrefix  What the keys of the element’s fields start with in prompts and responses, such as `body.2.`.
     * @param  string|null  $label  What the element is called before its fields’ labels, such as “Body › 2. Intro”.
     * @param  string|null  $description  How the element is described to the model.
     * @param  bool  $editable  Whether the field that holds the element can be edited.
     */
    public function __construct(
        public ElementInterface $element,
        public array $path = [],
        public string $keyPrefix = '',
        public ?string $label = null,
        public ?string $description = null,
        public bool $editable = true,
    ) {}

    public function isNested(): bool
    {
        return $this->path !== [];
    }
}
