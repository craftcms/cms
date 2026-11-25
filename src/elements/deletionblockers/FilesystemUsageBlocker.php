<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\deletionblockers;

use Craft;
use craft\base\FsInterface;
use craft\helpers\Html;
use craft\models\Volume;
use yii\base\BaseObject;

/**
 * FilesystemUsageBlocker lists the volumes that store their assets, or their asset transforms, in a filesystem.
 *
 * It can’t be resolved from the deletion modal. Each volume has an Edit button, and the blocker goes away once the
 * volumes use another filesystem.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class FilesystemUsageBlocker extends BaseObject implements DeletionBlockerInterface
{
    /**
     * @var Volume[]
     */
    private array $volumes;

    /**
     * Constructor
     *
     * @param FsInterface $fs The filesystem
     * @param bool $transforms Whether to list the volumes that store asset transforms in the filesystem, rather than
     * assets
     */
    public function __construct(
        protected readonly FsInterface $fs,
        protected readonly bool $transforms = false,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function init(): void
    {
        $this->volumes = array_values(array_filter(
            Craft::$app->getVolumes()->getAllVolumes(),
            fn(Volume $volume) => ($this->transforms ? $volume->getTransformFsHandle() : $volume->getFsHandle()) === $this->fs->handle,
        ));

        parent::init();
    }

    public function isActive(): bool
    {
        return !empty($this->volumes);
    }

    public function getSummary(): string
    {
        if ($this->transforms) {
            return Craft::t('app', '{num, number} {num, plural, =1{volume stores} other{volumes store}} asset transforms in this filesystem.', [
                'num' => count($this->volumes),
            ]);
        }

        return Craft::t('app', '{num, number} {num, plural, =1{volume stores} other{volumes store}} assets in this filesystem.', [
            'num' => count($this->volumes),
        ]);
    }

    public function getDetails(): ?string
    {
        return null;
    }

    public function getActions(): array
    {
        // Edit each volume in a slideout, and refresh the blockers once it's saved
        return array_map(fn(Volume $volume) => [
            'icon' => 'pencil',
            'label' => Craft::t('app', 'Edit {name}', ['name' => $volume->getUiLabel()]),
            'callback' => Html::jsWithVars(fn($id) => <<<JS
const slideout = new Craft.CpScreenSlideout('volumes/edit-volume', {params: {volumeId: $id}});
slideout.on('submit', () => blocker.manager.refresh());
slideout.on('close', () => reject());
JS, [$volume->id]),
        ], $this->volumes);
    }
}
