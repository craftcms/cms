<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\ElementHelper;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\Auth;
use Override;

use function CraftCms\Cms\t;

class TitleField extends TextField
{
    /**
     * {@inheritdoc}
     */
    public bool $mandatory = true;

    /**
     * {@inheritdoc}
     */
    public string $attribute = 'title';

    /**
     * {@inheritdoc}
     */
    public bool $translatable = true;

    /**
     * {@inheritdoc}
     */
    public ?int $maxlength = 255;

    /**
     * {@inheritdoc}
     */
    public bool $required = true;

    /**
     * {@inheritdoc}
     */
    public bool $autofocus = true;

    /**
     * {@inheritdoc}
     */
    public function __construct($config = [])
    {
        // We didn't start removing autofocus from fields() until 3.5.6
        parent::__construct(Arr::except($config, [
            'mandatory',
            'attribute',
            'translatable',
            'maxlength',
            'required',
            'autofocus',
        ]));
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function fields(): array
    {
        return Arr::except(parent::fields(), [
            'mandatory',
            'attribute',
            'translatable',
            'maxlength',
            'required',
            'autofocus',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return t('Title');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (
            $element &&
            ! $static &&
            (! isset($element->slug) || ElementHelper::isTempSlug($element->slug))
        ) {
            $view = Craft::$app->getView();

            $language = $element->getSite()->getLanguage();
            $charMap = $language !== app()->getLocale()
                ? Str::asciiCharMap(true, $language)
                : null;

            $view->registerJsWithVars(fn ($titleId, $slugId, $charMap) => <<<JS
(() => {
  const slugInput = $('#' + $slugId);
  if (slugInput.length && !slugInput.val().length) {
    new Craft.SlugGenerator($('#' + $titleId), slugInput, {
        charMap: $charMap,
    })
  }
})();
JS, [
                $view->namespaceInputId($this->id()),
                $view->namespaceInputId('slug'),
                $charMap,
            ]);
        }

        return parent::formHtml($element, $static);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function isCrossSiteCopyable(ElementInterface $element): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function actionMenuItems(?ElementInterface $element = null, bool $static = false): array
    {
        $items = [];

        if (Auth::user()?->isAdmin()) {
            $items[] = $this->copyAttributeAction();
        }

        return $items;
    }
}
