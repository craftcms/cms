<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use craft\base\ElementInterface;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Cp\Html\PreviewHtml;
use CraftCms\Cms\Cp\Html\StatusHtml;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Field\ContentBlock as ContentBlockField;
use CraftCms\Cms\Field\Contracts\EagerLoadingFieldInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\PreviewableFieldInterface;
use CraftCms\Cms\Field\Exceptions\FieldNotFoundException;
use CraftCms\Cms\Field\Exceptions\InvalidFieldException;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Translation\Locale;
use DateTime;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use Stringable;
use Twig\Markup;

use function CraftCms\Cms\t;

#[Singleton]
readonly class ElementAttributeRenderer
{
    public function __construct(
        private Fields $fields,
    ) {}

    /**
     * Renders the HTML for a given element attribute in table and card views.
     *
     * @param  ElementInterface&Element  $element  The element instance
     * @param  string  $attribute  The attribute name
     * @return string|Stringable The rendered HTML
     */
    public function render(ElementInterface $element, string $attribute): string|Stringable
    {
        if (str_starts_with($attribute, 'contentBlock:')) {
            return $this->renderContentBlockAttribute($element, $attribute);
        }

        if (str_starts_with($attribute, 'generatedField:')) {
            return $this->renderGeneratedFieldAttribute($element, $attribute);
        }

        return match ($attribute) {
            'id' => (string) $element->getCanonicalId(),
            'uid' => $element->getCanonicalUid(),
            'ancestors' => $this->renderAncestorsAttribute($element),
            'parent' => $this->renderParentAttribute($element),
            'status' => app(StatusHtml::class)->componentStatusLabelHtml($element),
            'link' => $this->renderLinkAttribute($element),
            'uri' => $this->renderUriAttribute($element),
            'slug' => $this->renderSlugAttribute($element),
            'revisionNotes' => $this->renderRevisionNotesAttribute($element),
            'revisionCreator' => $this->renderRevisionCreatorAttribute($element),
            'drafts' => $this->renderDraftsAttribute($element),
            default => $this->renderCustomFieldOrDefault($element, $attribute),
        };
    }

    /**
     * Renders the inline input HTML for a given element attribute.
     *
     * @param  ElementInterface&Element  $element  The element instance
     * @param  string  $attribute  The attribute name
     * @return string|Stringable The rendered inline input HTML
     */
    public function renderInlineInput(ElementInterface $element, string $attribute): string|Stringable
    {
        $field = null;

        if (preg_match('/^field:(.+)/', $attribute, $matches)) {
            $fieldUid = $matches[1];
            $field = $element->getFieldLayout()?->getFieldByUid($fieldUid);
        } elseif (preg_match('/^fieldInstance:(.+)/', $attribute, $matches)) {
            $instanceUid = $matches[1];
            $layoutElement = $element->getFieldLayout()?->getElementByUid($instanceUid);
            if ($layoutElement instanceof CustomField) {
                try {
                    $field = $layoutElement->getField();
                } catch (FieldNotFoundException) {
                }
            }

            $field ??= $this->getFieldFromAlternativeLayouts($element, $instanceUid);
        }

        if ($field !== null) {
            if ($field instanceof InlineEditableFieldInterface) {
                $layoutElement = $field->layoutElement;
                /** @var CustomField $layoutElement */
                if ($layoutElement && $layoutElement->showInForm($element) && $layoutElement->editable($element)) {
                    if ($field instanceof EagerLoadingFieldInterface && $element->hasEagerLoadedElements($field->handle)) {
                        $value = $element->getEagerLoadedElements($field->handle);
                    } else {
                        try {
                            $value = $element->getFieldValue($field->handle);
                        } catch (InvalidFieldException) {
                            return '';
                        }
                    }

                    return $field->getInlineInputHtml($value, $element);
                }
            }

            return $element->getAttributeHtml($attribute);
        }

        return $this->render($element, $attribute);
    }

    public function attributeHtml(mixed $value): string
    {
        if ($value instanceof DateTime) {
            $formatter = I18N::getFormatter();

            return Html::tag('span', $formatter->asTimestamp($value, Locale::LENGTH_SHORT), [
                'title' => $formatter->asDatetime($value, Locale::LENGTH_SHORT),
            ]);
        }

        if (is_bool($value)) {
            if (! $value) {
                return '';
            }

            return Html::tag('span', '', [
                'class' => 'checkbox-icon',
                'role' => 'img',
                'title' => t('Enabled'),
                'aria' => [
                    'label' => t('Enabled'),
                ],
            ]);
        }

        if ($value instanceof Markup) {
            return (string) $value;
        }

        if (is_object($value) && ! $value instanceof Stringable) {
            return '';
        }

        return Html::encode(strip_tags((string) $value));
    }

    public function linkAttributeHtml(?string $url): string
    {
        return Html::beginTag('a', [
            'href' => $url,
            'rel' => 'noopener',
            'target' => '_blank',
            'title' => t('Visit webpage'),
            'aria-label' => t('View'),
        ]).
            Html::tag('span', Icons::svg('world'), [
                'class' => ['cp-icon', 'small', 'inline-flex'],
            ]).
            Html::endTag('a');
    }

    public function uriAttributeHtml(?string $value, ?string $url): string
    {
        return Html::a(Html::tag('span', $value, ['dir' => 'ltr']), $url, [
            'href' => $url,
            'rel' => 'noopener',
            'target' => '_blank',
            'class' => 'go',
            'title' => t('Visit webpage'),
        ]);
    }

    private function getSourceElement(ElementInterface $element): ElementInterface
    {
        return $element->isProvisionalDraft ? $element->getCanonical() : $element;
    }

    private function renderAncestorsAttribute(ElementInterface $element): string
    {
        $ancestors = $this->getSourceElement($element)->getAncestors();

        if (! $ancestors instanceof ElementCollection || $ancestors->isEmpty()) {
            return '';
        }

        $html = Html::beginTag('ul', ['class' => 'path']);
        foreach ($ancestors as $ancestor) {
            $html .= Html::tag('li', app(ElementHtml::class)->elementChipHtml($ancestor));
        }

        return $html.Html::endTag('ul');
    }

    private function renderParentAttribute(ElementInterface $element): string
    {
        $parent = $this->getSourceElement($element)->getParent();

        return $parent ? app(ElementHtml::class)->elementChipHtml($parent) : '';
    }

    private function renderLinkAttribute(ElementInterface $element): string
    {
        $sourceElement = $this->getSourceElement($element);

        if (ElementHelper::isDraftOrRevision($sourceElement)) {
            return '';
        }

        $url = $sourceElement->getUrl();

        return $url !== null ? $this->linkAttributeHtml($url) : '';
    }

    private function renderUriAttribute(ElementInterface $element): string
    {
        $sourceElement = $this->getSourceElement($element);

        if ($sourceElement->getIsDraft() && ElementHelper::isTempSlug($sourceElement->slug)) {
            return '';
        }

        $url = $sourceElement->getUrl();

        if ($url === null) {
            return '';
        }

        if ($sourceElement->getIsHomepage()) {
            $value = Html::tag('span', '', [
                'data-icon' => 'home',
                'title' => t('Homepage'),
            ]);
        } else {
            $find = ['/'];
            $replace = ['/<wbr>'];

            $wordSeparator = Cms::config()->slugWordSeparator;

            if ($wordSeparator) {
                $find[] = $wordSeparator;
                $replace[] = $wordSeparator.'<wbr>';
            }

            $value = str_replace($find, $replace, $sourceElement->uri);
        }

        return $this->uriAttributeHtml($value, $url);
    }

    private function renderSlugAttribute(ElementInterface $element): string
    {
        if ($element->getIsDraft() && ElementHelper::isTempSlug($element->slug)) {
            return '';
        }

        return Html::encode($element->slug);
    }

    private function renderRevisionNotesAttribute(ElementInterface $element): string
    {
        $revision = $this->getSourceElement($element)->getCurrentRevision();

        return $revision ? Html::encode($revision->revisionNotes) : '';
    }

    private function renderRevisionCreatorAttribute(ElementInterface $element): string
    {
        $revision = $this->getSourceElement($element)->getCurrentRevision();

        if (! $revision) {
            return '';
        }

        $creator = $revision->getRevisionCreator();

        return $creator ? app(ElementHtml::class)->elementChipHtml($creator) : '';
    }

    private function renderDraftsAttribute(ElementInterface $element): string
    {
        $sourceElement = $this->getSourceElement($element);

        if (! $sourceElement->hasEagerLoadedElements('drafts')) {
            return '';
        }

        $drafts = $sourceElement->getEagerLoadedElements('drafts')->all();

        foreach ($drafts as $draft) {
            /** @var ElementInterface $draft */
            $draft->setUiLabel($draft->draftName);
        }

        return app(PreviewHtml::class)->elementPreviewHtml(
            $drafts,
            showThumb: false,
            showDraftName: false,
        );
    }

    private function renderCustomFieldOrDefault(ElementInterface $element, string $attribute): string
    {
        if (preg_match('/^(field|fieldInstance):(.+)/', $attribute, $matches)) {
            $uid = $matches[2];
            if ($matches[1] === 'field') {
                $field = $this->fields->getFieldByUid($uid);
            } else {
                $layoutElement = $element->getFieldLayout()?->getElementByUid($uid);
                if ($layoutElement instanceof CustomField) {
                    try {
                        $field = $layoutElement->getField();
                    } catch (FieldNotFoundException) {
                    }
                }
                $field ??= $this->getFieldFromAlternativeLayouts($element, $uid) ?? null;
            }

            if (isset($field) && $field instanceof PreviewableFieldInterface) {
                if ($field instanceof EagerLoadingFieldInterface && $element->hasEagerLoadedElements($field->handle)) {
                    $value = $element->getEagerLoadedElements($field->handle);
                } else {
                    try {
                        $value = $element->getFieldValue($field->handle);
                    } catch (InvalidFieldException) {
                        return '';
                    }
                }

                return $field->getPreviewHtml($value, $element);
            }

            return '';
        }

        return $this->attributeHtml($element->$attribute);
    }

    private function renderContentBlockAttribute(ElementInterface $element, string $attribute): string|Stringable
    {
        $parts = explode('.', $attribute);
        $uid = Str::after(array_shift($parts), 'contentBlock:');
        $layoutElement = $element->getFieldLayout()?->getElementByUid($uid);

        if (! $layoutElement instanceof CustomField) {
            return '';
        }

        try {
            $field = $layoutElement->getField();
        } catch (FieldNotFoundException) {
            return '';
        }

        if (! $field instanceof ContentBlockField) {
            return '';
        }

        return $element->getFieldValue($field->handle)->getAttributeHtml(implode('.', $parts));
    }

    private function renderGeneratedFieldAttribute(ElementInterface $element, string $attribute): string
    {
        $uid = Str::after($attribute, 'generatedField:');

        return $element->getGeneratedFieldValues()[$uid] ?? '';
    }

    public function getFieldFromAlternativeLayouts(ElementInterface $element, string $layoutElementUid): ?FieldInterface
    {
        $currentLayout = $element->getFieldLayout();

        $handle = $this->fields->getLayoutsByType($element::class)
            ->filter(fn ($fieldLayout) => $fieldLayout->uid !== $currentLayout?->uid)
            ->flatMap(fn ($fieldLayout) => $fieldLayout->getCustomFields())
            ->first(fn ($field) => $field->layoutElement->uid === $layoutElementUid)
            ?->layoutElement->handle;

        if (! $handle) {
            return null;
        }

        return Collection::make($currentLayout?->getCustomFields() ?? [])
            ->first(fn ($field) => $field->layoutElement->handle === $handle);
    }
}
