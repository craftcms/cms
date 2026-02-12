<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\ElementHelper;
use craft\helpers\StringHelper;

/**
 * TitleField represents a Title field that can be included in field layouts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class TitleField extends TextField
{
    /**
     * @inheritdoc
     */
    public bool $mandatory = true;

    /**
     * @inheritdoc
     */
    public string $attribute = 'title';

    /**
     * @inheritdoc
     */
    public bool $translatable = true;

    /**
     * @inheritdoc
     */
    public ?int $maxlength = 255;

    /**
     * @inheritdoc
     */
    public bool $required = true;

    /**
     * @inheritdoc
     */
    public bool $autofocus = true;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // We didn't start removing autofocus from fields() until 3.5.6
        unset(
            $config['mandatory'],
            $config['attribute'],
            $config['translatable'],
            $config['maxlength'],
            $config['required'],
            $config['autofocus']
        );

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        $fields = parent::fields();
        unset(
            $fields['mandatory'],
            $fields['attribute'],
            $fields['translatable'],
            $fields['maxlength'],
            $fields['required'],
            $fields['autofocus']
        );
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('app', 'Title');
    }

    /**
     * @inheritdoc
     */
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (
            $element &&
            !$static &&
            (!isset($element->slug) || ElementHelper::isTempSlug($element->slug))
        ) {
            $view = Craft::$app->getView();

            $language = $element->getSite()->language;
            $charMap = $language !== Craft::$app->language
                ? StringHelper::asciiCharMap(true, $language)
                : null;

            $view->registerJsWithVars(fn($titleId, $slugId, $charMap) => <<<JS
(() => {
  const slugInput = $('#' + $slugId);
  if (slugInput.length && !slugInput.val().length) {
    new Craft.SlugGenerator($('#' + $titleId), slugInput, {
        charMap: $charMap,
    });
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
     * @inheritdoc
     */
    public function isCrossSiteCopyable(ElementInterface $element): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function actionMenuItems(?ElementInterface $element = null, bool $static = false): array
    {
        $items = [];

        if (Craft::$app->getUser()->getIsAdmin()) {
            $items[] = $this->copyAttributeAction();
        }

        return $items;
    }
}
