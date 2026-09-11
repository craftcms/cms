<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\View\LegacyAssets\ConditionBuilderAsset;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;

class ConditionBuilderRenderer
{
    public function __construct(private readonly ConditionInterface $condition, private readonly bool $editable = true) {}

    public function render(): string
    {
        return Html::tag($this->condition->mainTag, $this->renderInner(), [
            'id' => $this->condition->id,
            'class' => 'condition-container',
        ]);
    }

    public function renderInner(bool $autofocusAddButton = false): string
    {
        app(InternalAssetRegistry::class)->register(ConditionBuilderAsset::class);

        return Html::tag('craft-condition-builder', '', [
            'data' => [
                'payload' => Json::encode(app(ConditionBuilder::class)->resolve($this->condition, $this->editable)),
                'name' => InputNamespace::namespaceInputName($this->condition->name),
                'editable' => $this->editable ? '1' : '0',
                'autofocus' => $autofocusAddButton ? '1' : '0',
            ],
        ]);
    }
}
