<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Form\Controls\Number;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Support\Html;
use Override;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

class MyDrafts extends Widget
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
    public function settingsForm(FormContext $context = new FormContext): Form
    {
        return Form::make([
            Field::make(t('Limit'))
                ->required()
                ->control(Number::make('limit')->value($this->limit)->min(1)->size(2)),
        ]);
    }

    #[Override]
    public function getBodyHtml(): string
    {
        $drafts = Entry::find()
            ->drafts()
            ->status(null)
            ->draftCreator(currentUser()?->getCraftUserId())
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
            $chip = app(ElementHtml::class)->elementChipHtml($draft, [
                'hyperlink' => true,
            ]);
            $html .= Html::tag('li', $chip, [
                'class' => 'widget__list-item',
            ]);
        }

        return $html.Html::endTag('ul');
    }
}
