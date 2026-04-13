<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Events\AuthorizingElement;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Policies\ElementPolicy;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Field\Elements\ContentBlock;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->policy = app(ElementPolicy::class);
});

it('is registered with the gate', function () {
    $user = UserModel::factory()->createElement();
    $element = createElementPolicyElement();

    $result = Gate::forUser($user)->allows('view', $element);

    expect($result)->toBeBool();
});

it('returns null from before for non-elements', function () {
    $user = UserModel::factory()->createElement();

    $result = $this->policy->before($user, 'view', 'not-an-element');

    expect($result)->toBeNull();
});

it('delegates unpublished save canonical checks to a cloned save check', function () {
    $user = UserModel::factory()->createElement();
    $element = createElementPolicyElement();
    $element->draftId = 100;

    Event::listen(AuthorizingElement::class, function (AuthorizingElement $event) use ($element): void {
        expect($event->ability)->toBe('save')
            ->and($event->element === $element)->toBeFalse()
            ->and($event->element->draftId)->toBeNull();

        $event->authorize();
    });

    $result = $this->policy->saveCanonical($user, $element);

    expect($result)->toBeTrue()
        ->and($element->draftId)->toBe(100);
});

it('delegates published save canonical checks to the canonical element', function () {
    $user = UserModel::factory()->createElement();
    $canonical = createElementPolicyElement();
    $element = createElementPolicyElement(canonical: $canonical);

    Event::listen(AuthorizingElement::class, function (AuthorizingElement $event) use ($canonical): void {
        expect($event->ability)->toBe('save')
            ->and($event->element)->toBe($canonical);

        $event->authorize();
    });

    $result = $this->policy->saveCanonical($user, $element);

    expect($result)->toBeTrue();
});

it('allows gate save canonical checks for unpublished drafts when save is authorized', function () {
    $user = UserModel::factory()->createElement();
    $element = createElementPolicyElement();
    $element->draftId = 100;

    Event::listen(AuthorizingElement::class, function (AuthorizingElement $event) use ($element): void {
        if ($event->ability !== 'save') {
            return;
        }

        expect($event->element === $element)->toBeFalse()
            ->and($event->element->draftId)->toBeNull();

        $event->authorize();
    });

    expect(Gate::forUser($user)->check('saveCanonical', $element))->toBeTrue();
});

it('denies gate save canonical checks when the delegated save check is denied', function () {
    $user = UserModel::factory()->createElement();
    $element = createElementPolicyElement();
    $element->draftId = 100;

    Event::listen(AuthorizingElement::class, function (AuthorizingElement $event): void {
        if ($event->ability === 'save') {
            $event->deny();
        }
    });

    expect(Gate::forUser($user)->check('saveCanonical', $element))->toBeFalse();
});

it('returns false for view when the site does not exist', function () {
    $user = UserModel::factory()->createElement();
    $element = createElementPolicyElement(siteId: 999999);

    $result = $this->policy->before($user, 'view', $element);

    expect($result)->toBeFalse();
});

it('returns false for save when the user cannot edit the site', function () {
    $user = UserModel::factory()->createElement();
    $site = Site::factory()->create();
    $element = createElementPolicyElement(siteId: $site->id);

    $result = $this->policy->before($user, 'save', $element);

    expect($result)->toBeFalse();
});

it('continues save checks when the user can edit the site', function () {
    $site = Site::factory()->create();
    $user = UserModel::factory()->withPermissions(["editSite:$site->uid"])->createElement();
    $element = createElementPolicyElement(siteId: $site->id);

    Event::listen(AuthorizingElement::class, function (AuthorizingElement $event) use ($element): void {
        expect($event->ability)->toBe('save')
            ->and($event->element)->toBe($element);

        $event->authorize();
    });

    $result = $this->policy->before($user, 'save', $element);

    expect($result)->toBeTrue();
});

it('bypasses site authorization for other abilities', function () {
    $user = UserModel::factory()->createElement();
    $site = Site::factory()->create();
    $element = createElementPolicyElement(siteId: $site->id);

    $result = $this->policy->before($user, 'delete', $element);

    expect($result)->toBeNull();
});

it('falls through when nested elements do not have a container field', function () {
    $user = UserModel::factory()->createElement();
    $element = createElementPolicyNestedElement();

    $result = $this->policy->before($user, 'view', $element);

    expect($result)->toBeNull();
});

