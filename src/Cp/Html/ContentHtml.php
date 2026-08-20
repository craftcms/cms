<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Html;

use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Support\Facades\Markdown;
use CraftCms\Cms\Support\Html;
use Illuminate\Container\Attributes\Singleton;

use function CraftCms\Cms\t;

#[Singleton]
readonly class ContentHtml
{
    /** @param array<string, mixed> $data */
    public function metadataHtml(array $data): string
    {
        $defs = [];

        foreach ($data as $label => $value) {
            $value = value($value);

            if ($value !== false) {
                $defs[] =
                    Html::beginTag('div', [
                        'class' => 'cp-metadata-list__item',
                    ]).
                    Html::tag('dt', Html::encode($label), ['class' => 'font-bold text-xs'])."\n".
                    Html::tag('dd', $value)."\n".
                    Html::endTag('div');
            }
        }

        if (empty($defs)) {
            return '';
        }

        return Html::tag('dl', implode("\n", $defs), [
            'class' => ['cp-metadata-list'],
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
        return Html::decodeDoubles(Markdown::parse(Html::encodeInvalidTags($text), $flavor));
    }
}
