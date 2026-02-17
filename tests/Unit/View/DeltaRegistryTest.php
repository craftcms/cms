<?php

declare(strict_types=1);

use CraftCms\Cms\View\DeltaRegistry;

beforeEach(function () {
    $this->registry = app(DeltaRegistry::class);
});

describe('isActive and setActive', function () {
    it('defaults to inactive', function () {
        expect($this->registry->isActive())->toBeFalse();
    });

    it('can be activated', function () {
        $this->registry->setActive(true);

        expect($this->registry->isActive())->toBeTrue();
    });

    it('can be deactivated', function () {
        $this->registry->setActive(true);
        $this->registry->setActive(false);

        expect($this->registry->isActive())->toBeFalse();
    });
});

describe('withActive', function () {
    it('temporarily sets active state during callback', function () {
        $this->registry->setActive(false);

        $this->registry->withActive(true, function () {
            expect($this->registry->isActive())->toBeTrue();
        });

        expect($this->registry->isActive())->toBeFalse();
    });

    it('restores previous active state after callback', function () {
        $this->registry->setActive(true);

        $this->registry->withActive(false, function () {
            expect($this->registry->isActive())->toBeFalse();
        });

        expect($this->registry->isActive())->toBeTrue();
    });

    it('returns the callback result', function () {
        $result = $this->registry->withActive(true, fn () => 'hello');

        expect($result)->toBe('hello');
    });

    it('restores state even when callback throws', function () {
        $this->registry->setActive(false);

        try {
            $this->registry->withActive(true, function () {
                throw new RuntimeException('test');
            });
        } catch (RuntimeException) {
        }

        expect($this->registry->isActive())->toBeFalse();
    });

    it('handles nested calls correctly', function () {
        $this->registry->setActive(false);

        $this->registry->withActive(true, function () {
            expect($this->registry->isActive())->toBeTrue();

            $this->registry->withActive(false, function () {
                expect($this->registry->isActive())->toBeFalse();
            });

            expect($this->registry->isActive())->toBeTrue();
        });

        expect($this->registry->isActive())->toBeFalse();
    });
});

describe('registerName', function () {
    it('does not register when inactive', function () {
        $this->registry->registerName('title');

        expect($this->registry->getNames())->toBeEmpty();
    });

    it('registers a name when active', function () {
        $this->registry->setActive(true);
        $this->registry->registerName('title');

        expect($this->registry->getNames())->toBe(['title']);
    });

    it('registers multiple names', function () {
        $this->registry->setActive(true);
        $this->registry->registerName('title');
        $this->registry->registerName('slug');

        expect($this->registry->getNames())->toBe(['title', 'slug']);
    });

    it('does not track as modified by default', function () {
        $this->registry->setActive(true);
        $this->registry->registerName('title');

        expect($this->registry->getModifiedNames())->toBeEmpty();
    });

    it('tracks as modified when forceModified is true', function () {
        $this->registry->setActive(true);
        $this->registry->registerName('title', forceModified: true);

        expect($this->registry->getModifiedNames())->toBe(['title']);
    });

    it('namespaces the input name with the active input namespace', function () {
        app(\CraftCms\Cms\View\InputNamespace::class)->set('fields');
        $this->registry->setActive(true);
        $this->registry->registerName('title');

        expect($this->registry->getNames())->toBe(['fields[title]']);
    });

    it('registers names within withActive scope', function () {
        $this->registry->withActive(true, function () {
            $this->registry->registerName('postDate');
            $this->registry->registerName('expiryDate');
        });

        expect($this->registry->getNames())->toBe(['postDate', 'expiryDate']);
    });

    it('does not register names within inactive withActive scope', function () {
        $this->registry->setActive(true);

        $this->registry->withActive(false, function () {
            $this->registry->registerName('shouldNotRegister');
        });

        expect($this->registry->getNames())->toBeEmpty();
    });
});

describe('setInitialValue', function () {
    it('does not store when inactive', function () {
        $this->registry->setInitialValue('title', 'Hello');

        expect($this->registry->getInitialValues())->toBeEmpty();
    });

    it('stores a value when active', function () {
        $this->registry->setActive(true);
        $this->registry->setInitialValue('title', 'Hello');

        expect($this->registry->getInitialValues())->toBe(['title' => 'Hello']);
    });

    it('stores null values', function () {
        $this->registry->setActive(true);
        $this->registry->setInitialValue('color', null);

        expect($this->registry->getInitialValues())->toBe(['color' => null]);
    });

    it('stores array values', function () {
        $this->registry->setActive(true);
        $this->registry->setInitialValue('date', [
            'date' => '2026-01-01',
            'time' => '12:00',
            'timezone' => 'UTC',
        ]);

        expect($this->registry->getInitialValues())->toBe([
            'date' => [
                'date' => '2026-01-01',
                'time' => '12:00',
                'timezone' => 'UTC',
            ],
        ]);
    });

    it('overwrites previous values for the same key', function () {
        $this->registry->setActive(true);
        $this->registry->setInitialValue('title', 'First');
        $this->registry->setInitialValue('title', 'Second');

        expect($this->registry->getInitialValues())->toBe(['title' => 'Second']);
    });

    it('namespaces the input name with the active input namespace', function () {
        app(\CraftCms\Cms\View\InputNamespace::class)->set('fields');
        $this->registry->setActive(true);
        $this->registry->setInitialValue('title', 'Hello');

        expect($this->registry->getInitialValues())->toBe(['fields[title]' => 'Hello']);
    });
});

describe('getNames and getModifiedNames', function () {
    it('returns empty arrays by default', function () {
        expect($this->registry->getNames())->toBe([]);
        expect($this->registry->getModifiedNames())->toBe([]);
    });

    it('separates modified from unmodified names', function () {
        $this->registry->setActive(true);
        $this->registry->registerName('title');
        $this->registry->registerName('slug', forceModified: true);
        $this->registry->registerName('body');

        expect($this->registry->getNames())->toBe(['title', 'slug', 'body']);
        expect($this->registry->getModifiedNames())->toBe(['slug']);
    });
});

describe('scoped resolution', function () {
    it('is resolved as a scoped instance', function () {
        $a = app(DeltaRegistry::class);
        $b = app(DeltaRegistry::class);

        expect($a)->toBe($b);
    });
});
