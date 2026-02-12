<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use craft\helpers\Cp;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Override;

use function CraftCms\Cms\t;

final class MyDrafts extends Widget
{
    #[Override]
    public static function displayName(): string
    {
        return t('My Drafts');
    }

    #[Override]
    protected static function allowMultipleInstances(): bool
    {
        return false;
    }

    /**
     * @var int The total number of drafts that the widget should show
     */
    public int $limit = 10;

    #[Override]
    public static function icon(): string
    {
        return 'scribble';
    }

    #[Override]
    public function getRules(): array
    {
        return [
            'limit' => ['required', 'integer', 'min:1'],
        ];
    }

    #[Override]
    public function getSettingsHtml(): string
    {
        return Cp::textFieldHtml([
            'label' => t('Limit'),
            'id' => 'limit',
            'name' => 'limit',
            'value' => $this->limit,
            'size' => 2,
            'errors' => Session::get('errors.limit', []),
        ]);
    }

    #[Override]
    public function getBodyHtml(): string
    {
        /** @var \CraftCms\Cms\Element\ElementCollection<Entry> $drafts */
        $drafts = Entry::find()
            ->drafts()
            ->status(null)
            ->draftCreator(Auth::user()->id)
            ->section('*')
            ->site('*')
            ->unique()
            ->orderByDesc('dateUpdated')
            ->limit($this->limit)
            ->get();

        if ($drafts->isEmpty()) {
            return Html::tag('div', t('You don’t have any active drafts.'), [
                'class' => ['zilch', 'small'],
            ]);
        }

        $html = Html::beginTag('ul', [
            'class' => 'widget__list chips',
            'role' => 'list',
        ]);

        foreach ($drafts as $draft) {
            $html .= Html::tag('li', Cp::elementChipHtml($draft), [
                'class' => 'widget__list-item',
            ]);
        }

        return $html.Html::endTag('ul');
    }
}
