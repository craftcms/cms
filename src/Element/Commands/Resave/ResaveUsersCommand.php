<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Commands\Resave;

use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\User\Elements\User;
use Override;

class ResaveUsersCommand extends ResaveCommand
{
    #[Override]
    protected $signature = 'craft:resave:users'
        .self::DEFAULT_OPTIONS
        .' {--group= : User group handle(s), comma-separated.}';

    #[Override]
    protected $description = 'Re-saves users.';

    #[Override]
    protected $aliases = ['resave/users'];

    public function handle(Fields $fields): int
    {
        if (! $this->validateResaveOptions()) {
            return self::FAILURE;
        }

        $criteria = [];

        if ($this->option('group')) {
            $criteria['group'] = str($this->option('group'))
                ->explode(',')
                ->all();
        }

        $withFields = $this->resolvedWithFields();

        if (! empty($withFields)) {
            $fieldLayout = $fields->getLayoutByType(User::class);

            if (! $this->hasTheFields($fieldLayout)) {
                $this->components->warn("The user field layout doesn\u{2019}t satisfy `--with-fields`.");

                return self::FAILURE;
            }
        }

        return $this->resaveElements(User::class, $criteria);
    }
}
