<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Field\Markdown as MarkdownField;
use CraftCms\Cms\Release\Models\Release;
use CraftCms\Cms\Ui\Controls\Markdown;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class ReleaseEditViewModel extends ViewModel
{
    public function __construct(
        private readonly ?Release $release,
        private readonly UiResolver $uiResolver,
    ) {}

    public function ui(): UiPayload
    {
        $release = $this->release;
        $name = $release === null ? '' : $release->name;
        $description = $release === null ? null : $release->description;
        $plannedAt = $release === null ? null : $release->plannedAt;

        return $this->uiResolver->resolve(Ui::make([
            Field::make(t('Name'), Text::make('name')->autofocus())
                ->required()
                ->instructions(t('What this release will be called in the control panel.')),
            Field::make(t('Description'), Markdown::make('description')
                ->toolbarButtons(MarkdownField::DEFAULT_TOOLBAR_BUTTONS)),
            ReleaseDateGroup::make('plannedAt', t('Planned date'))
                ->instructions(t('This date is for planning only. The release will not publish until it is explicitly scheduled.')),
        ]), new UiContext(values: [
            'name' => $name,
            'description' => $description,
            'plannedAt' => ReleaseDateGroup::value($plannedAt),
        ]));
    }

    /** @return array{method: 'patch'|'post', url: string} */
    public function submit(): array
    {
        return [
            'method' => $this->release === null ? 'post' : 'patch',
            'url' => $this->release === null
                ? route('craft.cp.releases.store')
                : route('craft.cp.releases.update', $this->release),
        ];
    }
}
