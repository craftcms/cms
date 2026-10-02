<?php

declare(strict_types=1);

use CraftCms\Cms\View\TemplateManager;
use Twig\Error\RuntimeError;

beforeEach(function () {
    $this->manager = app(TemplateManager::class);
});

describe('has some', function () {
    it('returns true when at least one element satisfies the condition', function () {
        $result = $this->manager->renderString(
            '{% if items has some v => v > 3 %}yes{% else %}no{% endif %}',
            ['items' => [1, 2, 3, 4, 5]],
        );

        expect(trim($result))->toBe('yes');
    });

    it('returns false when no elements satisfy the condition', function () {
        $result = $this->manager->renderString(
            '{% if items has some v => v > 10 %}yes{% else %}no{% endif %}',
            ['items' => [1, 2, 3]],
        );

        expect(trim($result))->toBe('no');
    });

    it('returns false for an empty array', function () {
        $result = $this->manager->renderString(
            '{% if items has some v => v > 0 %}yes{% else %}no{% endif %}',
            ['items' => []],
        );

        expect(trim($result))->toBe('no');
    });

    it('works with string comparisons', function () {
        $result = $this->manager->renderString(
            '{% if items has some v => v == "banana" %}found{% else %}not found{% endif %}',
            ['items' => ['apple', 'banana', 'cherry']],
        );

        expect(trim($result))->toBe('found');
    });
});

describe('has every', function () {
    it('returns true when all elements satisfy the condition', function () {
        $result = $this->manager->renderString(
            '{% if items has every v => v > 0 %}yes{% else %}no{% endif %}',
            ['items' => [1, 2, 3]],
        );

        expect(trim($result))->toBe('yes');
    });

    it('returns false when not all elements satisfy the condition', function () {
        $result = $this->manager->renderString(
            '{% if items has every v => v > 2 %}yes{% else %}no{% endif %}',
            ['items' => [1, 2, 3]],
        );

        expect(trim($result))->toBe('no');
    });

    it('returns true for an empty array', function () {
        $result = $this->manager->renderString(
            '{% if items has every v => v > 0 %}yes{% else %}no{% endif %}',
            ['items' => []],
        );

        expect(trim($result))->toBe('yes');
    });

    it('works with object properties', function () {
        $result = $this->manager->renderString(
            '{% if items has every v => v.active %}all active{% else %}not all active{% endif %}',
            ['items' => [
                (object) ['active' => true],
                (object) ['active' => true],
            ]],
        );

        expect(trim($result))->toBe('all active');
    });
});

describe('sandbox', function () {
    it('rejects a non-Closure callable passed to has some', function () {
        $this->manager->renderSandboxedString(
            '{% if {x: "y"} has some "str_contains" %}yes{% else %}no{% endif %}',
        );
    })->throws(RuntimeError::class, 'The callable passed to the "has some" operator must be a Closure in sandbox mode');

    it('rejects a non-Closure callable passed to has every', function () {
        $this->manager->renderSandboxedString(
            '{% if {x: "y"} has every "str_contains" %}yes{% else %}no{% endif %}',
        );
    })->throws(RuntimeError::class, 'The callable passed to the "has every" operator must be a Closure in sandbox mode');

    it('still allows arrow functions with has some when sandboxed', function () {
        $result = $this->manager->renderSandboxedString(
            '{% if items has some v => v > 3 %}yes{% else %}no{% endif %}',
            ['items' => [1, 2, 3, 4, 5]],
        );

        expect(trim($result))->toBe('yes');
    });

    it('still allows arrow functions with has every when sandboxed', function () {
        $result = $this->manager->renderSandboxedString(
            '{% if items has every v => v > 0 %}yes{% else %}no{% endif %}',
            ['items' => [1, 2, 3]],
        );

        expect(trim($result))->toBe('yes');
    });
});
