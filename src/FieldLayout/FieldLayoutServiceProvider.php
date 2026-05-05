<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\FieldLayout\Events\NativeFieldsResolving;
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
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class FieldLayoutServiceProvider extends ServiceProvider
{
    public function boot(Sites $sites): void
    {
        Event::listen(function (NativeFieldsResolving $event) use ($sites) {
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