it('delegates nested view checks to the field', function () {
    $user = UserModel::factory()->createElement();
    $field = createElementPolicyField(view: true);
    $element = createElementPolicyNestedElement(field: $field);

    $result = $this->policy->before($user, 'view', $element);

    expect($result)->toBeTrue();
});

it('returns false when nested save is denied by the field', function () {
    $user = UserModel::factory()->createElement();
    $field = createElementPolicyField(save: false);
    $element = createElementPolicyNestedElement(field: $field);

    $result = $this->policy->before($user, 'save', $element);

    expect($result)->toBeFalse();
});

it('returns null when nested save authorization is unresolved', function () {
    $user = UserModel::factory()->createElement();
    $field = createElementPolicyField(save: null);
    $element = createElementPolicyNestedElement(field: $field);

    $result = $this->policy->before($user, 'save', $element);

    expect($result)->toBeNull();
});

it('allows nested save when the field allows it without a layout element', function () {
    $user = UserModel::factory()->createElement();
    $field = createElementPolicyField(save: true);
    $element = createElementPolicyNestedElement(field: $field);

    $result = $this->policy->before($user, 'save', $element);

    expect($result)->toBeTrue();
});

it('returns false for nested save when the field layout element exists but there is no owner', function () {
    $user = UserModel::factory()->createElement();
    $field = createElementPolicyField(save: true);
    $field->layoutElement = createElementPolicyLayoutElement(editable: true);
    $element = createElementPolicyNestedElement(field: $field);

    $result = $this->policy->before($user, 'save', $element);

    expect($result)->toBeFalse();
});

it('returns the layout element editability for nested save when an owner exists', function (bool $editable, bool $expected) {
    $user = UserModel::factory()->createElement();
    $field = createElementPolicyField(save: true);
    $field->layoutElement = createElementPolicyLayoutElement(editable: $editable);
    $element = createElementPolicyNestedElement(
        field: $field,
        owner: createElementPolicyElement(),
    );

    $result = $this->policy->before($user, 'save', $element);

    expect($result)->toBe($expected);
})->with([
    'editable owner field' => [true, true],
    'non-editable owner field' => [false, false],
]);

it('delegates nested delete checks to the field', function () {
    $user = UserModel::factory()->createElement();
    $field = createElementPolicyField(delete: true);
    $element = createElementPolicyNestedElement(field: $field);

    $result = $this->policy->before($user, 'delete', $element);

    expect($result)->toBeTrue();
});

it('delegates nested duplicate checks to the field', function () {
    $user = UserModel::factory()->createElement();
    $field = createElementPolicyField(duplicate: true);
    $element = createElementPolicyNestedElement(field: $field);

    $result = $this->policy->before($user, 'duplicate', $element);

    expect($result)->toBeTrue();
});

it('delegates nested duplicate as draft checks to the field', function () {
    $user = UserModel::factory()->createElement();
    $field = createElementPolicyField(duplicate: true);
    $element = createElementPolicyNestedElement(field: $field);

    $result = $this->policy->before($user, 'duplicateAsDraft', $element);

    expect($result)->toBeTrue();
});

it('delegates nested delete for site checks to the field', function () {
    $user = UserModel::factory()->createElement();
    $field = createElementPolicyField(deleteForSite: true);
    $element = createElementPolicyNestedElement(field: $field);

    $result = $this->policy->before($user, 'deleteForSite', $element);

    expect($result)->toBeTrue();
});

it('falls through to the authorizing event for nested abilities without field delegation', function () {
    $user = UserModel::factory()->createElement();
    $field = createElementPolicyField();
    $element = createElementPolicyNestedElement(field: $field);

    $result = $this->policy->before($user, 'copy', $element);

    expect($result)->toBeNull();
});

it('returns the authorizing event default authorization', function () {
    $user = UserModel::factory()->createElement();
    $element = createElementPolicyElement();

    $result = $this->policy->before($user, 'view', $element);

    expect($result)->toBeNull();
});

it('allows authorizing event listeners to authorize an element', function () {
    $user = UserModel::factory()->createElement();
    $element = createElementPolicyElement();

    Event::listen(AuthorizingElement::class, function (AuthorizingElement $event): void {
        $event->authorize();
    });

    $result = $this->policy->before($user, 'view', $element);

    expect($result)->toBeTrue();
});

