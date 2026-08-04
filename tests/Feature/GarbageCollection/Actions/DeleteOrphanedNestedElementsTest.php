<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedNestedElements;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->action = app(DeleteOrphanedNestedElements::class, [
        'elementType' => TestNestedElement::class,
        'table' => 'test_nested_elements',
    ]);

    // Set up test database tables
    setupTestTables();
});

afterEach(function () {
    tearDownTestTables();
});

// Mock element class for testing
class TestNestedElement
{
    public static function pluralLowerDisplayName(): string
    {
        return 'test nested elements';
    }
}

it('deletes elements missing from elements_owners table', function () {
    // Create test data
    $elementId1 = Element::factory()->create()->id;
    $elementId2 = Element::factory()->create()->id;
    $validElementId = Element::factory()->create()->id;

    $fieldId = Field::factory()->create()->id;

    // Create nested elements with field IDs
    createTestNestedElement($elementId1, $fieldId);
    createTestNestedElement($elementId2, $fieldId);
    createTestNestedElement($validElementId, $fieldId);

    // Only create elements_owners entry for the valid element
    DB::table(Table::ELEMENTS_OWNERS)->insert([
        'elementId' => $validElementId,
        'ownerId' => $elementId1,
        'sortOrder' => 1,
    ]);

    // Run the action
    $this->action->__invoke();

    // Assert orphaned elements were deleted
    expect(DB::table(Table::ELEMENTS)->where('id', $elementId1)->exists())->toBeFalse();
    expect(DB::table(Table::ELEMENTS)->where('id', $elementId2)->exists())->toBeFalse();
    expect(DB::table(Table::ELEMENTS)->where('id', $validElementId)->exists())->toBeTrue();
});

it('deletes elements with invalid field IDs', function () {
    // Create test data
    $elementId1 = Element::factory()->create()->id;
    $elementId2 = Element::factory()->create()->id;
    $validElementId = Element::factory()->create()->id;

    $validFieldId = Field::factory()->create()->id;
    $invalidFieldId = 99999; // Non-existent field ID

    // Create nested elements
    $nestedElementId1 = createTestNestedElement($elementId1, $invalidFieldId);
    $nestedElementId2 = createTestNestedElement($elementId2, $invalidFieldId);
    $nestedElementId3 = createTestNestedElement($validElementId, $validFieldId);

    // Create elements_owners entries for all
    DB::table(Table::ELEMENTS_OWNERS)->insert([
        'elementId' => $nestedElementId1,
        'ownerId' => $elementId1,
        'sortOrder' => 1,
    ]);
    DB::table(Table::ELEMENTS_OWNERS)->insert([
        'elementId' => $nestedElementId2,
        'ownerId' => $elementId2,
        'sortOrder' => 1,
    ]);
    DB::table(Table::ELEMENTS_OWNERS)->insert([
        'elementId' => $nestedElementId3,
        'ownerId' => $validElementId,
        'sortOrder' => 1,
    ]);

    // Run the action
    $this->action->__invoke();

    // Assert elements with invalid field IDs were deleted
    expect(DB::table(Table::ELEMENTS)->where('id', $elementId1)->exists())->toBeFalse();
    expect(DB::table(Table::ELEMENTS)->where('id', $elementId2)->exists())->toBeFalse();
    expect(DB::table(Table::ELEMENTS)->where('id', $validElementId)->exists())->toBeTrue();
});

it('does not delete anything when no orphaned elements exist', function () {
    // Create valid test data
    $elementId = Element::factory()->create()->id;
    $fieldId = Field::factory()->create()->id;

    $nestedElementId = createTestNestedElement($elementId, $fieldId);

    DB::table(Table::ELEMENTS_OWNERS)->insert([
        'elementId' => $nestedElementId,
        'ownerId' => $elementId,
        'sortOrder' => 1,
    ]);

    // Count elements before
    $countBefore = DB::table(Table::ELEMENTS)->count();

    // Run the action
    $this->action->__invoke();

    // Count should remain the same
    $countAfter = DB::table(Table::ELEMENTS)->count();
    expect($countAfter)->toBe($countBefore);
});

// Helper methods
function setupTestTables(): void
{
    // Create test nested elements table
    Schema::create('test_nested_elements', function ($table) {
        $table->id();
        $table->unsignedBigInteger('fieldId')->nullable();
        $table->unsignedBigInteger('customFieldId')->nullable();
        $table->timestamps();
    });
}

function tearDownTestTables(): void
{
    Schema::dropIfExists('test_nested_elements');
}

function createTestNestedElement(int $elementId, ?int $fieldId): int
{
    return DB::table('test_nested_elements')->insertGetId([
        'id' => $elementId,
        'fieldId' => $fieldId,
    ]);
}
