<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Data;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Support\Arr;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

class UserSettings extends Component
{
    public ?string $photoVolumeUid = null;

    public ?string $photoSubpath = null;

    /** @var string[]|string|false */
    #[LiteralTypeScriptType('false | "all" | Array<string>')]
    public array|string|false $require2fa = false;

    public bool $requireEmailVerification = true;

    public bool $allowPublicRegistration = false;

    public bool $validateOnPublicRegistration = false;

    public bool $deactivateByDefault = false;

    public ?string $defaultGroup = null;

    public function __construct(object|array $config = [])
    {
        $config = Arr::except($config, ['groups', 'fieldLayouts']);

        parent::__construct($config);
    }
}
