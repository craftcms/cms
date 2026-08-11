<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Field\Concerns;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Events\FieldHtmlResolving;
use CraftCms\Cms\Support\Html;

trait LegacyFieldHtml
{
    public function getInputHtml(mixed $value, ?ElementInterface $element): string
    {
        $html = $this->inputHtml($value, $element, false);

        event($event = new FieldHtmlResolving(
            field: $this,
            value: $value,
            inline: false,
            element: $element,
            html: $html,
        ));

        return $event->html;
    }

    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return Html::disableInputs(fn() => $this->getInputHtml($value, $element));
    }
}
