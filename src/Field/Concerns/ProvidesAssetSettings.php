<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Concerns;

use Closure;
use CraftCms\Cms\Asset\Conditions\ViewableConditionRule;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Conditions;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Controls\ConditionBuilder;
use CraftCms\Cms\Ui\Controls\Lightswitch;
use CraftCms\Cms\Ui\Nodes\Field as UiField;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use function CraftCms\Cms\t;

/**
 * Asset settings for fields whose input lets authors select or upload assets, such as a rich text or Markdown editor:
 * which volumes are available, whether unpermitted volumes are shown, which assets are selectable, and where pasted
 * or dropped files are uploaded.
 *
 * Call {@see prepareAssetSettingsConfig()} from the constructor before `parent::__construct()`, and
 * {@see addDefaultAssetSelectionCondition()} after it. Add {@see assetSettingsNodes()} to the settings UI,
 * {@see assetSettingsRules()} to the rules, and {@see assetSelectionConditionSettings()} to the settings.
 * The field's input can then be passed {@see assetSourceKeys()}, {@see getAssetSelectionCondition()} and
 * {@see uploadFolderId()}.
 *
 * @since 6.0.0
 */
trait ProvidesAssetSettings
{
    /**
     * The UIDs of the volumes that should be available when selecting assets, or `'*'` for all of them.
     *
     * @var list<string>|'*'
     */
    public array|string $availableVolumes = '*';

    /** The UID of the volume where pasted or dropped files should be uploaded. */
    public ?string $uploadVolume = null;

    /** Whether to show volumes that the user doesn’t have permission to view. */
    public bool $showUnpermittedVolumes = false;

    /**
     * @phpstan-var ElementConditionInterface|array{class:class-string<ElementConditionInterface>}|null
     *
     * @see getAssetSelectionCondition()
     * @see setAssetSelectionCondition()
     */
    private array|null|ElementConditionInterface $_assetSelectionCondition = null;

    /**
     * Normalizes the asset settings in a field config.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected function prepareAssetSettingsConfig(array $config): array
    {
        if (array_key_exists('availableVolumes', $config)) {
            $config['availableVolumes'] = match (true) {
                $config['availableVolumes'] === '*',
                $config['availableVolumes'] === ['*'] => '*',
                is_array($config['availableVolumes']) => $config['availableVolumes'],
                $config['availableVolumes'] === null,
                $config['availableVolumes'] === '' => [],
                default => [$config['availableVolumes']],
            };
        }

        if (($config['uploadVolume'] ?? null) === '') {
            $config['uploadVolume'] = null;
        }

        return $config;
    }

    /**
     * Gives a new field a selection condition with the “Viewable” rule, so only assets the user can view are
     * selectable by default.
     *
     * @param  array<string, mixed>  $config
     */
    protected function addDefaultAssetSelectionCondition(array $config): void
    {
        if (isset($config['id']) || $this->getAssetSelectionCondition() !== null) {
            return;
        }

        $condition = $this->createAssetSelectionCondition();
        $condition->getConditionRules()->addRule(new ViewableConditionRule(['value' => true]));
        $this->setAssetSelectionCondition($condition);
    }

    /**
     * Returns the element condition that should be used to determine which assets are selectable by this field.
     */
    public function getAssetSelectionCondition(): ?ElementConditionInterface
    {
        if ($this->_assetSelectionCondition !== null && ! $this->_assetSelectionCondition instanceof ConditionInterface) {
            /** @var ElementConditionInterface $condition */
            $condition = Conditions::createCondition($this->_assetSelectionCondition);
            if (! empty($condition->getConditionRules()->getRules())) {
                $this->_assetSelectionCondition = $condition;
            } else {
                $this->_assetSelectionCondition = null;
            }
        }

        return $this->_assetSelectionCondition;
    }

    /**
     * Sets the element condition that should be used to determine which assets are selectable by this field.
     *
     * @param  ElementConditionInterface|string|array|null  $condition
     *
     * @phpstan-param ElementConditionInterface|string|array{class:string}|null $condition
     */
    public function setAssetSelectionCondition(mixed $condition): void
    {
        if ($condition instanceof ConditionInterface && ! $condition->getConditionRules()->getRules()) {
            $condition = null;
        }

        // Don't instantiate it unless we actually end up needing it.
        $this->_assetSelectionCondition = $condition;
    }

    /**
     * Creates an element condition that should be used to determine which assets are selectable by this field.
     */
    protected function createAssetSelectionCondition(): ElementConditionInterface
    {
        return Asset::createCondition();
    }

