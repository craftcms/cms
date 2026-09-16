<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\LinkTypes;

use CraftCms\Cms\Asset\Conditions\ViewableConditionRule;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Form\Controls\Lightswitch;
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
    /**
     * @var bool Whether to show input sources for volumes the user doesn’t have permission to view.
     */
    public bool $showUnpermittedVolumes = false;

    public function __construct($config = [])
    {
        parent::__construct($config);

        // Add the “Viewable” rule by default
        if (empty($config) && is_null($this->getSelectionCondition())) {
            $condition = $this->createSelectionCondition();
            $condition->getConditionRules()->addRule(new ViewableConditionRule(['value' => true]));
            $this->setSelectionCondition($condition);
        }
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
            FormField::make(t('Show unpermitted volumes'))
                ->instructions(t('Whether to show volumes that the user doesn’t have permission to view.'))
                ->control(Lightswitch::make($this->settingPath($prefix, 'showUnpermittedVolumes'))->value($this->showUnpermittedVolumes)),
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
        // Ignore the parent value since asset URLs don't get saved to the element,
        // and let the selection condition determine whether they can view unpermitted files
        return [
            'uploaderId' => null,
        ];
    }

    /**
     * @return array{
     *     elementType: class-string<ElementInterface>,
     *     limit: int,
     *     single: bool,
     *     sources: string|array<int, string>,
     *     criteria: array<string, bool|list<string>|string|null>,
     *     condition: array<string, mixed>|null,
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