it('allows authorizing event listeners to deny an element', function () {
    $user = UserModel::factory()->createElement();
    $element = createElementPolicyElement();

    Event::listen(AuthorizingElement::class, function (AuthorizingElement $event): void {
        $event->deny();
    });

    $result = $this->policy->before($user, 'view', $element);

    expect($result)->toBeFalse();
});

it('returns false for built-in abilities via __call', function (string $ability) {
    $user = UserModel::factory()->createElement();
    $element = createElementPolicyElement();

    $result = $this->policy->$ability($user, $element);

    expect($result)->toBeFalse();
})->with([
    'view',
    'save',
    'delete',
    'duplicate',
    'copy',
    'createDrafts',
    'deleteForSite',
    'duplicateAsDraft',
]);

it('throws for unsupported methods via __call', function () {
    $user = UserModel::factory()->createElement();
    $element = createElementPolicyElement();

    $this->policy->unsupportedAbility($user, $element);
})->throws(BadMethodCallException::class, 'Method unsupportedAbility does not exist.');

function createElementPolicyElement(?int $siteId = null, ?ElementInterface $canonical = null): Element
{
    $element = new class extends Element
    {
        public ?ElementInterface $canonicalResult = null;

        public function getCanonical(bool $anySite = false): ElementInterface
        {
            return $this->canonicalResult ?? parent::getCanonical($anySite);
        }
    };

    $element->siteId = $siteId;
    $element->canonicalResult = $canonical;

    return $element;
}

function createElementPolicyNestedElement(
    ?ElementContainerFieldInterface $field = null,
    ?ElementInterface $owner = null,
): ContentBlock {
    $element = new class extends ContentBlock
    {
        public ?ElementContainerFieldInterface $mockField = null;

        public ?ElementInterface $mockOwner = null;

        public function getField(): ?ElementContainerFieldInterface
        {
            return $this->mockField;
        }

        public function getOwner(): ?ElementInterface
        {
            return $this->mockOwner;
        }
    };

    $element->siteId = null;
    $element->mockField = $field;
    $element->mockOwner = $owner;

    return $element;
}

function createElementPolicyField(
    ?bool $view = null,
    ?bool $save = null,
    ?bool $delete = null,
    ?bool $duplicate = null,
    ?bool $deleteForSite = null,
): ElementContainerFieldInterface {
    $field = new class extends Field implements ElementContainerFieldInterface
    {
        public ?bool $viewResult = null;

        public ?bool $saveResult = null;

        public ?bool $deleteResult = null;

        public ?bool $duplicateResult = null;

        public ?bool $deleteForSiteResult = null;

        public static function displayName(): string
        {
            return 'Test Field';
        }

        public static function icon(): string
        {
            return 'circle';
        }

        public static function supportedTranslationMethods(): array
        {
            return [];
        }

        public static function phpType(): string
        {
            return 'mixed';
        }

        public static function dbType(): array|string|null
        {
            return null;
        }

        public function getFieldLayoutProviders(): array
        {
            return [];
        }

        public function getUriFormatForElement(NestedElementInterface $element): ?string
        {
            return null;
        }

        public function getRouteForElement(NestedElementInterface $element): mixed
        {
            return null;
        }

        public function getSupportedSitesForElement(NestedElementInterface $element): array
        {
            return [];
        }

        public function canViewElement(NestedElementInterface $element, User $user): ?bool
        {
            return $this->viewResult;
        }

        public function canSaveElement(NestedElementInterface $element, User $user): ?bool
        {
            return $this->saveResult;
        }

        public function canDeleteElement(NestedElementInterface $element, User $user): ?bool
        {
            return $this->deleteResult;
        }

        public function canDuplicateElement(NestedElementInterface $element, User $user): ?bool
        {
            return $this->duplicateResult;
        }

        public function canDeleteElementForSite(NestedElementInterface $element, User $user): ?bool
        {
            return $this->deleteForSiteResult;
        }
    };

    $field->viewResult = $view;
    $field->saveResult = $save;
    $field->deleteResult = $delete;
    $field->duplicateResult = $duplicate;
    $field->deleteForSiteResult = $deleteForSite;

    return $field;
}

function createElementPolicyLayoutElement(bool $editable): CustomField
{
    $layoutElement = new class extends CustomField
    {
        public bool $editableResult = false;

        public function editable(?ElementInterface $element): bool
        {
            return $this->editableResult;
        }
    };

    $layoutElement->editableResult = $editable;

    return $layoutElement;
}
