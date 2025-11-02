<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use Craft;
use craft\elements\Entry;
use craft\helpers\Cp;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\Facades\Session;

use function CraftCms\Cms\t;

final class MyDrafts extends Widget
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function displayName(): string
    {
        return t('My Drafts');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected static function allowMultipleInstances(): bool
    {
        return false;
    }

    /**
     * @var int The total number of drafts that the widget should show
     */
    public int $limit = 10;

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function icon(): string
    {
        return 'scribble';
    }

    #[\Override]
    public static function getRules(): array
    {
        return [
            'limit' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
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

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getBodyHtml(): string
    {
        /** @var Entry[] $drafts */
        $drafts = Entry::find()
            ->drafts()
            ->status(null)
            ->draftCreator(Craft::$app->getUser()->getId())
            ->section('*')
            ->site('*')
            ->unique()
            ->orderBy(['dateUpdated' => SORT_DESC])
            ->limit($this->limit)
            ->all();

        if (empty($drafts)) {
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
