<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Commands\Resave;

use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Address\Elements\Address;
use Override;

class ResaveAddressesCommand extends ResaveCommand
{
    #[Override]
    protected $signature = 'craft:resave:addresses'
        .self::DEFAULT_OPTIONS
        .'
        {--owner-id= : Owner element ID(s), comma-separated.}
        {--country-code= : Country code(s), comma-separated.}
    ';

    #[Override]
    protected $description = 'Re-saves addresses.';

    #[Override]
    protected $aliases = ['resave/addresses'];

    public function handle(Addresses $addresses): int
    {
        if (! $this->validateResaveOptions()) {
            return self::FAILURE;
        }

        $criteria = [];

        if ($this->option('owner-id')) {
            $criteria['ownerId'] = str($this->option('owner-id'))
                ->explode(',')
                ->map(intval(...))
                ->all();
        }

        if ($this->option('country-code')) {
            $criteria['countryCode'] = str($this->option('country-code'))
                ->explode(',')
                ->all();
        }

        $withFields = $this->resolvedWithFields();

        if (! empty($withFields)) {
            $fieldLayout = $addresses->getFieldLayout();

            if (! $this->hasTheFields($fieldLayout)) {
                $this->components->warn("The address field layout doesn\u{2019}t satisfy `--with-fields`.");

                return self::FAILURE;
            }
        }

        return $this->resaveElements(Address::class, $criteria);
    }
}
