<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\EventTypes\ElementCreated;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Navigation;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\Eloquent\EloquentElement;
use CraftCms\Cms\Element\Eloquent\EloquentElements;
use CraftCms\Cms\Element\Eloquent\ModelTrashBehavior;
use CraftCms\Cms\Element\Events\ElementSaving;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\TextField;
use CraftCms\Cms\GarbageCollection\Actions\HardDeleteElements;
use CraftCms\Cms\Http\Controllers\Elements\ActivityTimelineController;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\ElementIndexController;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\UserPermissions as UserPermissionsFacade;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Tests\TestClasses\Element\Eloquent\Customer;
use CraftCms\Cms\Tests\TestClasses\Element\Eloquent\CustomerElement;
use CraftCms\Cms\Tests\TestClasses\Element\Eloquent\CustomersField;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\UserPermissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Once;
use Inertia\Testing\AssertableInertia;
use Twig\Sandbox\SecurityNotAllowedPropertyError;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\renderSandboxedString;
use function CraftCms\Cms\renderString;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    createEloquentElementsTable('customers', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('company')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
    createEloquentElementsTable('articles', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->boolean('enabled');
        $table->text('body');
    });
    createEloquentElementsTable('customer_notes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('customer_id');
        $table->string('body');
        $table->timestamps();
    });

    // Registered types add their routes to the router, rather than finding them in the route cache
    app()->forgetInstance('routes.cached');

    app(EloquentElements::class)->register(CustomerElement::class);

    actingAs(User::findOne());
});

afterEach(function () {
    Route::getRoutes()->refreshNameLookups();
    app()->forgetInstance('routes.cached');
});

/** @param list<string> $permissions */
function actingAsEloquentElementsCpUser(array $permissions = []): void
{
    $user = UserModel::factory()->create();
    UserPermissionsFacade::saveUserPermissions($user->id, ['accessCp', ...$permissions]);
    actingAs($user);
}

/**
 * MySQL commits the test's transaction on DDL, which would leave the test's writes behind. There,
 * each table is created once per process, between transactions, before the test writes anything.
 */
function createEloquentElementsTable(string $name, Closure $blueprint): void
{
    if (! DB::isMysql()) {
        Schema::create($name, $blueprint);

        return;
    }

    if (Schema::hasTable($name)) {
        return;
    }

    DB::commit();
    Schema::create($name, $blueprint);
    DB::beginTransaction();
}

it('gives a model saved outside Craft an element that hydrates the model', function () {
    $customer = Customer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);

    $element = CustomerElement::find()->one();

    expect($element->getModel()->is($customer))->toBeTrue()
        ->and($element->email)->toBe('ada@example.com')
        ->and($element->getUiLabel())->toBe('Ada Lovelace')
        ->and(CustomerElement::findForModel($customer)?->id)->toBe($element->id);
});

it('finds elements by their model attributes through search', function () {
    Customer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
    Customer::create(['name' => 'Grace Hopper', 'email' => 'grace@example.com']);

    expect(CustomerElement::find()->search('grace')->collect()->map->email->all())
        ->toBe(['grace@example.com']);
});

it('keeps hidden model attributes out of the index and search', function () {
    app(EloquentElements::class)->register(TestKeptCustomerElement::class);

    TestKeptCustomer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'company' => 'Analytical Engines']);

    expect(TestKeptCustomerElement::find()->search('Analytical')->count())->toBe(0)
        ->and(TestKeptCustomerElement::find()->search('Lovelace')->count())->toBe(1)
        ->and(TestKeptCustomerElement::tableAttributes())->not->toHaveKey('company')
        ->and(TestKeptCustomerElement::defaultTableAttributes('*'))->not->toContain('company')
        ->and(array_column(TestKeptCustomerElement::sortOptions(), 'attribute'))->not->toContain('company');
});

