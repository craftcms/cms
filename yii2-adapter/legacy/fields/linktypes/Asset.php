<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\linktypes;

use Craft;
use craft\elements\Asset as AssetElement;
use craft\fields\Link;
use craft\fs\Temp;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\Cp;
use craft\models\Volume;
use Illuminate\Support\Collection;

/**
 * Asset link type.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
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
     * users” permission.
     */
    public bool $showUnpermittedFiles = false;

    public function __construct($config = [])
    {
        if (
            isset($config['allowedKinds']) &&
            (!is_array($config['allowedKinds']) || empty($config['allowedKinds']) || $config['allowedKinds'] === ['*'])
        ) {
            unset($config['allowedKinds']);
        }

        parent::__construct($config);
    }

    protected static function elementType(): string
    {
        return AssetElement::class;
    }

    public function getSettingsHtml(): ?string
    {
        return
            parent::getSettingsHtml() .
            Cp::checkboxSelectFieldHtml([
                'label' => Craft::t('app', 'Allowed File Types'),
                'name' => 'allowedKinds',
                'options' => Collection::make(AssetsHelper::getAllowedFileKinds())
                    ->map(fn(array $kind, string $value) => [
                        'value' => $value,
                        'label' => $kind['label'],
                    ])
                    ->all(),
                'values' => $this->allowedKinds ?? '*',
                'showAllOption' => true,
            ]) .
            Cp::lightswitchFieldHtml([
                'label' => Craft::t('app', 'Show unpermitted volumes'),
                'instructions' => Craft::t('app', 'Whether to show volumes that the user doesn’t have permission to view.'),
                'name' => 'showUnpermittedVolumes',
                'on' => $this->showUnpermittedVolumes,
            ]) .
            Cp::lightswitchFieldHtml([
                'label' => Craft::t('app', 'Show unpermitted files'),
                'instructions' => Craft::t('app', 'Whether to show files that the user doesn’t have permission to view, per the “View files uploaded by other users” permission.'),
                'name' => 'showUnpermittedFiles',
                'on' => $this->showUnpermittedFiles,
            ]);
    }

    protected function availableSourceKeys(): array
    {
        $volumes = Collection::make(Craft::$app->getVolumes()->getAllVolumes())
            ->filter(fn(Volume $volume) => $volume->getFs()->hasUrls);

        if (!$this->showUnpermittedVolumes) {
            $userService = Craft::$app->getUser();
            $volumes = $volumes->filter(fn(Volume $volume) => $userService->checkPermission("viewAssets:$volume->uid"));
        }

        return $volumes
            ->map(fn(Volume $volume) => "volume:$volume->uid")
            ->all();
    }

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

    protected function elementSelectConfig(): array
    {
        $config = array_merge(parent::elementSelectConfig(), [
            'jsClass' => 'Craft.AssetSelectInput',
        ]);

        if (!$this->showUnpermittedVolumes) {
            $sourceKeys = $this->sources ?? Collection::make($this->availableSources())
                ->map(fn(array $source) => $source['key'])
                ->all();
            $userService = Craft::$app->getUser();
            $config['sources'] = Collection::make($sourceKeys)
                ->filter(function(string $source) use ($userService) {
                    // If it’s not a volume folder, let it through
                    if (!str_starts_with($source, 'volume:')) {
                        return true;
                    }
                    // Only show it if they have permission to view it, or if it's the temp volume
                    $volumeUid = explode(':', $source)[1];
                    return $userService->checkPermission("viewAssets:$volumeUid");
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
