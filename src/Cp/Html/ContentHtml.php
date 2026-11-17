<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Html;

use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Markdown\MarkdownOptions;
use CraftCms\Cms\Support\Facades\Markdown;
use CraftCms\Cms\Support\Html;
use Illuminate\Container\Attributes\Singleton;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
#[Singleton]
readonly class ContentHtml
{
    /**
     * Returns the HTML for metadata lists, with a rule between each group of metadata that has anything to show.
     *
     * @param  array<string, mixed>  ...$groups  The metadata, with keys representing the labels. A value can be a list
     *                                           of values, which each get their own `<dd>`, such as a map below a link.
     */
    public function metadataHtml(array ...$groups): string
    {
        $lists = array_filter(array_map($this->metadataListHtml(...), $groups));

        return implode("\n".Html::tag('hr', '', ['class' => 'my-lg'])."\n", $lists);
    }

    /** @param array<string, mixed> $data */
    private function metadataListHtml(array $data): string
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
                    implode('', array_map(fn (mixed $value): string => Html::tag('dd', $value)."\n", is_array($value) ? $value : [$value])).
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
        return Html::decodeDoubles(Markdown::convert(Html::encodeInvalidTags($text), new MarkdownOptions(flavor: $flavor, indentedCode: false)));
    }
}
