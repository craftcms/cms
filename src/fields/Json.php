<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\CrossSiteCopyableFieldInterface;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\MergeableFieldInterface;
use craft\fields\data\JsonData;
use craft\helpers\Html;
use craft\helpers\Json as JsonHelper;
use craft\web\assets\codemirror\CodeMirrorAsset;
use yii\base\InvalidArgumentException;
use yii\db\Schema;

/**
 * Icon represents an icon picker field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.7.0
 */
class Json extends Field implements MergeableFieldInterface, CrossSiteCopyableFieldInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'JSON';
    }

    /**
     * @inheritdoc
     */
    public static function icon(): string
    {
        return 'brackets-curly';
    }

    /**
     * @inheritdoc
     */
    public static function phpType(): string
    {
        return 'array|null';
    }

    /**
     * @inheritdoc
     */
    public static function dbType(): string
    {
        return Schema::TYPE_JSON;
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof JsonData) {
            return $value;
        }

        return new JsonData($value);
    }

    /**
     * @inheritdoc
     */
    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $value = JsonHelper::decode($value);
        } catch (InvalidArgumentException $e) {
            $value = [
                '__ERROR__' => $e->getMessage(),
                '__VALUE__' => $value,
            ];
        }

        return new JsonData($value);
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->_inputHtml($value, false);
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return $this->_inputHtml($value, true);
    }

    private function _inputHtml(?JsonData $value, bool $static): string
    {
        $id = $this->getInputId();

        $view = Craft::$app->getView();
        $view->registerAssetBundle(CodeMirrorAsset::class);
        $view->registerJsWithVars(fn($id, $static) => <<<JS
(() => {
  const textarea = document.getElementById($id);
  const init = () => {
    const editor = CodeMirror.fromTextArea(textarea, {
      mode: {
        name: 'javascript',
        json: true,
      },
      viewportMargin: Infinity,
      readOnly: $static,
      theme: [
        'default',
        $static ? 'readonly' : null,
      ].filter(v => v).join(' '),
    });
    editor.on('change', (editor) => {
      editor.save();
    });
  };

  const intersectionObserver = new IntersectionObserver((entries) => {
    // was the field just made visible?
    if (entries[0].intersectionRatio !== 0) {
      intersectionObserver.disconnect();
      init();
    }
  });
  intersectionObserver.observe(textarea);
})();
JS, [
            $view->namespaceInputId($id),
            $static,
        ]);

        return Html::beginTag('div', ['class' => 'json-field']) .
            Html::textarea($this->handle, $value?->getJson(true), [
                'id' => $id,
            ]) .
            Html::endTag('div');
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        return [
            [
                function(ElementInterface $element) {
                    /** @var JsonData|null $value */
                    $value = $element->getFieldValue($this->handle);
                    if (isset($value['__ERROR__'])) {
                        $element->addError("field:$this->handle", Craft::t('app', '{attribute} must be valid JSON.', [
                            'attribute' => $this->getUiLabel(),
                        ]));
                    }
                },
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        if ($value === null) {
            return '';
        }
        /** @var JsonData $value */
        return Html::tag('code', $value->getJson());
    }

    /**
     * @inheritdoc
     */
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        return Html::tag('code', '{foo:"bar"}');
    }
}
