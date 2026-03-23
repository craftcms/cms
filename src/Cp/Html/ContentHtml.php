<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Html;

use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Support\Html;
use Illuminate\Container\Attributes\Singleton;
use yii\helpers\Markdown;

use function CraftCms\Cms\t;

#[Singleton]
readonly class ContentHtml
{
    public function metadataHtml(array $data): string
    {
        $defs = [];

        foreach ($data as $label => $value) {
            $value = value($value);

            if ($value !== false) {
                $defs[] =
                    Html::beginTag('div', [
                        'class' => 'data',
                    ]).
                    Html::tag('dt', Html::encode($label), ['class' => 'heading'])."\n".
                    Html::tag('dd', $value, ['class' => 'value'])."\n".
                    Html::endTag('div');
            }
        }

        if (empty($defs)) {
            return '';
        }

        return Html::tag('dl', implode("\n", $defs), [
            'class' => ['meta', 'read-only'],
        ]);
    }

    public function readOnlyNoticeHtml(): string
    {
        return
            Html::beginTag('div', [
                'class' => 'content-notice',
            ]).
            Html::beginTag('div', [
                'class' => ['content-notice-icon'],
                'aria' => ['hidden' => 'true'],
            ]).
            Html::tag('div', Icons::svg('gear-slash'), [
                'class' => 'cp-icon',
            ]).
            Html::endTag('div').
            Html::tag('p', t('Changes to these settings aren’t permitted in this environment.')).
            Html::endTag('div');
    }

    public function parseMarkdown(string $text, string $flavor = 'gfm-comment'): string
    {
        return Html::decodeDoubles(Markdown::process(Html::encodeInvalidTags($text), $flavor));
    }
}
