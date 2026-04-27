<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Filesystems;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Security;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\View\TemplateMode;
use Override;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class Local extends Filesystem
{
    public const string VISIBILITY_FILE = 'file';

    public const string VISIBILITY_DIR = 'dir';

    /**
     * @var int[][] Visibility map
     */
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

    public ?string $settingsHtml {
        get => $this->getSettingsHtml();
        set {
        }
    }

    public string $rootPath {
        get => $this->getRootPath();
        set {
        }
    }

    #[Override]
    public static function displayName(): string
    {
        return t('Local Folder');
    }

    /**
     * @var string|null Path to the root of this sources local folder.
     */
    public ?string $path = null;

    /**
     * {@inheritdoc}
     */
    public function __construct($config = [])
    {
        // Config normalization
        if (isset($config['path'])) {
            $config['path'] = rtrim(str_replace('\\', '/', $config['path']), '/');
            if ($config['path'] === '') {
                unset($config['path']);
            }
        }

        parent::__construct($config);

        $generalConfig = Cms::config();

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
            'path' => t('Base Path'),
        ]);
    }

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'path' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (Security::isSystemDir($this->getRootPath())) {
                        $fail(t('Local filesystems cannot be located within or above system directories.'));
                    }
                },
            ],
        ]);
    }

    #[Override]
    public function getSettingsHtml(): ?string
    {
        return $this->settingsHtml(false);
    }

    #[Override]
    public function getReadOnlySettingsHtml(): ?string
    {
        return $this->settingsHtml(true);
    }

    private function settingsHtml(bool $readOnly): string
    {
        return template('_components/fs/Local/settings', [
            'filesystem' => $this,
            'readOnly' => $readOnly,
        ], TemplateMode::Cp);
    }

    #[Override]
    public function afterSave(bool $isNew): void
    {
        // If the folder doesn't exist yet, create it with a .gitignore file
        $path = $this->getRootPath();

        File::ensureDirectoryExists($path);
        File::writeGitignoreFile($path);

        parent::afterSave($isNew);
    }

    public function getRootPath(): string
    {
        $path = File::normalizePath(Env::parse($this->path) ?? '');

        // Pass it through realpath() in case the path is symlinked
        return realpath($path) ?: $path;
    }

    #[Override]
    public function getDiskConfig(): array
    {
        $config = [
            'driver' => 'local',
            'root' => $this->getRootPath(),
            'permissions' => [
                'file' => [
                    'public' => $this->diskPermission(self::VISIBILITY_FILE, 'public'),
                    'private' => $this->diskPermission(self::VISIBILITY_FILE, 'private'),
                ],
                'dir' => [
                    'public' => $this->diskPermission(self::VISIBILITY_DIR, 'public'),
                    'private' => $this->diskPermission(self::VISIBILITY_DIR, 'private'),
                ],
            ],
            'visibility' => $this->defaultDiskVisibility(self::VISIBILITY_FILE),
            'directory_visibility' => $this->defaultDiskVisibility(self::VISIBILITY_DIR),
        ];

        $rootUrl = $this->getRootUrl();
        if ($rootUrl !== null) {
            $config['url'] = rtrim($rootUrl, '/');
        }

        return $config;
    }

    private function diskPermission(string $type, string $visibility): int
    {
        $defaultVisibility = $this->defaultDiskVisibility($type);
        if ($defaultVisibility === $visibility) {
            return $this->visibilityMap[$type][self::VISIBILITY_DEFAULT];
        }

        if ($visibility === 'private') {
            return $this->visibilityMap[$type][self::VISIBILITY_HIDDEN];
        }

        return $this->visibilityMap[$type][self::VISIBILITY_PUBLIC];
    }

    private function defaultDiskVisibility(string $type): string
    {
        $defaultMode = $this->visibilityMap[$type][self::VISIBILITY_DEFAULT];
        $hiddenMode = $this->visibilityMap[$type][self::VISIBILITY_HIDDEN];

        return $defaultMode === $hiddenMode ? 'private' : 'public';
    }
}
