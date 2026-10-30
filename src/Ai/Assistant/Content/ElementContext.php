<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant\Content;

use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * An element the user added to the AI assistant’s context, as they have it in their editor.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class ElementContext
{
    /**
     * @param  string  $id  The element’s id in prompts and proposals, such as `e1`.
     * @param  list<string>  $namespace  The namespace of the editor’s form, which proposed values are applied in.
     * @param  list<string>|null  $fields  The handles of the custom fields the model sees, or `null` for all of them.
     * @param  string|null  $ref  What the client calls the context item, which proposed changes are matched to.
     * @param  bool  $earlier  Whether an earlier message was sent with it, rather than the latest one.
     * @param  bool  $open  Whether it’s the element open in the user’s editor, which the model reads when it needs to.
     */
    public function __construct(
        public string $id,
        public ElementInterface $element,
        public array $namespace,
        public ?array $fields,
        public bool $editable,
        public ?string $ref = null,
        public bool $earlier = false,
        public bool $open = false,
    ) {}
}
