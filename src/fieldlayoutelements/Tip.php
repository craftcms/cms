<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Cp;
use craft\helpers\Html;
use yii\helpers\Markdown;

/**
 * Tip represents an author tip UI element that can be included in field layouts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Tip extends BaseUiElement
{
    public const STYLE_TIP = 'tip';
    public const STYLE_WARNING = 'warning';

    /**
     * @var string The tip text
     */
    public string $tip = '';

    /**
     * @var bool Whether the tip can be dismissed by user
     * @since 4.4.0
     */
    public bool $dismissible = false;

    /**
     * @var string The tip style (`tip` or `warning`)
     * @phpstan-var self::STYLE_TIP|self::STYLE_WARNING
     */
    public string $style = self::STYLE_TIP;

    /**
     * @inheritdoc
     */
    protected function selectorLabel(): string
    {
        if ($this->tip) {
            return $this->tip;
        }

        return $this->_isTip() ? Craft::t('app', 'Tip') : Craft::t('app', 'Warning');
    }

    /**
     * @inheritdoc
     */
    protected function selectorIcon(): ?string
    {
        return '@appicons/' . ($this->_isTip() ? 'tip' : 'alert') . '.svg';
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): ?string
    {
        return
            Cp::textareaFieldHtml([
                'label' => $this->_isTip() ? Craft::t('app', 'Tip') : Craft::t('app', 'Warning'),
                'instructions' => Craft::t('app', 'Can contain Markdown formatting.'),
                'class' => ['nicetext'],
                'id' => 'tip',
                'name' => 'tip',
                'value' => $this->tip,
            ]) .
            Cp::lightswitchFieldHtml([
                'label' => Craft::t('app', 'Can be dismissed?'),
                'instructions' => Craft::t('app', 'Whether this can be dismissed by a user and not shown again.'),
                'id' => 'dismissible',
                'name' => 'dismissible',
                'on' => $this->dismissible,
            ]);
    }

    /**
     * @inheritdoc
     */
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$this->uid) {
            $this->dismissible = false;
        }

        $id = sprintf('tip%s', mt_rand());
        $namespacedId = Craft::$app->getView()->namespaceInputId($id);

        $classes = [$this->_isTip() ? self::STYLE_TIP : self::STYLE_WARNING];
        if ($this->dismissible) {
            $classes[] = 'dismissible';
        }

        $tip = Markdown::process(Html::encode(Craft::t('site', $this->tip)));
        $closeBtn = $this->dismissible
            ? Html::button('', [
                'class' => 'tip-dismiss-btn',
                'title' => Craft::t('app', 'Dismiss'),
                'aria' => [
                    'label' => Craft::t('app', 'Dismiss'),
                ],
                'data' => [
                    'icon' => 'remove',
                ],
            ])
            : '';

        if ($this->dismissible) {
            $key = sprintf('Craft-%s.dismissedTips', Craft::$app->getSystemUid());
            $js = <<<JAVASCRIPT
if (
  typeof localStorage !== 'undefined' &&
  typeof localStorage['$key'] !== 'undefined' &&
  JSON.parse(localStorage['$key']).includes('$this->uid')
) {
  document.getElementById('$namespacedId').remove();
}
JAVASCRIPT;
        } else {
            $js = null;
        }

        $html = "<div id=\"$id\" class=\"readable\">" .
            "<blockquote class=\"note " . implode(' ', $classes) . "\">" .
                $closeBtn .
                $tip .
            "</blockquote>" .
            '</div>';

        if ($js) {
            $html .= "<script>$js</script>";
        }

        return $html;
    }

    /**
     * Returns whether this should have a tip style.
     *
     * @return bool
     */
    private function _isTip(): bool
    {
        return $this->style !== self::STYLE_WARNING;
    }
}