it('edits an element beneath its index, with a crumb back to it', function () {
    $element = CustomerElement::findForModel(Customer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']));

    expect($element->getCpEditUrl())->toBe(cp_url("customers/$element->id"));

    get($element->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('elements/Edit')
            ->where('crumbs.0.label', 'Customers')
            ->where('crumbs.0.href', cp_url('customers')));
});

it('refuses another element type’s ID at its edit URL', function () {
    get(cp_url('customers/'.User::findOne()->id))->assertBadRequest();
});

it('sets model attributes passed to the element', function () {
    expect(new CustomerElement(['name' => 'Ada Lovelace'])->name)->toBe('Ada Lovelace');
});

it('duplicates an element with new model attributes', function () {
    $element = CustomerElement::findForModel(Customer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']));

    Elements::duplicateElement($element, ['email' => 'countess@example.com']);

    expect(Customer::where('email', 'countess@example.com')->exists())->toBeTrue();
});

it('leaves out model attributes named like the element’s own properties', function () {
    expect(TestArticleElement::modelAttributes())->toBe(['body']);
});

it('labels and searches elements by a title column', function () {
    app(EloquentElements::class)->register(TestArticleElement::class);

    $article = TestArticle::create(['title' => 'Notes on the Analytical Engine', 'enabled' => true, 'body' => '…']);

    expect(TestArticleElement::findForModel($article)->getUiLabel())->toBe('Notes on the Analytical Engine')
        ->and(TestArticleElement::find()->search('Analytical')->count())->toBe(1);
});

it('syncs a model touched by a related model', function () {
    $customer = Customer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
    $dateUpdated = CustomerElement::findForModel($customer)->dateUpdated;

    $this->travel(1)->hours();
    $customer->company = 'Analytical Engines';
    $note = new TestCustomerNote(['body' => 'Prefers email']);
    $note->customer()->associate($customer);
    $note->save();

    expect(CustomerElement::findForModel($customer)->dateUpdated)->toBeGreaterThan($dateUpdated);
});

it('keeps a model’s element when its key changes', function () {
    $customer = Customer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
    $elementId = CustomerElement::findForModel($customer)->id;

    $customer->id = 1000;
    $customer->save();

    expect(CustomerElement::findForModel($customer)?->id)->toBe($elementId)
        ->and(DB::table(Table::ELEMENTS_MODELS)->count())->toBe(1);
});

it('applies the model’s global scopes to element queries', function () {
    app(EloquentElements::class)->register(TestScopedCustomerElement::class);

    TestScopedCustomer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'company' => 'Analytical Engines']);
    $hidden = TestScopedCustomer::create(['name' => 'Grace Hopper', 'email' => 'grace@example.com', 'company' => 'Navy']);

    expect(TestScopedCustomerElement::find()->collect()->map->email->all())->toBe(['ada@example.com'])
        ->and(TestScopedCustomerElement::findForModel($hidden))->not->toBeNull();
});

it('keeps the model’s global scopes from being lifted through index criteria', function () {
    app(EloquentElements::class)->register(TestScopedCustomerElement::class);

    TestScopedCustomer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'company' => 'Analytical Engines']);
    TestScopedCustomer::create(['name' => 'Grace Hopper', 'email' => 'grace@example.com', 'company' => 'Navy']);

    $response = postJson(action([ElementIndexController::class, 'getElements']), [
        'context' => 'index',
        'elementType' => TestScopedCustomerElement::class,
        'source' => '*',
        'criteria' => ['withoutModelScopes' => true, 'modelScopes' => false],
        'viewState' => ['mode' => 'table', 'static' => false],
    ]);

    expect($response->assertOk()->getContent())->not->toContain('grace@example.com');
});

it('syncs other models an observer writes while an element saves its own', function () {
    $customer = Customer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);

    Customer::saved(function (Customer $saved): void {
        if ($saved->email === 'ada@example.com' && Customer::where('email', 'charles@example.com')->doesntExist()) {
            Customer::create(['name' => 'Charles Babbage', 'email' => 'charles@example.com']);
        }
    });

    $element = CustomerElement::findForModel($customer);
    $element->company = 'Analytical Engines';
    Elements::saveElement($element);

    expect(CustomerElement::findForModel(Customer::where('email', 'charles@example.com')->sole()))->not->toBeNull();
});

