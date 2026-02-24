<?php

declare(strict_types=1);

use craft\web\twig\Extension as LegacyExtension;
use CraftCms\Cms\Twig\Twig;
use Twig\TwigFilter;
use Twig\TwigFunction;

beforeEach(function () {
    $this->twig = new Twig;
    $this->env = $this->twig->create();
});

describe('split extension registration', function () {
    it('does not register the legacy monolith extension directly', function () {
        expect($this->env->hasExtension(LegacyExtension::class))->toBeFalse();
    });
});

describe('filter parity', function () {
    it('keeps representative filters available', function (string $name) {
        $filter = $this->env->getFilter($name);

        expect($filter)->toBeInstanceOf(TwigFilter::class);
    })->with([
        'core filter' => 't',
        'date filter' => 'date',
        'array filter' => 'group',
        'text filter' => 'truncate',
        'html filter' => 'parseRefs',
    ]);
});

describe('function parity', function () {
    it('keeps representative functions available', function (string $name) {
        $function = $this->env->getFunction($name);

        expect($function)->toBeInstanceOf(TwigFunction::class);
    })->with([
        'core function' => 'entries',
        'date function' => 'date',
        'array function' => 'collect',
        'text function' => 'uuid',
        'html function' => 'svg',
    ]);
});

describe('globals parity', function () {
    it('keeps core globals available', function () {
        $globals = $this->env->getGlobals();

        expect($globals)->toHaveKeys([
            'craft',
            'app',
            'currentSite',
            'now',
        ]);
    });
});
