<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Component\Concerns\ConfigurableComponent;
use CraftCms\Cms\Component\Concerns\SavableComponent;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Validation\Rules\HandleRule;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Validation\Rule;

use function CraftCms\Cms\t;

abstract class Filesystem extends Component implements FsInterface
{
    use ConfigurableComponent;
    use Macroable;
    use SavableComponent;

    public const string CONFIG_MIMETYPE = 'mimetype';

    public const string CONFIG_VISIBILITY = 'visibility';

    public const string VISIBILITY_DEFAULT = 'default';

    public const string VISIBILITY_HIDDEN = 'hidden';

    public const string VISIBILITY_PUBLIC = 'public';

    /**
     * Whether the “Files in this filesystem have public URLs” setting should be shown.
     */
    protected static bool $showHasUrlSetting = true;

    /**
     * Whether the “Base URL” setting should be shown.
     */
    protected static bool $showUrlSetting = true;

    public ?string $name = null;

    public ?string $handle = null;

    public ?string $oldHandle = null;

    public bool $hasUrls = false;

    public ?string $url = null;

    public ?string $uid = null;

    public ?string $rootUrl {
        get => $this->getRootUrl();
        set {
        }
    }

    public function __construct(array|object $config = [])
    {
        parent::__construct($config);
    }

    public function getRootUrl(): ?string
    {
        if (! $this->hasUrls) {
            return null;
        }

        $url = Env::parse($this->url);
        if (is_string($url)) {
            $url = rtrim($url, '/');
        }

        if ($url) {
            return "$url/";
        }

        return null;
    }

    abstract public function getDiskConfig(): array;

    #[\Override]
    public function attributeLabels(): array
    {
        return [
            'handle' => t('Handle'),
            'name' => t('Name'),
            'url' => t('Base URL'),
        ];
    }

    public function getShowHasUrlSetting(): bool
    {
        return static::$showHasUrlSetting;
    }

    public function getShowUrlSetting(): bool
    {
        return static::$showUrlSetting;
    }

    #[\Override]
    public function getRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'handle' => [
                'required',
                'string',
                'max:255',
                new HandleRule([
                    'dateCreated',
                    'dateUpdated',
                    'edit',
                    'id',
                    'new',
                    'title',
                    'uid',
                ]),
            ],
            'url' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn () => $this->hasUrls && $this->getShowUrlSetting()),
            ],
        ];
    }
}
