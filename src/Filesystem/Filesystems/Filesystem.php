<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Filesystems;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Component\Concerns\ConfigurableComponent;
use CraftCms\Cms\Component\Concerns\SavableComponent;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Validation\Rules\HandleRule;
use Override;

use function CraftCms\Cms\t;

abstract class Filesystem extends Component implements FsInterface
{
    use ConfigurableComponent;
    use SavableComponent;

    public const string CONFIG_MIMETYPE = 'mimetype';

    public const string CONFIG_VISIBILITY = 'visibility';

    public const string VISIBILITY_DEFAULT = 'default';

    public const string VISIBILITY_HIDDEN = 'hidden';

    public const string VISIBILITY_PUBLIC = 'public';

    public ?string $name = null;

    public ?string $handle = null;

    public ?string $oldHandle = null;

    public ?string $uid = null;

    public function getRootUrl(): ?string
    {
        return null;
    }

    abstract public function getDiskConfig(): array;

    #[Override]
    public function attributeLabels(): array
    {
        return [
            'handle' => t('Handle'),
            'name' => t('Name'),
        ];
    }

    #[Override]
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
        ];
    }
}