it('fails an element save the model refuses', function () {
    $customer = Customer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
    Customer::saving(fn () => false);

    $element = CustomerElement::findForModel($customer);
    $element->email = 'countess@example.com';

    expect(fn () => Elements::saveElement($element))->toThrow(RuntimeException::class)
        ->and($customer->fresh()->email)->toBe('ada@example.com');
});

it('fails the sync when an element can’t be created', function () {
    Customer::withoutEvents(fn () => Customer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']));
    Event::listen(ElementSaving::class, fn (ElementSaving $event) => $event->isValid = false);

    artisan('craft:eloquent-elements:sync')->assertFailed();
});

it('creates elements for model rows that don’t have one', function () {
    $customer = Customer::withoutEvents(fn () => Customer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']));

    artisan('craft:eloquent-elements:sync')->assertSuccessful();

    expect(CustomerElement::findForModel($customer))->not->toBeNull();
});

it('writes control panel edits back to the model', function () {
    $customer = Customer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);

    postJson(cp_url('actions/elements/save'), [
        'elementType' => CustomerElement::class,
        'elementId' => CustomerElement::findForModel($customer)->id,
        'email' => 'countess@example.com',
    ])->assertOk();

    expect($customer->fresh()->email)->toBe('countess@example.com');
});

it('shows model writes on the element’s activity timeline', function () {
    $customer = Customer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
    $element = CustomerElement::findForModel($customer);

    get($element->getCpEditUrl())
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('activityTimelineUrl', Url::actionUrl('elements/activity')));

    app()->forgetScopedInstances();

    postJson(cp_url('actions/elements/save'), [
        'elementType' => CustomerElement::class,
        'elementId' => $element->id,
        'email' => 'countess@example.com',
    ])->assertOk();

    postJson(action(ActivityTimelineController::class), [
        'elementType' => CustomerElement::class,
        'elementId' => $element->id,
        'siteId' => $element->siteId,
    ])
        ->assertOk()
        ->assertJsonCount(2, 'events')
        ->assertJsonPath('events.0.description.text', 'Created')
        ->assertJsonPath('events.1.description.text', 'Updated')
        ->assertJsonPath('events.1.changes', [['label' => 'Email', 'old' => 'ada@example.com', 'new' => 'countess@example.com']]);
});

it('records a new element’s draft being applied as its creation', function () {
    $draft = new CustomerElement;
    $draft->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);
    app(Drafts::class)->saveElementAsDraft($draft, User::findOne()->id, markAsSaved: false);

    postJson(cp_url('actions/elements/apply-draft'), [
        'elementType' => CustomerElement::class,
        'elementId' => $draft->id,
        'draftId' => $draft->draftId,
        'siteId' => $draft->siteId,
        'name' => 'Grace Hopper',
        'email' => 'grace@example.com',
    ])->assertOk();

    expect(ActivityEvent::query()->pluck('eventType')->all())->toBe([ElementCreated::class]);
});

it('validates model attributes with the element’s model rules', function () {
    $customer = Customer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);

    postJson(cp_url('actions/elements/save'), [
        'elementType' => CustomerElement::class,
        'elementId' => CustomerElement::findForModel($customer)->id,
        'email' => 'not-an-email',
    ])->assertBadRequest()->assertJsonValidationErrors('email');

    expect($customer->fresh()->email)->toBe('ada@example.com');
});

it('opens a new element’s editor from a draft, without creating its model', function () {
    $response = get(cp_url('customers/new'));

    $draft = CustomerElement::find()->drafts()->status(null)->sole();

    $response->assertRedirect(Url::urlWithParams($draft->getCpEditUrl(), ['fresh' => 1]));

    expect(Customer::count())->toBe(0);

    get($draft->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('editorActions.primary.label', 'Create customer')
            ->where('editorActions.buttons', fn (Collection $buttons) => $buttons->pluck('label')->doesntContain('Apply draft')));
});

