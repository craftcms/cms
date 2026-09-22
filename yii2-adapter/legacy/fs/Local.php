<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fs;

use Craft;
use craft\base\LocalFsInterface;
use craft\helpers\App;
use craft\helpers\FileHelper;
use CraftCms\Yii2Adapter\Filesystem\DiskFs;
use Override;
use yii\validators\InlineValidator;

/**
 * Local represents a local filesystem.
 *
 * @since 4.0.0
 * @deprecated 6.0.0 Configure a Laravel local disk instead.
 */
class Local extends DiskFs implements LocalFsInterface
{
    public const VISIBILITY_FILE = 'file';

    public const VISIBILITY_DIR = 'dir';

    /** @var int[][] */
    protected array $visibilityMap = [
        self::VISIBILITY_FILE => [
            self::VISIBILITY_DEFAULT => 0644,
            self::VISIBILITY_PUBLIC => 0644,
            self::VISIBILITY_HIDDEN => 0600,
        ],
        self::VISIBILITY_DIR => [
            self::VISIBILITY_DEFAULT => 0775,
            self::VISIBILITY_PUBLIC => 0775,
            self::VISIBILITY_HIDDEN => 0700,
        ],
    ];

    public ?string $path = null;

    public function __construct($config = [])
    {
        if (isset($config['path'])) {
            $config['path'] = rtrim(str_replace('\\', '/', $config['path']), '/');
            if ($config['path'] === '') {
                unset($config['path']);
            }
        }

        parent::__construct($config);
    }

    #[Override]
    public static function displayName(): string
    {
        return Craft::t('app', 'Local Folder');
    }

    #[Override]
    public function init(): void
    {
        parent::init();

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        if ($generalConfig->defaultFileMode) {
            $this->visibilityMap[self::VISIBILITY_FILE][self::VISIBILITY_DEFAULT] = $generalConfig->defaultFileMode;
        }
        if ($generalConfig->defaultDirMode) {
            $this->visibilityMap[self::VISIBILITY_DIR][self::VISIBILITY_DEFAULT] = $generalConfig->defaultDirMode;
        }
    }

    #[Override]
    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            'path' => Craft::t('app', 'Base Path'),
        ]);
    }

    #[Override]
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['path'], 'required'];
        $rules[] = [['path'], 'validatePath'];

        return $rules;
    }

    public function validatePath(string $attribute, ?array $params, InlineValidator $validator): void
    {
        if (Craft::$app->getSecurity()->isRestrictedDir($this->getRootPath())) {
            $validator->addError($this, $attribute, Craft::t('app', 'Local filesystems cannot be located within or above system directories.'));
        }
    }

    #[Override]
    public function afterSave(bool $isNew): void
    {
        $path = $this->getRootPath();
        if (!is_dir($path)) {
            FileHelper::createDirectory($path);
            FileHelper::writeGitignoreFile($path);
        }

        parent::afterSave($isNew);
    }

    public function getRootPath(): string
    {
        $path = FileHelper::normalizePath(App::parseEnv($this->path) ?? '');

        return realpath($path) ?: $path;
    }

    #[Override]
    public function getRootUrl(): ?string
    {
        if (!$this->hasUrls) {
            return null;
        }

        $url = App::parseEnv($this->url);
        if (is_string($url)) {
            $url = rtrim($url, '/');
        }

        return $url ? "$url/" : null;
    }

    #[Override]
    public function getDiskConfig(): array
    {
        $config = [
            'driver' => 'local',
            'root' => $this->getRootPath(),
            'permissions' => [
                'file' => [
                    'public' => $this->visibilityMap[self::VISIBILITY_FILE][self::VISIBILITY_PUBLIC],
                    'private' => $this->visibilityMap[self::VISIBILITY_FILE][self::VISIBILITY_HIDDEN],
                ],
                'dir' => [
                    'public' => $this->visibilityMap[self::VISIBILITY_DIR][self::VISIBILITY_PUBLIC],
                    'private' => $this->visibilityMap[self::VISIBILITY_DIR][self::VISIBILITY_HIDDEN],
                ],
            ],
        ];

        $rootUrl = $this->getRootUrl();
        if ($rootUrl !== null) {
            $config['url'] = rtrim($rootUrl, '/');
        }

        return $config;
    }
}
