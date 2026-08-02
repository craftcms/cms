<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\LinkTypes;

use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Cp\Components\CheckboxSelect;
use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\Components\Lightswitch;
use CraftCms\Cms\Cp\FormDefinitions\Contracts\ProjectableFormElement;
use CraftCms\Cms\Support\Facades\Volumes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Override;

use function CraftCms\Cms\t;

/**
 * Asset link type.
 */
class Asset extends BaseElementLinkType
{
    /**
     * @var array|null The file kinds that the field should be restricted to (only used if [[restrictFiles]] is true).
     */
    public ?array $allowedKinds = null;

    /**
     * @var bool Whether to show input sources for volumes the user doesn’t have permission to view.
     */
    public bool $showUnpermittedVolumes = false;

    /**
     * @var bool Whether to show files the user doesn’t have permission to view, per the “View files uploaded by other
     *           users” permission.
     */
    public bool $showUnpermittedFiles = false;

    public function __construct($config = [])
    {
        if (
            isset($config['allowedKinds']) &&
            (! is_array($config['allowedKinds']) || empty($config['allowedKinds']) || $config['allowedKinds'] === ['*'])
        ) {
            unset($config['allowedKinds']);
        }

        parent::__construct($config);
    }

    protected static function elementType(): string
    {
        return AssetElement::class;
    }

    /** @return list<ProjectableFormElement> */
    #[Override]
    protected function settingsFormElements(bool $readOnly): array
    {
        $allowedKinds = Collection::make(AssetsHelper::getAllowedFileKinds())
            ->map(fn (array $kind, string $value): array => [
                'value' => $value,
                'label' => (string) $kind['label'],
            ])
            ->values()
            ->all();
        array_unshift($allowedKinds, ['label' => t('All'), 'value' => '*']);

        return [
            ...parent::settingsFormElements($readOnly),
            Field::make()
                ->label(t('Allowed File Types'))
                ->readOnly($readOnly)
                ->input(
                    CheckboxSelect::make()
                        ->name('allowedKinds')
                        ->options($allowedKinds)
                        ->allOption('*'),
                ),
            Field::make()
                ->label(t('Show unpermitted volumes'))
                ->instructions(t('Whether to show volumes that the user doesn’t have permission to view.'))
                ->readOnly($readOnly)
                ->input(Lightswitch::make()->name('showUnpermittedVolumes')),
            Field::make()
                ->label(t('Show unpermitted files'))
                ->instructions(t('Whether to show files that the user doesn’t have permission to view, per the “View files uploaded by other users” permission.'))
                ->readOnly($readOnly)
                ->input(Lightswitch::make()->name('showUnpermittedFiles')),
        ];
    }

    #[Override]
    protected function availableSourceKeys(): array
    {
        $volumes = Volumes::getAllVolumes()
            ->filter(fn (Volume $volume) => $volume->sourceHasUrls());

        if (! $this->showUnpermittedVolumes) {
            $volumes = $volumes->filter(fn (Volume $volume) => Gate::check("viewAssets:$volume->uid"));
        }

        return $volumes
            ->map(fn (Volume $volume) => "volume:$volume->uid")
            ->all();
    }

    #[Override]
    protected function selectionCriteria(): array
    {
        // Ignore the parent value since asset URLs don't get saved to the element
        $criteria = [
            'kind' => $this->allowedKinds,
        ];

        if ($this->showUnpermittedFiles) {
            $criteria['uploaderId'] = null;
        }

        return $criteria;
    }

    #[Override]
    protected function elementSelectConfig(): array
    {
        $config = array_merge(parent::elementSelectConfig(), [
            'jsClass' => 'Craft.AssetSelectInput',
        ]);

        if (! $this->showUnpermittedVolumes) {
            $sourceKeys = $this->sources ?? Collection::make($this->availableSources())
                ->map(fn (array $source) => $source['key'])
                ->all();
            $config['sources'] = Collection::make($sourceKeys)
                ->filter(function (string $source) {
                    // If it’s not a volume folder, let it through
                    if (! str_starts_with($source, 'volume:')) {
                        return true;
                    }
                    // Only show it if they have permission to view it, or if it's the temp volume
                    $volumeUid = explode(':', $source)[1];

                    return Gate::check("viewAssets:$volumeUid");
                })
                ->all();
        }

        return $config;
    }

    public function filename(string $value): ?string
    {
        /** @var AssetElement|null $element */
        $element = $this->element($value);

        return $element?->getFilename();
    }
}