it('passes the index a new element was created from on to its editor', function () {
    $returnUrl = cp_url('customers').':QS:source=*';

    $response = get(cp_url('customers/new').'?'.http_build_query(['returnUrl' => $returnUrl]));

    $draft = CustomerElement::find()->drafts()->status(null)->sole();

    $response->assertRedirect(Url::urlWithParams($draft->getCpEditUrl(), ['fresh' => 1, 'returnUrl' => $returnUrl]));
});

it('creates the model row when a new element’s draft is applied', function () {
    $draft = new CustomerElement;
    $draft->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);
    app(Drafts::class)->saveElementAsDraft($draft, User::findOne()->id, markAsSaved: false);

    postJson(cp_url('actions/elements/apply-draft'), [
        'elementType' => CustomerElement::class,
        'elementId' => $draft->id,
        'draftId' => $draft->draftId,
        'siteId' => $draft->siteId,
        'name' => 'Grace Hopper',
        'email' => 'grace@example.com',
    ])->assertOk();

    expect(Customer::sole()->name)->toBe('Grace Hopper')
        ->and(CustomerElement::findForModel(Customer::sole())?->id)->toBe($draft->id);
});

describe('field layouts defined in code', function () {
    it('shows the element type’s field layout in the editor', function () {
        $customer = Customer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);

        get(CustomerElement::findForModel($customer)->getCpEditUrl())
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('ui.nodes.0.props.label', 'Contact')
                ->where('ui.nodes.1.props.label', 'Company')
                ->where('ui.nodes.1.children.1.component', 'craft:callout'));
    });

    describe('with a custom field', function () {
        beforeEach(function () {
            Field::factory()->create(['name' => 'Notes', 'handle' => 'notes', 'type' => PlainText::class]);
            app(Fields::class)->refreshFields();
            app(EloquentElements::class)->register(TestNotedCustomerElement::class);
        });

        it('queries custom fields by handle', function () {
            foreach (['ada' => 'Prefers email', 'grace' => 'Prefers phone'] as $name => $notes) {
                $element = TestNotedCustomerElement::findForModel(TestKeptCustomer::create(['name' => $name, 'email' => "$name@example.com"]));
                $element->setFieldValue('notes', $notes);
                Elements::saveElement($element);
            }

            expect(TestNotedCustomerElement::find()->notes('Prefers email')->collect()->map->email->all())->toBe(['ada@example.com']);
        });

        it('offers custom fields as index columns', function () {
            expect(app(ElementSources::class)->getSourceTableAttributes(TestNotedCustomerElement::class, '*')->pluck('label')->all())
                ->toContain('Notes');
        });

        it('keeps custom field values across requests', function () {
            $element = TestNotedCustomerElement::findForModel(TestKeptCustomer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']));
            $element->setFieldValue('notes', 'Prefers email');
            Elements::saveElement($element);

            Once::flush();

            expect(TestNotedCustomerElement::find()->id($element->id)->one()->notes)->toBe('Prefers email');
        });
    });
});

describe('templates', function () {
    it('queries the element type through the craft variable', function () {
        Customer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
        Customer::create(['name' => 'Grace Hopper', 'email' => 'grace@example.com']);

        expect(renderString('{{ craft.customers().count() }}'))->toBe('2')
            ->and(renderString("{{ craft.customers({search: 'grace'}).one().email }}"))->toBe('grace@example.com');
    });

    it('lets sandboxed templates read model attributes, except hidden ones', function () {
        app(EloquentElements::class)->register(TestKeptCustomerElement::class);

        $customer = TestKeptCustomerElement::findForModel(TestKeptCustomer::create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'company' => 'Analytical Engines',
        ]));

        expect(renderSandboxedString('{{ customer.email }}', ['customer' => $customer]))->toBe('ada@example.com')
            ->and(fn () => renderSandboxedString('{{ customer.company }}', ['customer' => $customer]))
            ->toThrow(SecurityNotAllowedPropertyError::class)
            ->and(renderSandboxedString("{% for value in customer %}{% if value is same as('Analytical Engines') %}hidden{% endif %}{% endfor %}", ['customer' => $customer]))
            ->toBe('');
    });
});

