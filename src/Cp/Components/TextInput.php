<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Override;

class TextInput extends ScalarInput
{
    public static function formElementType(): string
    {
        return 'craft:text-input';
    }

    #[Override]
    protected function formElementProps(): array
    {
        return [
            'placeholder' => $this->portableText('placeholder', $this->placeholder),
        ];
    }
}
