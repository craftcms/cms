<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\FieldLayout\LayoutElements;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use CraftCms\Cms\Support\Html as HtmlHelper;
use CraftCms\Yii2Adapter\FieldLayout\FieldLayoutElement;

class Html extends FieldLayoutElement
{
    /** @param array<string, mixed> $config */
    public function __construct(private readonly string $html, array $config = [])
    {
        parent::__construct($config);
    }

    /** @throws NotSupportedException */
    public function selectorHtml(): string
    {
        throw new NotSupportedException(sprintf('%s should not be included in user-modifyable field layouts.', self::class));
    }

    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return HtmlHelper::tag('div', $this->html);
    }
}