describe('soft-deleting models', function () {
    it('soft-deletes the model when the element is trashed, and restores it with the element', function () {
        $customer = Customer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);

        Elements::deleteElement(CustomerElement::findForModel($customer));

        expect(Customer::count())->toBe(0)
            ->and(Customer::withTrashed()->count())->toBe(1);

        Elements::restoreElement(CustomerElement::findForModel($customer));

        expect(Customer::count())->toBe(1);
    });

    it('trashes and restores the element with the model', function () {
        $customer = Customer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);

        $customer->delete();

        expect(CustomerElement::findForModel($customer)?->trashed)->toBeTrue();

        $customer->restore();

        expect(CustomerElement::findForModel($customer)?->trashed)->toBeFalse();
    });

    it('hard-deletes the element when the model is force-deleted', function () {
        $customer = Customer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
        $elementId = CustomerElement::findForModel($customer)->id;

        $customer->forceDelete();

        expect(DB::table(Table::ELEMENTS)->where('id', $elementId)->exists())->toBeFalse();
    });
});

describe('models that are kept while their element is trashed', function () {
    beforeEach(function () {
        app(EloquentElements::class)->register(TestKeptCustomerElement::class);
    });

    it('keeps the model when the element is trashed', function () {
        $customer = TestKeptCustomer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);

        Elements::deleteElement(TestKeptCustomerElement::findForModel($customer));

        expect(TestKeptCustomer::count())->toBe(1);
    });

    it('deletes the model when the element is hard-deleted', function () {
        $customer = TestKeptCustomer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);

        Elements::deleteElement(TestKeptCustomerElement::findForModel($customer), hardDelete: true);

        expect(TestKeptCustomer::count())->toBe(0);
    });

    it('keeps an element whose model refuses to be deleted', function () {
        $element = TestKeptCustomerElement::findForModel(TestKeptCustomer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']));
        TestKeptCustomer::deleting(fn () => false);

        expect(fn () => Elements::deleteElement($element, hardDelete: true))->toThrow(RuntimeException::class)
            ->and(DB::table(Table::ELEMENTS)->where('id', $element->id)->exists())->toBeTrue();
    });

    it('leaves an element in the trash when garbage collection can’t delete its model', function () {
        $element = TestKeptCustomerElement::findForModel(TestKeptCustomer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']));

        Elements::deleteElement($element);
        DB::table(Table::ELEMENTS)->where('id', $element->id)->update([
            'dateDeleted' => now()->subSeconds(Cms::config()->softDeleteDuration + 1),
        ]);
        TestKeptCustomer::deleting(fn () => false);

        app(HardDeleteElements::class)();

        expect(DB::table(Table::ELEMENTS)->where('id', $element->id)->exists())->toBeTrue()
            ->and(TestKeptCustomer::count())->toBe(1);
    });

    it('deletes the model when garbage collection purges the trashed element', function () {
        $customer = TestKeptCustomer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
        $element = TestKeptCustomerElement::findForModel($customer);

        Elements::deleteElement($element);
        DB::table(Table::ELEMENTS)->where('id', $element->id)->update([
            'dateDeleted' => now()->subSeconds(Cms::config()->softDeleteDuration + 1),
        ]);

        app(HardDeleteElements::class)();

        expect(TestKeptCustomer::count())->toBe(0);
    });
});

it('refuses to register a second element type for the same model', function () {
    app(EloquentElements::class)->register(TestKeptCustomerElement::class, TestNotedCustomerElement::class);
})->throws(InvalidArgumentException::class, 'is already represented by');

it('refuses a control panel URI another route already answers', function () {
    app(EloquentElements::class)->register(TestUsersElement::class);
})->throws(InvalidArgumentException::class, 'is taken by');

it('refuses to register an element that soft-deletes a model without SoftDeletes', function () {
    app(EloquentElements::class)->register(TestMisconfiguredCustomerElement::class);
})->throws(InvalidArgumentException::class, 'doesn’t use SoftDeletes');

