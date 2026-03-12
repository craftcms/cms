<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\FieldLayout\Events\DefineNativeFields;
use CraftCms\Cms\FieldLayout\LayoutElements\addresses\AddressField;
use CraftCms\Cms\FieldLayout\LayoutElements\addresses\CountryCodeField;
use CraftCms\Cms\FieldLayout\LayoutElements\addresses\LabelField;
use CraftCms\Cms\FieldLayout\LayoutElements\addresses\LatLongField;
use CraftCms\Cms\FieldLayout\LayoutElements\addresses\OrganizationField;
use CraftCms\Cms\FieldLayout\LayoutElements\addresses\OrganizationTaxIdField;
use CraftCms\Cms\FieldLayout\LayoutElements\assets\AltField;
use CraftCms\Cms\FieldLayout\LayoutElements\assets\AssetTitleField;
use CraftCms\Cms\FieldLayout\LayoutElements\entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\LayoutElements\FullNameField;
use CraftCms\Cms\FieldLayout\LayoutElements\users\AffiliatedSiteField;
use CraftCms\Cms\FieldLayout\LayoutElements\users\EmailField;
use CraftCms\Cms\FieldLayout\LayoutElements\users\FullNameField as UserFullNameField;
use CraftCms\Cms\FieldLayout\LayoutElements\users\PhotoField;
use CraftCms\Cms\FieldLayout\LayoutElements\users\UsernameField;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class FieldLayoutServiceProvider extends ServiceProvider
{
    public function boot(Sites $sites): void
    {
        Event::listen(function (DefineNativeFields $event) use ($sites) {
            switch ($event->fieldLayout->type) {
                case Address::class:
                    $event->fields[] = LabelField::class;
                    $event->fields[] = OrganizationField::class;
                    $event->fields[] = OrganizationTaxIdField::class;
                    $event->fields[] = FullNameField::class;
                    $event->fields[] = CountryCodeField::class;
                    $event->fields[] = AddressField::class;
                    $event->fields[] = LatLongField::class;
                    break;
                case Asset::class:
                    $event->fields[] = AssetTitleField::class;
                    $event->fields[] = AltField::class;
                    break;
                case Entry::class:
                    $event->fields[] = EntryTitleField::class;
                    break;
                case User::class:
                    if (! Cms::config()->useEmailAsUsername) {
                        $event->fields[] = UsernameField::class;
                    }
                    $event->fields[] = UserFullNameField::class;
                    $event->fields[] = PhotoField::class;
                    $event->fields[] = EmailField::class;
                    if ($sites->isMultiSite()) {
                        $event->fields[] = AffiliatedSiteField::class;
                    }
                    break;
            }
        });
    }
}
