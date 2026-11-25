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
 * FilesystemUsageBlocker lists the volumes that store their files or transforms on a filesystem.
 *
 * It can’t be resolved from the deletion modal. The volumes have to be moved to another filesystem first.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class FilesystemUsageBlocker extends BaseObject implements DeletionBlockerInterface
{
    /**
     * @var bool Whether the blocker’s details should be expanded when the modal opens.
     */
    public bool $expandDetails = true;

    /**
     * @var Volume[]
     */
    private array $volumes;

    /**
     * Constructor
     */
    public function __construct(
        protected readonly FsInterface $fs,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function init(): void
    {
        $this->volumes = array_values(array_filter(
            Craft::$app->getVolumes()->getAllVolumes(),
            fn(Volume $volume) => in_array($this->fs->handle, [
                $volume->getFsHandle(),
                $volume->getTransformFsHandle(),
            ], true),
        ));

        parent::init();
    }

    public function isActive(): bool
    {
        return !empty($this->volumes);
    }

    public function getSummary(): string
    {
        return Craft::t('app', 'The filesystem is used by {num, number} {num, plural, =1{volume} other{volumes}}. Choose another filesystem for {num, plural, =1{it} other{them}} first.', [
            'num' => count($this->volumes),
        ]);
    }

    public function getDetails(): ?string
    {
        return Html::ul(array_map(fn(Volume $volume) => Html::a(
            Html::encode($volume->getUiLabel()),
            $volume->getCpEditUrl(),
        ), $this->volumes), ['encode' => false]);
    }

    public function getActions(): array
    {
        return [];
    }
}