describe('registration', function () {
    it('gives the element type an index and a nav item', function () {
        Customer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);

        get(cp_url('customers'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('elements/Index')
                ->where('elementType', CustomerElement::class)
                ->where('newElement.label', 'New customer')
                ->where('newElement.href', cp_url('customers/new')));

        $content = collect(app(Navigation::class)->getTree())->firstWhere('label', 'Content');

        expect(collect($content->subnav)->firstWhere('label', 'Customers')?->href)->toBe(cp_url('customers'));
    });

    it('leaves a lone source out of the nav', function () {
        [$item] = app(EloquentElements::class)->navItems();

        expect($item->subnav)->toBeFalse();
    });

    it('lists the sources under the nav item once there are several', function () {
        app(EloquentElements::class)->register(TestSegmentedCustomerElement::class);

        $item = collect(app(EloquentElements::class)->navItems())->firstWhere('label', 'Test Kept Customers');

        expect(collect($item->subnav)->pluck('label')->all())->toBe(['All customers', 'Companies']);
    });

    it('keeps the index and nav item from non-admins when the model has no policy', function () {
        actingAsEloquentElementsCpUser();

        get(cp_url('customers'))->assertForbidden();
        get(cp_url('customers/new'))->assertForbidden();

        expect(app(EloquentElements::class)->navItems())->toBe([]);
    });

    describe('permissions', function () {
        it('registers Craft permissions for the element type', function () {
            $group = app(UserPermissions::class)->getAllPermissions()->firstWhere('handle', 'elements:customer');

            expect($group->heading)->toBe('Customers')
                ->and($group->keys)->toBe(['elements:customer:view', 'elements:customer:create', 'elements:customer:edit', 'elements:customer:delete']);
        });

        it('grants access through those permissions when the model has no policy', function () {
            actingAsEloquentElementsCpUser(['elements:customer:view', 'elements:customer:edit']);

            $customer = Customer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);

            get(cp_url('customers'))->assertOk();
            get(CustomerElement::findForModel($customer)->getCpEditUrl())->assertOk();
            get(cp_url('customers/new'))->assertForbidden();

            expect(collect(app(EloquentElements::class)->navItems())->pluck('label')->all())->toBe(['Customers']);
        });

        it('keeps the index endpoints from users who can’t view the index, even when the type defines its own sources', function () {
            app(EloquentElements::class)->register(TestSegmentedCustomerElement::class);
            TestKeptCustomer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);

            actingAsEloquentElementsCpUser();

            $response = postJson(action([ElementIndexController::class, 'getElements']), [
                'context' => 'index',
                'elementType' => TestSegmentedCustomerElement::class,
                'source' => '*',
                'viewState' => ['mode' => 'table', 'static' => false],
            ]);

            expect($response->assertOk()->getContent())->not->toContain('ada@example.com');
        });

        it('hands a model policy the user the auth guard returns', function () {
            Gate::policy(Customer::class, TestCustomerPolicy::class);

            actingAsEloquentElementsCpUser();

            $customer = Customer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);

            get(cp_url('customers'))->assertOk();

            postJson(cp_url('actions/elements/save'), [
                'elementType' => CustomerElement::class,
                'elementId' => CustomerElement::findForModel($customer)->id,
                'email' => 'countess@example.com',
            ])->assertOk();

            expect($customer->fresh()->email)->toBe('countess@example.com');
        });

        it('hands a model policy the user being checked when they aren’t signed in', function () {
            Gate::policy(Customer::class, TestCustomerPolicy::class);

            $element = CustomerElement::findForModel(Customer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']));

            expect($element->canView(UserModel::factory()->createElement(['admin' => false, 'email' => 'ada@example.com'])))->toBeTrue()
                ->and($element->canView(UserModel::factory()->createElement(['admin' => false, 'email' => 'grace@example.com'])))->toBeFalse();
        });
    });
});

