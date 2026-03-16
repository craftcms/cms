<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Closure;
use Craft;
use craft\base\ElementInterface;
use craft\web\assets\codemirror\CodeMirrorAsset;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Data\JsonData;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json as JsonHelper;
use InvalidArgumentException;
use Override;
use yii\db\Schema;

use function CraftCms\Cms\t;

/**
 * Icon represents an icon picker field.
 */
class Json extends Field implements CrossSiteCopyableFieldInterface, MergeableFieldInterface
{
    #[Override]
    public static function displayName(): string
    {
        return 'JSON';
    }

    #[Override]
    public static function icon(): string
    {
        return 'brackets-curly';
    }

    #[Override]
    public static function phpType(): string
    {
        return 'array|null';
    }

    #[Override]
    public static function dbType(): string
    {
        return Schema::TYPE_JSON;
    }

    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): ?JsonData
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof JsonData) {
            return $value;
        }

        return new JsonData($value);
    }

    #[Override]
    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): ?JsonData
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

    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->_inputHtml($value, false);
    }

    #[Override]
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return $this->_inputHtml($value, true);
    }

    private function _inputHtml(?JsonData $value, bool $static): string
    {
        $id = $this->getInputId();

        Craft::$app->getView()->registerAssetBundle(CodeMirrorAsset::class);
        HtmlStack::jsWithVars(fn ($id, $static) => <<<JS
(() => {
  const textarea = document.getElementById($id)
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
    })
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
            InputNamespace::namespaceId($id),
            $static,
        ]);

        return Html::beginTag('div', ['class' => 'json-field']).
            Html::textarea($this->handle, $value?->getJson(true), [
                'id' => $id,
            ]).
            Html::endTag('div');
    }

    #[Override]
    public function getElementRules(ElementInterface $element): array
    {
        return [
            function ($attribute, ?JsonData $value, Closure $fail) {
                if (is_null($value)) {
                    return;
                }

                if (isset($value['__ERROR__'])) {
                    $fail(t('{attribute} must be valid JSON.', [
                        'attribute' => $this->getUiLabel(),
                    ]));
                }
            },
        ];
    }

    #[Override]
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        if ($value === null) {
            return '';
        }

        /** @var JsonData $value */
        return Html::tag('code', $value->getJson());
    }

    #[Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        return Html::tag('code', '{foo:"bar"}');
    }
}
