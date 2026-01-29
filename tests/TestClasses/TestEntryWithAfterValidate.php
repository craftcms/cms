<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses;

use craft\models\FieldLayout;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Validation\Attributes\Ruleset;
use Illuminate\Validation\Validator as LaravelValidator;
use Override;

#[Ruleset(TestEntryRules::class)]
final class TestEntryWithAfterValidate extends Entry
{
    public bool $afterValidateCalled = false;

    private ?FieldLayout $mockFieldLayout = null;

    public function setMockFieldLayout(FieldLayout $fieldLayout): void
    {
        $this->mockFieldLayout = $fieldLayout;
    }

    #[Override]
    public static function hasUris(): bool
    {
        return false;
    }

    #[Override]
    public function getFieldLayout(): ?FieldLayout
    {
        return $this->mockFieldLayout ?? parent::getFieldLayout();
    }

    #[Override]
    public function afterValidate(?LaravelValidator $validator = null): void
    {
        parent::afterValidate($validator);

        $this->afterValidateCalled = true;
        $this->errors()->add('customError', 'Custom error');
    }
}

final class TestEntryRules extends ElementRules {}
