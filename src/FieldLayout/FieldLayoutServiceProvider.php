<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\FieldLayout\LayoutElements\Addresses\AddressField;
use CraftCms\Cms\FieldLayout\LayoutElements\Addresses\CountryCodeField;
use CraftCms\Cms\FieldLayout\LayoutElements\Addresses\LabelField;
use CraftCms\Cms\FieldLayout\LayoutElements\Addresses\LatLongField;
use CraftCms\Cms\FieldLayout\LayoutElements\Addresses\OrganizationField;
use CraftCms\Cms\FieldLayout\LayoutElements\Addresses\OrganizationTaxIdField;
use CraftCms\Cms\FieldLayout\LayoutElements\Assets\AltField;
use CraftCms\Cms\FieldLayout\LayoutElements\Assets\AssetTitleField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\LayoutElements\FullNameField;
use CraftCms\Cms\FieldLayout\LayoutElements\Users\AffiliatedSiteField;
use CraftCms\Cms\FieldLayout\LayoutElements\Users\EmailField;
use CraftCms\Cms\FieldLayout\LayoutElements\Users\FullNameField as UserFullNameField;
use CraftCms\Cms\FieldLayout\LayoutElements\Users\PhotoField;
use CraftCms\Cms\FieldLayout\LayoutElements\Users\UsernameField;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\ServiceProvider;

class FieldLayoutServiceProvider extends ServiceProvider
{
    public function boot(NativeFields $nativeFields): void
    {
        $nativeFields->register('craft', function (FieldLayout $fieldLayout, array $fields, Sites $sites): array {
            switch ($fieldLayout->type) {
                case Address::class:
                    array_push($fields,
                        LabelField::class,
                        OrganizationField::class,
                        OrganizationTaxIdField::class,
                        FullNameField::class,
                        CountryCodeField::class,
                        AddressField::class,
                        LatLongField::class,
                    );
                    break;
                case Asset::class:
                    array_push($fields, AssetTitleField::class, AltField::class);
                    break;
                case Entry::class:
                    $fields[] = EntryTitleField::class;
                    break;
                case User::class:
                    if (! Cms::config()->useEmailAsUsername) {
                        $fields[] = UsernameField::class;
                    }
                    array_push($fields, UserFullNameField::class, PhotoField::class, EmailField::class);
                    if ($sites->isMultiSite()) {
                        $fields[] = AffiliatedSiteField::class;
                    }
                    break;
            }

            return $fields;
        });
    }
}
