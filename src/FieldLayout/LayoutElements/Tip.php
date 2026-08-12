<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Nodes\Callout;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\t;

class Tip extends BaseUiElement
{
    public const string STYLE_TIP = 'tip';

    public const string STYLE_WARNING = 'warning';

    public string $tip = '';

    /**
     * @var bool Whether the tip can be dismissed by user
     */
    public bool $dismissible = false;

    /**
     * @var self::STYLE_TIP|self::STYLE_WARNING The tip style (`tip` or `warning`)
     */
    public string $style = self::STYLE_TIP;

    public static function make(string $tip): static
    {
        return app(static::class)->tip($tip);
    }

    public function tip(string $tip): static
    {
        $this->tip = $tip;

        return $this;
    }

    public function dismissible(bool $dismissible = true): static
    {
        $this->dismissible = $dismissible;

        return $this;
    }

    public function warning(bool $warning = true): static
    {
        $this->style = $warning ? self::STYLE_WARNING : self::STYLE_TIP;

        return $this;
    }

    protected function selectorLabel(): string
    {
        $tip = trim($this->tip);

        if ($tip !== '') {
            return $this->tip;
        }

        return $this->_isTip() ? t('Tip') : t('Warning');
    }

    protected function selectorIcon(): ?string
    {
        return $this->_isTip() ? 'lightbulb' : 'triangle-exclamation';
    }

    #[Override]
    public function hasSettings(): bool
    {
        return true;
    }

    protected function settingsHtml(): ?string
    {
        return
            FormFields::textareaFieldHtml([
                'label' => $this->_isTip() ? t('Tip') : t('Warning'),
                'instructions' => t('Can contain Markdown formatting.'),
                'class' => ['nicetext'],
                'id' => 'tip',
                'name' => 'tip',
                'value' => $this->tip,
            ]).
            FormFields::lightswitchFieldHtml([
                'label' => t('Can be dismissed?'),
                'instructions' => t('Whether this can be dismissed by a user and not shown again.'),
                'id' => 'dismissible',
                'name' => 'dismissible',
                'on' => $this->dismissible,
            ]);
    }

    #[Override]
    public function formNode(FieldLayoutElementContext $context): ?Node
    {
        if (trim($this->tip) === '') {
            return null;
        }

        if (! $this->uid) {
            throw new InvalidArgumentException('Persisted Tip FieldLayout elements require stable UIDs.');
        }

        return Callout::make($this->uid, t($this->tip, category: 'site'))
            ->variant($this->_isTip() ? 'info' : 'warning')
            ->dismissible($this->dismissible)
            ->width($this->width);
    }

    /**
     * Returns whether this should have a tip style.
     */
    private function _isTip(): bool
    {
        return $this->style !== self::STYLE_WARNING;
    }
}
