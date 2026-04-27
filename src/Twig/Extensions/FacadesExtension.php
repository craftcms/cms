<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Extensions;

use CraftCms\Cms\Support\Facades\Addresses;
use CraftCms\Cms\Support\Facades\Announcements;
use CraftCms\Cms\Support\Facades\AssetIndexer;
use CraftCms\Cms\Support\Facades\Assets;
use CraftCms\Cms\Support\Facades\Auth;
use CraftCms\Cms\Support\Facades\BulkOps;
use CraftCms\Cms\Support\Facades\Conditions;
use CraftCms\Cms\Support\Facades\DeltaRegistry;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\Drafts;
use CraftCms\Cms\Support\Facades\ElementActions;
use CraftCms\Cms\Support\Facades\ElementActivity;
use CraftCms\Cms\Support\Facades\ElementCaches;
use CraftCms\Cms\Support\Facades\ElementExporters;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\ElementSources;
use CraftCms\Cms\Support\Facades\Entries;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Filesystems;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\Support\Facades\Gql;
use CraftCms\Cms\Support\Facades\HtmlSanitizers;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Images;
use CraftCms\Cms\Support\Facades\ImageTransforms;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\JobProgress;
use CraftCms\Cms\Support\Facades\Markdown;
use CraftCms\Cms\Support\Facades\OAuth;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\Facades\Plugins;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Revisions;
use CraftCms\Cms\Support\Facades\Search;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Security;
use CraftCms\Cms\Support\Facades\SiteGroups;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Structures;
use CraftCms\Cms\Support\Facades\TemplateHooks;
use CraftCms\Cms\Support\Facades\Twig;
use CraftCms\Cms\Support\Facades\Updates;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Twig\Variables\Facade;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class FacadesExtension extends AbstractExtension implements GlobalsInterface
{
    public function getGlobals(): array
    {
        return collect([
            'Addresses' => Addresses::class,
            'Announcements' => Announcements::class,
            'AssetIndexer' => AssetIndexer::class,
            'Auth' => Auth::class,
            'Assets' => Assets::class,
            'BulkOps' => BulkOps::class,
            'Conditions' => Conditions::class,
            'DeltaRegistry' => DeltaRegistry::class,
            'Deprecator' => Deprecator::class,
            'Drafts' => Drafts::class,
            'ElementActions' => ElementActions::class,
            'ElementActivity' => ElementActivity::class,
            'ElementCaches' => ElementCaches::class,
            'ElementExporters' => ElementExporters::class,
            'Elements' => Elements::class,
            'ElementSources' => ElementSources::class,
            'Entries' => Entries::class,
            'EntryTypes' => EntryTypes::class,
            'Fields' => Fields::class,
            'Filesystems' => Filesystems::class,
            'Folders' => Folders::class,
            'Gql' => Gql::class,
            'HtmlSanitizers' => HtmlSanitizers::class,
            'HtmlStack' => HtmlStack::class,
            'I18N' => I18N::class,
            'Images' => Images::class,
            'ImageTransforms' => ImageTransforms::class,
            'InputNamespace' => InputNamespace::class,
            'JobProgress' => JobProgress::class,
            'Markdown' => Markdown::class,
            'OAuth' => OAuth::class,
            'Path' => Path::class,
            'Plugins' => Plugins::class,
            'ProjectConfig' => ProjectConfig::class,
            'Revisions' => Revisions::class,
            'Search' => Search::class,
            'Sections' => Sections::class,
            'Security' => Security::class,
            'SiteGroups' => SiteGroups::class,
            'Sites' => Sites::class,
            'Structures' => Structures::class,
            'TemplateHooks' => TemplateHooks::class,
            'Twig' => Twig::class,
            'Updates' => Updates::class,
            'UserGroups' => UserGroups::class,
            'UserPermissions' => UserPermissions::class,
            'Users' => Users::class,
            'Volumes' => Volumes::class,
        ])->map(fn (string $class) => new Facade($class))->all();
    }
}
