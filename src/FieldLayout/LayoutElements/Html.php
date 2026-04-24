<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use CraftCms\Cms\Support\Html as HtmlHelper;

class Html extends FieldLayoutElement
{
    public function __construct(private readonly string $html, array $config = [])
    {
        parent::__construct($config);
    }

    /**
     * @throws NotSupportedException
     */
    public function selectorHtml(): string
    {
        throw new NotSupportedException(sprintf('%s should not be included in user-modifyable field layouts.', self::class));
    }

    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return HtmlHelper::tag('div', $this->html);
    }
}
