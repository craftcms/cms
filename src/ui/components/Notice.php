<?php

namespace craft\ui\components;

use Craft;
use craft\helpers\Html;
use craft\ui\Component;
use craft\ui\concerns\HasId;
use craft\ui\concerns\HasLabel;
use yii\helpers\Markdown;

class Notice extends Component
{
    use HasId;
    use HasLabel;

    protected string $type = 'notice';

    /**
     *
     * @var string|null Message for the notice
     */
    protected ?string $message = null;

    public function message(?string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function type(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function render(): string
    {
        return
            Html::beginTag('p', [
                'id' => $this->getId(),
                'class' => [$this->getType(), 'has-icon'],
            ]) .
            Html::tag('span', '', [
                'class' => 'icon',
                'aria' => [
                    'hidden' => 'true',
                ],
            ]) .
            Html::tag('span', "{$this->getLabel()} ", [
                'class' => 'visually-hidden',
            ]) .
            Html::tag('span',
                preg_replace(
                    '/&amp;(\w+);/',
                    '&$1;',
                    Markdown::processParagraph(
                        Html::encodeInvalidTags($this->message)
                    )
                )
            ) .
            Html::endTag('p');
    }
}