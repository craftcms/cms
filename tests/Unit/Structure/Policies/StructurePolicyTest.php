<?php

declare(strict_types=1);

use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Structure\Data\Structure;
use CraftCms\Cms\Structure\Policies\StructurePolicy;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Models\User;

beforeEach(function () {
    $this->policy = app(StructurePolicy::class);
});

it('returns false for structures without ids', function () {
    $user = createStructurePolicyUser(['saveEntries:section-uid']);

    $result = $this->policy->edit($user, new Structure);

    expect($result)->toBeFalse();
});

it('requires save entries permission for the section using the structure', function () {
    $structure = new Structure(['id' => 10]);
    $section = new Section([
        'id' => 1,
        'uid' => 'section-uid',
        'type' => SectionType::Structure,
        'structureId' => 10,
    ]);
    Sections::shouldReceive('getAllSections')->andReturn(collect([$section]));

    expect($this->policy->edit(createStructurePolicyUser([]), $structure))->toBeFalse()
        ->and($this->policy->edit(createStructurePolicyUser(['saveEntries:section-uid']), $structure))->toBeTrue();
});

function createStructurePolicyUser(array $permissions): User
{
    $user = new class extends User
    {
        public array $grantedPermissions = [];

        public function can($abilities, $arguments = []): bool
        {
            if (is_array($abilities)) {
                return array_all($abilities, fn ($ability) => $this->can($ability, $arguments));
            }

            return in_array($abilities, $this->grantedPermissions, true);
        }
    };

    $user->grantedPermissions = $permissions;

    return $user;
}
