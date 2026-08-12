<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\LinkTypes;

use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Enums\ChoicePresentation;
use CraftCms\Cms\Form\Nodes\Field as FormField;
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
    /** @var list<string>|null The file kinds that the field should be restricted to (only used if [[restrictFiles]] is true). */
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

    #[Override]
    public function settingsNodes(string $prefix): array
    {
        return [
            ...parent::settingsNodes($prefix),
            FormField::make(t('Allowed File Types'))
                ->control(Choice::make($this->settingPath($prefix, 'allowedKinds'))
                    ->multiple()
                    ->presentation(ChoicePresentation::Checkboxes)
                    ->options(Collection::make(AssetsHelper::getAllowedFileKinds())
                        ->map(fn (array $kind, string $value): array => [
                            'value' => $value,
                            'label' => $kind['label'],
                        ])
                        ->values()
                        ->all())
                    ->value($this->allowedKinds ?? [])),
            FormField::make(t('Show unpermitted volumes'))
                ->instructions(t('Whether to show volumes that the user doesn’t have permission to view.'))
                ->control(Lightswitch::make($this->settingPath($prefix, 'showUnpermittedVolumes'))->value($this->showUnpermittedVolumes)),
            FormField::make(t('Show unpermitted files'))
                ->instructions(t('Whether to show files that the user doesn’t have permission to view, per the “View files uploaded by other users” permission.'))
                ->control(Lightswitch::make($this->settingPath($prefix, 'showUnpermittedFiles'))->value($this->showUnpermittedFiles)),
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

    /** @return array<string, bool|list<string>|string|null> */
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

    /**
     * @return array{
     *     elementType: class-string<ElementInterface>,
     *     limit: int,
     *     single: bool,
     *     sources: string|array<int, string>,
     *     criteria: array<string, bool|list<string>|string|null>,
     *     jsClass: string,
     * }
     */
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