    /**
     * Returns the selection condition's config, keyed for the field's settings.
     *
     * @return array{assetSelectionCondition?: array<string, mixed>}
     */
    protected function assetSelectionConditionSettings(): array
    {
        $condition = $this->getAssetSelectionCondition();

        return $condition ? ['assetSelectionCondition' => $condition->getConfig()] : [];
    }

    /**
     * Returns the source keys of the volumes the user can select assets from.
     *
     * @return list<string>
     */
    protected function assetSourceKeys(): array
    {
        return $this->availableAssetVolumes()
            ->map(fn (Volume $volume) => "volume:$volume->uid")
            ->all();
    }

    /**
     * Returns the ID of the folder pasted or dropped files should be uploaded to, if the user can save assets there.
     */
    protected function uploadFolderId(): ?int
    {
        $volume = $this->uploadVolume ? Volumes::getVolumeByUid($this->uploadVolume) : null;

        if (! $volume || ! Gate::check("saveAssets:$volume->uid")) {
            return null;
        }

        return Folders::getRootFolderByVolumeId($volume->id)?->id;
    }

    /**
     * Returns the volumes the user can select assets from.
     *
     * @return Collection<int, Volume>
     */
    protected function availableAssetVolumes(): Collection
    {
        $volumes = Volumes::getAllVolumes();

        if ($this->availableVolumes !== '*') {
            $volumes = $volumes->filter(fn (Volume $volume) => in_array($volume->uid, $this->availableVolumes, true));
        }

        if (! $this->showUnpermittedVolumes) {
            $volumes = $volumes->filter(fn (Volume $volume) => Gate::check("viewAssets:$volume->uid"));
        }

        return $volumes->values();
    }

    /** @return list<array{label: string, value: string}> */
    public function volumeOptions(): array
    {
        return Volumes::getAllVolumes()
            ->map(fn (Volume $volume) => [
                'label' => $volume->name,
                'value' => $volume->uid,
            ])
            ->values()
            ->all();
    }

    /**
     * Returns the settings UI fields for the asset settings.
     *
     * @return list<Node>
     */
    protected function assetSettingsNodes(): array
    {
        $volumeOptions = $this->volumeOptions();
        $assetSelectionCondition = $this->getAssetSelectionCondition() ?? $this->createAssetSelectionCondition();

        return [
            UiField::make(t('Available Volumes'))
                ->instructions(t('The volumes that should be available when selecting assets.'))
                ->control(Choice::make('availableVolumes')
                    ->multiple()
                    ->options([
                        ['label' => t('All'), 'value' => '*'],
                        ...$volumeOptions,
                    ])
                    ->value($this->availableVolumes === '*' ? ['*'] : $this->availableVolumes)),
            UiField::make(t('Show unpermitted volumes'))
                ->instructions(t('Whether to show volumes that the user doesn’t have permission to view.'))
                ->control(Lightswitch::make('showUnpermittedVolumes')->value($this->showUnpermittedVolumes)),
            UiField::make(t('Selectable Assets Condition'))
                ->instructions(mb_ucfirst(t('Only allow assets to be selected if they match the following rules:')))
                ->control(ConditionBuilder::make('assetSelectionCondition')
                    ->conditionClass($assetSelectionCondition::class)
                    ->queryParams(['site'])
                    ->forProjectConfig()
                    ->value($assetSelectionCondition->getConfig())),
            UiField::make(t('Upload Volume'))
                ->instructions(t('The volume where pasted or dropped files should be uploaded.'))
                ->control(Choice::make('uploadVolume')->options([
                    ['label' => t('No uploads'), 'value' => ''],
                    ...$volumeOptions,
                ])->value($this->uploadVolume)),
        ];
    }

    /** @return array<string, list<mixed>> */
    protected function assetSettingsRules(): array
    {
        $volumeUids = fn (): array => Arr::pluck($this->volumeOptions(), 'value');

        return [
            'uploadVolume' => ['nullable', Rule::in($volumeUids())],
            'availableVolumes' => [
                function ($attribute, mixed $value, Closure $fail) use ($volumeUids) {
                    if ($value === '*') {
                        return;
                    }

                    if (! is_array($value)) {
                        $fail(t('Invalid volumes.'));

                        return;
                    }

                    foreach ($value as $volumeUid) {
                        if (! is_string($volumeUid) || ! in_array($volumeUid, $volumeUids(), true)) {
                            $fail(t('Invalid volumes.'));

                            return;
                        }
                    }
                },
            ],
            'showUnpermittedVolumes' => ['boolean'],
        ];
    }
}
