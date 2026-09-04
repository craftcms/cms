<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Filesystems;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Security;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Validation\Rules\EnvValueRule;
use Illuminate\Validation\Rule;
use Override;

use function CraftCms\Cms\t;

class Local extends Filesystem
{
    public const string VISIBILITY_FILE = 'file';

    public const string VISIBILITY_DIR = 'dir';

    public bool $hasUrls = false;

    public ?string $url = null;

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
            'url' => t('Base URL'),
        ]);
    }

    #[Override]
    public function settingsAttributes(): array
    {
        return array_values(array_diff(parent::settingsAttributes(), ['rootPath']));
    }

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'url' => new EnvValueRule([
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn () => $this->hasUrls),
            ]),
            'path' => new EnvValueRule([
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (Security::isRestrictedDir($this->getRootPath())) {
                        $fail(t('Local filesystems cannot be located within or above system directories.'));
                    }
                },
            ]),
        ]);
    }

    #[Override]
    public function settingsForm(FormContext $context = new FormContext): ?Form
    {
        $form = Form::make();

        $form->add(Field::make(t('Files in this filesystem have public URLs'))
            ->control(Lightswitch::make('hasUrls')
                ->value($this->hasUrls)
                ->reactive()));

        if ($this->hasUrls) {
            $form->add(Field::make(t('Base URL'))
                ->instructions(t('The base URL to the files in this filesystem.'))
                ->required()
                ->control(Text::make('url')
                    ->value($this->url)
                    ->textExpanderTriggers(SelectOptions::getEnvTextExpanderTriggers(true, fn ($value): bool => Str::isUrl($value)))
                    ->placeholder('//example.com/path/to/folder'))
                ->tip(t('Type `$` to choose an environment variable, or `@` to choose an alias.')));
        }

        return $form->add(Field::make(t('Base Path'))
            ->instructions(t('The base folder path that should be used as the root of the filesystem.'))
            ->required()
            ->control(Text::make('path')
                ->value($this->path)
                ->textExpanderTriggers(SelectOptions::getEnvTextExpanderTriggers(true))
                ->placeholder('/path/to/folder'))
            ->tip(t('Type `$` to choose an environment variable, or `@` to choose an alias.')));
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
    public function getRootUrl(): ?string
    {
        if (! $this->hasUrls) {
            return null;
        }

        $url = Env::parse($this->url);
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
