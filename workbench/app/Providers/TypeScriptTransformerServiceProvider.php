<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Data\EntryTypeIndexData;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Data\GqlToken;
use CraftCms\Cms\Http\ViewModels\FieldEditViewModel;
use CraftCms\Cms\Http\ViewModels\FilesystemsEditViewModel;
use CraftCms\Cms\Http\ViewModels\UserAddressesViewModel;
use CraftCms\Cms\Http\ViewModels\UserPermissionsViewModel;
use CraftCms\Cms\Http\ViewModels\UserPreferencesViewModel;
use CraftCms\Cms\Http\ViewModels\UserProfileViewModel;
use CraftCms\Cms\Http\ViewModels\UserSignInProvidersViewModel;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Route\Data\Route;
use CraftCms\Cms\Update\Data\Updates;
use CraftCms\Cms\User\Data\Permission;
use CraftCms\Cms\User\Data\PermissionGroup;
use CraftCms\Cms\User\Data\UserSettings;
use CraftCms\Cms\View\HtmlFragment;
use DateTimeInterface;
use Spatie\LaravelTypeScriptTransformer\TypeScriptTransformerApplicationServiceProvider;
use Spatie\TypeScriptTransformer\Transformers\EnumTransformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfigFactory;
use Workbench\App\TypeScript\ClassListClassTransformer;
use Workbench\App\TypeScript\ClassListTransformedProvider;
use Workbench\App\TypeScript\ExportedNamespaceWriter;
use Workbench\App\TypeScript\ViewModelTransformer;

class TypeScriptTransformerServiceProvider extends TypeScriptTransformerApplicationServiceProvider
{
    protected function configure(TypeScriptTransformerConfigFactory $config): void
    {
        $config
            ->outputDirectory(dirname(__DIR__, 3).'/resources/js/generated')
            ->writer(new ExportedNamespaceWriter('types.d.ts'))
            ->replaceType(DateTimeInterface::class, 'string')
            ->provider(new ClassListTransformedProvider(
                [
                    GqlSchema::class,
                    GqlToken::class,
                    ImageTransform::class,
                    EntryType::class,
                    EntryTypeIndexData::class,
                    FilesystemsEditViewModel::class,
                    NavItem::class,
                    Permission::class,
                    PermissionGroup::class,
                    Route::class,
                    Updates::class,
                    HtmlFragment::class,
                    FieldEditViewModel::class,
                    UserAddressesViewModel::class,
                    UserPermissionsViewModel::class,
                    UserPreferencesViewModel::class,
                    UserProfileViewModel::class,
                    UserSettings::class,
                    UserSignInProvidersViewModel::class,
                ],
                [
                    new EnumTransformer,
                    new ViewModelTransformer,
                    new ClassListClassTransformer,
                ],
            ));
    }
}
