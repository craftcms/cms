<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Cp\Forms;

use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\FieldLayoutFormElementContext;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Yii2Adapter\Cp\Components\LegacySettings;

class LegacyFieldLayoutElementProjector
{
    public function project(
        FieldLayoutElement $layoutElement,
        FieldLayoutFormElementContext $context,
    ): ?LegacySettings {
        $fragment = HtmlStack::capture(fn (): string => InputNamespace::namespaceInputs(
            fn (): string => $layoutElement->formHtml($context->element, $context->readOnly) ?? '',
            $context->inputNamespace,
        ));

        return $fragment->isEmpty() ? null : LegacySettings::make($fragment);
    }
}