describe('relation fields', function () {
    it('registers the type’s relation field type, named after the type', function () {
        expect(app(Fields::class)->getAllFieldTypes())->toContain(CustomersField::class)
            ->and(CustomersField::displayName())->toBe('Customers');
    });

    it('relates elements to the type’s elements', function () {
        $customer = CustomerElement::findForModel(Customer::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']));
        $entry = EntryModel::factory()
            ->withField('customers', CustomersField::class, value: [$customer->id])
            ->createElementWithFields()
            ->element;

        $lazy = Entry::find()->id($entry->id)->one()->customers->all();
        $eager = Entry::find()->id($entry->id)->with(['customers'])->one()->customers->all();

        expect(array_map(fn (CustomerElement $customer) => $customer->email, $lazy))->toBe(['ada@example.com'])
            ->and(array_map(fn (CustomerElement $customer) => $customer->email, $eager))->toBe(['ada@example.com'])
            ->and(Entry::find()->relatedTo($customer)->ids())->toBe([$entry->id]);
    });
});

class TestKeptCustomer extends Model
{
    #[Override]
    protected $table = 'customers';

    #[Override]
    protected $fillable = ['name', 'email', 'company'];

    #[Override]
    protected $hidden = ['company'];
}

/** @extends EloquentElement<TestKeptCustomer> */
class TestKeptCustomerElement extends EloquentElement
{
    public static function refHandle(): string
    {
        return 'test-kept-customer';
    }

    public static function modelClass(): string
    {
        return TestKeptCustomer::class;
    }
}

class TestMisconfiguredCustomerElement extends TestKeptCustomerElement
{
    public static function refHandle(): string
    {
        return 'test-misconfigured-customer';
    }

    public static function trashBehavior(): ModelTrashBehavior
    {
        return ModelTrashBehavior::SoftDelete;
    }
}

/** @extends EloquentElement<TestKeptCustomer> */
class TestNotedCustomerElement extends TestKeptCustomerElement
{
    public static function refHandle(): string
    {
        return 'test-noted-customer';
    }

    public static function defineFieldLayout(FieldLayout $layout): FieldLayout
    {
        return $layout->tab('Content', fn (FieldLayoutTab $tab) => $tab
            ->add(new TextField(['attribute' => 'name', 'label' => 'Name']))
            ->field('notes'));
    }
}

class TestSegmentedCustomerElement extends TestKeptCustomerElement
{
    public static function refHandle(): string
    {
        return 'test-segmented-customer';
    }

    protected static function defineSources(string $context): array
    {
        return [
            ['key' => '*', 'label' => 'All customers', 'criteria' => []],
            ['key' => 'companies', 'label' => 'Companies', 'criteria' => []],
        ];
    }
}

class TestCustomerPolicy
{
    public function viewAny(UserModel $user): bool
    {
        return true;
    }

    public function view(UserModel $user, Customer $customer): bool
    {
        return $user->is(currentUser()) || $user->email === $customer->email;
    }

    public function update(UserModel $user, Customer $customer): bool
    {
        return true;
    }
}

class TestUsersElement extends TestKeptCustomerElement
{
    public static function refHandle(): string
    {
        return 'test-user';
    }

    public static function cpIndexUri(): string
    {
        return 'users';
    }
}

class TestScopedCustomer extends Model
{
    #[Override]
    protected $table = 'customers';

    #[Override]
    protected $fillable = ['name', 'email', 'company'];

    protected static function booted(): void
    {
        static::addGlobalScope('company', fn (Builder $query) => $query->where('company', 'Analytical Engines'));
    }
}

/** @extends EloquentElement<TestScopedCustomer> */
class TestScopedCustomerElement extends EloquentElement
{
    public static function modelClass(): string
    {
        return TestScopedCustomer::class;
    }
}

class TestArticle extends Model
{
    #[Override]
    protected $table = 'articles';

    #[Override]
    protected $guarded = [];

    #[Override]
    public $timestamps = false;
}

/** @extends EloquentElement<TestArticle> */
class TestArticleElement extends EloquentElement
{
    public static function modelClass(): string
    {
        return TestArticle::class;
    }
}

class TestCustomerNote extends Model
{
    #[Override]
    protected $table = 'customer_notes';

    #[Override]
    protected $guarded = [];

    #[Override]
    protected $touches = ['customer'];

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
