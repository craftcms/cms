<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Controls;

use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Ui\Contracts\ReadableValueControl;
use CraftCms\Cms\Ui\Contracts\WritableValueControl;
use CraftCms\Cms\Ui\ControlPayload;
use CraftCms\Cms\Ui\Controls\Concerns\HasLinkFieldSettings;
use CraftCms\Cms\Ui\Controls\Concerns\HasTextExpander;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use Illuminate\Support\Arr;

/**
 * A Markdown string Control. Its canonical value is a plain string or null;
 * Markdown is authored and previewed by the renderer, never accepted as HTML.
 *
 * @since 6.0.0
 */
class Markdown extends Control implements ReadableValueControl, WritableValueControl
{
    use HasLinkFieldSettings;
    use HasTextExpander;

    private int $rows = 8;

    private ?string $placeholder = null;

    private ?int $maxLength = null;

    /** @var list<string> */
    private array $toolbarButtons = [];

    private bool $showToolbar = true;

    private bool $showStats = false;

    /** @var list<string> */
    private array $assetSources = [];

    /** @var array<string, mixed>|null */
    private ?array $assetCondition = null;

    /** @var array{referenceElementId: int, referenceElementOwnerId: int|null, referenceElementSiteId: int|null}|null */
    private ?array $assetReferenceElement = null;

    private bool $assetAnyUploader = false;

    private ?int $uploadFolderId = null;

    private ?int $uploadSiteId = null;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, UiHtmlRenderer $renderer): string
    {
        $markdown = Html::tag('craft-markdown-field', Html::encode((string) ($value ?? '')), [
            'id' => $attributes['id'],
            'name' => $attributes['name'],
            'rows' => $control->props['rows'] ?? 8,
            'placeholder' => $control->props['placeholder'] ?? null,
            'max-length' => $control->props['maxLength'] ?? null,
            'toolbar-buttons' => Json::encode($control->props['toolbarButtons'] ?? []),
            'show-toolbar' => (bool) ($control->props['showToolbar'] ?? true) && $attributes['name'] !== null,
            'show-stats' => (bool) ($control->props['showStats'] ?? false),
            'link-types' => Json::encode($control->props['types'] ?? []),
            'show-link-label-field' => $control->props['showLabelField'] ?? false,
            'link-advanced-fields' => Json::encode($control->props['advancedFields'] ?? []),
            'asset-sources' => Json::encode($control->props['assetSources'] ?? []),
            'asset-condition' => isset($control->props['assetCondition']) ? Json::encode($control->props['assetCondition']) : null,
            'asset-reference-element' => isset($control->props['assetReferenceElement']) ? Json::encode($control->props['assetReferenceElement']) : null,
            'asset-any-uploader' => (bool) ($control->props['assetAnyUploader'] ?? false),
            'upload-folder-id' => $attributes['name'] !== null ? ($control->props['uploadFolderId'] ?? null) : null,
            'upload-site-id' => $control->props['uploadSiteId'] ?? null,
            'sanitize-html' => true,
            'disabled' => $attributes['name'] === null,
            'required' => $attributes['required'],
            'described-by' => $attributes['aria']['describedby'] ?? null,
            'aria' => [
                'invalid' => $attributes['aria']['invalid'] ?? null,
            ],
        ]);

        return $markdown.self::textExpanderHtml($control, $attributes);
    }

    public function component(): string
    {
        return 'craft:markdown';
    }

    public function rows(int $rows): static
    {
        $this->rows = $rows;

        return $this;
    }

    public function placeholder(?string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function maxLength(?int $maxLength): static
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    /** @param list<string> $toolbarButtons */
    public function toolbarButtons(array $toolbarButtons): static
    {
        $this->toolbarButtons = $toolbarButtons;

        return $this;
    }

    public function showToolbar(bool $showToolbar = true): static
    {
        $this->showToolbar = $showToolbar;

        return $this;
    }

    public function showStats(bool $showStats = true): static
    {
        $this->showStats = $showStats;

        return $this;
    }

    /**
     * Sets the asset sources the toolbar’s Asset button selects from. Without any, the button isn’t shown.
     *
     * @param  list<string>  $assetSources
     */
    public function assetSources(array $assetSources): static
    {
        $this->assetSources = $assetSources;

        return $this;
    }

    /** Sets the condition assets have to meet to be selected. */
    public function assetCondition(?ElementConditionInterface $condition): static
    {
        $this->assetCondition = $condition?->getConfig();

        return $this;
    }

    /**
     * Sets the element the asset condition is evaluated against, such as the element being edited, for rules that
     * refer to it.
     */
    public function assetReferenceElement(?ElementInterface $element): static
    {
        $this->assetReferenceElement = $element?->id ? [
            'referenceElementId' => $element->id,
            'referenceElementOwnerId' => $element instanceof NestedElementInterface ? $element->getOwnerId() : null,
            'referenceElementSiteId' => $element->siteId,
        ] : null;

        return $this;
    }

    /**
     * Sets whether assets can be selected whoever uploaded them, leaving the asset condition to decide which
     * ones the user can see.
     */
    public function assetAnyUploader(bool $assetAnyUploader = true): static
    {
        $this->assetAnyUploader = $assetAnyUploader;

        return $this;
    }

    /** Sets the folder pasted and dropped files are uploaded to. Without one, files aren’t uploaded. */
    public function uploadFolderId(?int $uploadFolderId): static
    {
        $this->uploadFolderId = $uploadFolderId;

        return $this;
    }

    /** Sets the site the reference tags of uploaded assets point to. */
    public function uploadSiteId(?int $uploadSiteId): static
    {
        $this->uploadSiteId = $uploadSiteId;

        return $this;
    }

    #[\Override]
    public function inputSchema(): array
    {
        return Arr::whereNotNull(['type' => 'string', 'description' => 'Markdown.', 'maxLength' => $this->maxLength]);
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return Arr::whereNotNull([
            'rows' => $this->rows,
            'placeholder' => $this->placeholder,
            'maxLength' => $this->maxLength,
            'toolbarButtons' => $this->toolbarButtons,
            'showToolbar' => $this->showToolbar,
            'showStats' => $this->showStats,
            'assetSources' => $this->assetSources,
            'assetCondition' => $this->assetCondition,
            'assetReferenceElement' => $this->assetReferenceElement,
            'assetAnyUploader' => $this->assetAnyUploader,
            'uploadFolderId' => $this->uploadFolderId,
            'uploadSiteId' => $this->uploadSiteId,
            ...$this->linkFieldSettingsProps(),
            ...$this->textExpanderProps(),
        ]);
    }
}
