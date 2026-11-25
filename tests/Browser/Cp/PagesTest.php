<?php

declare(strict_types=1);

use CraftCms\Cms\Ai\Models\AgentSkill;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Gql\Models\GqlSchema;
use CraftCms\Cms\Gql\Models\GqlToken;
use CraftCms\Cms\Release\Models\Release;
use CraftCms\Cms\User\Models\User;
use Workbench\App\Http\Controllers\ErrorPagesController;
use Workbench\App\Http\Controllers\PortedPagesController;
use Workbench\App\Http\Controllers\SpecialPagesController;

use function Pest\Laravel\actingAs;

/*
 * Pages that no other browser test covers. Pages with a test file of their own, such as entries or settings, are
 * checked there.
 */

beforeEach(fn () => actingAs(User::first()));

describe('with the workbench content', function () {
    beforeEach(fn () => seedWorkbenchContent());

    it('renders control panel pages', function (string $path, string $heading) {
        assertCpPageRenders($path, $heading);
    })->with([
        'dashboard' => ['dashboard', 'Dashboard'],
        'releases' => ['releases', 'Releases'],
        'new release' => ['releases/create', 'Create a release'],
        'release' => [fn () => 'releases/'.Release::query()->value('id'), 'Spring launch'],
        'new ai skill' => ['ai-skills/new', 'New Skill'],
        'ai skill' => [fn () => 'ai-skills/'.AgentSkill::query()->value('id'), 'Edit Skill'],
        'system report' => ['utilities/system-report', 'System Report'],
        'project config' => ['utilities/project-config', 'Project Config'],
        'caches' => ['utilities/clear-caches', 'Caches'],
        'queue manager' => ['utilities/queue-manager', 'Queue Manager'],
        'ai guidance' => ['utilities/ai-guidance', 'Guidance'],
        'asset indexes' => ['utilities/asset-indexes', 'Asset Indexes'],
        'database backup' => ['utilities/db-backup', 'Database Backup'],
        'deprecation warnings' => ['utilities/deprecation-errors', 'Deprecation Warnings'],
        'find and replace' => ['utilities/find-replace', 'Find and Replace'],
        'migrations' => ['utilities/migrations', 'Migrations'],
        'php info' => ['utilities/php-info', 'PHP Info'],
        'system messages' => ['utilities/system-messages', 'System Messages'],
        'updates' => ['utilities/updates', 'Updates'],
        'graphql schemas' => ['graphql/schemas', 'GraphQL Schemas'],
        'new graphql schema' => ['graphql/schemas/new', 'Create a new GraphQL Schema'],
        'graphql schema' => [fn () => 'graphql/schemas/'.GqlSchema::query()->where('name', 'Headless front end')->value('id'), 'Headless front end'],
        'graphql tokens' => ['graphql/tokens', 'GraphQL Tokens'],
        'new graphql token' => ['graphql/tokens/new', 'Create a new GraphQL token'],
        'graphql token' => [fn () => 'graphql/tokens/'.GqlToken::query()->where('name', 'Front end')->value('id'), 'Front end'],
        'ui kitchen sink' => ['workbench/ui', 'Address control'],
        'chips' => ['workbench/chips', 'Chips'],
        'headings' => ['workbench/headings', 'Headings'],
        'layout slots' => ['workbench/layout-slots', 'Layout Slots plugin page'],
    ]);
});

/**
 * The workbench's samples of pages outside the control panel layout. The standalone preview renders a site template,
 * and the uncaught exception page is the framework's debug page in tests, so neither is checked.
 */
it('renders pages that draw their own document', function (string $path) {
    $cpTrigger = trim((string) Cms::config()->cpTrigger, '/');

    assertNoAccessibilityIssues(
        assertNoJavaScriptErrors(visit("/$cpTrigger/workbench/$path")->assertPresent('css=main')),
    );
})->with(fn () => [
    ...array_map(fn (string $page) => "special-pages/$page", array_diff(SpecialPagesController::pages(), ['preview'])),
    ...array_map(fn (string $page) => "ported-pages/$page", array_keys(PortedPagesController::PAGES)),
    ...array_map(fn (string $page) => "error-pages/$page", array_diff(array_keys(ErrorPagesController::PAGES), ['exception'])),
]);
