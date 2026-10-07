<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Address\Validation\AddressRules;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Deprecator\Deprecator;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Twig\Exceptions\TemplateLoaderException;
use CraftCms\Cms\View\TemplateManager;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\RulesetValidation\Attributes\Ruleset;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Markup;

#[Ruleset(AddressRules::class)]
class TestAddressForFormFields extends Address
{
    #[Override]
    public function getFieldLayout(): FieldLayout
    {
        return app(Addresses::class)->getFieldLayout();
    }
}

describe('fieldHtml', function () {
    it('renders the field container and optional label', function () {
        $html = FormFields::fieldHtml('<input>');
        $labelHtml = FormFields::fieldHtml('<input>', ['label' => 'Label', 'id' => 'id']);
        $blankLabelHtml = FormFields::fieldHtml('<input>', ['label' => '__blank__']);

        expect($html)->toContain('<craft-field class="field"')
            ->and($html)->toContain('orientation="ltr"')
            ->and($html)->toContain('<input slot="input">')
            ->and($labelHtml)->toContain('label="Label"')
            ->and($labelHtml)->toContain('id="id-field"')
            ->and($blankLabelHtml)->not->toContain('label=');
    });

    it('renders markup input', function () {
        $html = FormFields::fieldHtml(new Markup('<input name="title">', 'UTF-8'));

        expect($html)->toContain('<input name="title" slot="input">');
    });

    it('supports fieldsets with grouped label semantics', function () {
        $html = FormFields::fieldHtml('<input>', ['fieldset' => true, 'label' => 'Label']);

        expect($html)->toContain(' fieldset')
            ->and($html)->toContain('label="Label"');
    });

    it('renders instructions, tip, warning, and errors', function () {
        $withInstructions = FormFields::fieldHtml('<input>', [
            'instructions' => '**Test**',
        ]);
        $withTip = FormFields::fieldHtml('<input>', [
            'tip' => '**Test**',
        ]);
        $withWarning = FormFields::fieldHtml('<input>', [
            'warning' => '**Test**',
        ]);
        $withErrors = FormFields::fieldHtml('<input>', [
            'errors' => ['Very bad', 'Very, very bad'],
        ]);

        expect($withInstructions)->toContain('slot="help-text"')
            ->and($withInstructions)->toContain('<p><strong>Test</strong></p>')
            ->and($withTip)->toContain('slot="tip"')
            ->and($withTip)->toContain('<strong>Test</strong>')
            ->and($withWarning)->toContain('slot="warning"')
            ->and($withErrors)->toContain(' has-errors')
            ->and($withErrors)->toContain('slot="feedback"')
            ->and($withErrors)->toContain('class="error-list"')
            ->and($withErrors)->toContain('<li>Very bad</li>');
    });

    it('renders markup warnings', function () {
        $html = FormFields::fieldHtml('<input>', [
            'warning' => new Markup('Config warning', 'UTF-8'),
        ]);

        expect($html)->toContain('slot="warning"')
            ->and($html)->toContain('Config warning');
    });

    it('throws for invalid template paths', function () {
        expect(fn () => FormFields::fieldHtml('template:invalid/template.twig', []))
            ->toThrow(TemplateLoaderException::class);
    });
});

describe('elementSelectFieldHtml', function () {
    it('projects the selector and preserves the empty submission value', function (bool $useCustomElement) {
        $html = FormFields::elementSelectFieldHtml([
            'id' => 'featured-entries',
            'name' => 'featuredEntryIds',
            'elementType' => Entry::class,
            'label' => 'Featured entries',
            'selectionLabel' => 'Add an entry',
            'registerJs' => false,
            'useCustomElement' => $useCustomElement,
        ]);

        $crawler = new Crawler($html);
        $input = $crawler->filter('craft-field > [slot="input"]');

        expect($input->filter('button')->text())->toBe('Add an entry')
            ->and($input->filter('input[type="hidden"][name="featuredEntryIds"][value=""]'))->toHaveCount(1);
    })->with([false, true]);
});

describe('checkboxFieldHtml', function () {
    it('slots the checkbox host, not its always-post hidden input', function () {
        $html = FormFields::checkboxFieldHtml([
            'id' => 'cb',
            'name' => 'enabled',
            'checkboxLabel' => 'Agree',
        ]);

        expect($html)->toContainTag('craft-checkbox', ['slot' => 'input'])
            ->and($html)->toContainTag('input', ['type' => 'hidden', 'name' => 'enabled', 'slot' => false])
            ->and($html)->toContainTag('label', ['slot' => 'label', 'for' => 'cb']);
    });

    it('renders the checkbox label through the Twig macro', function () {
        $html = app(TemplateManager::class)->renderString(
            '{% import "_includes/forms" as forms %}'.
            '{{ forms.checkboxField({id: "cb", name: "enabled", label: "Agree"}) }}',
            [],
            TemplateMode::Cp,
        );

        // The macro moves `label` onto the checkbox, so the field itself gets
        // no label — only `fieldLabel` puts one on the <craft-field>.
        expect($html)->toContainTag('craft-checkbox', ['slot' => 'input'])
            ->and($html)->toContainTag('label', ['slot' => 'label', 'for' => 'cb'])
            ->and($html)->toContain('>Agree</label>')
            ->and($html)->toContainTag('craft-field', ['label' => false]);
    });
});

describe('dateTimeHtml', function () {
    it('renders native inputs and owns the form metadata', function () {
        $html = app(TemplateManager::class)->renderString(
            '{% include "_includes/forms/datetime" %}',
            [
                'id' => 'starts-at',
                'name' => 'startsAt',
                'value' => new DateTimeImmutable('2026-08-05 12:30:00', new DateTimeZone('Europe/Brussels')),
                'timeZone' => false,
                'minuteIncrement' => 15,
            ],
            TemplateMode::Cp,
        );

        expect($html)->toContainTag('craft-input-date-time', ['name' => 'startsAt'])
            ->and($html)->toContainTag('craft-input-date', ['name' => 'startsAt[date]'])
            ->and($html)->toContainTag('input', ['type' => 'date', 'name' => 'startsAt[date]', 'value' => '2026-08-05'])
            ->and($html)->toContainTag('craft-input-time', ['name' => 'startsAt[time]', 'minute-increment' => '15'])
            ->and($html)->toContainTag('input', ['type' => 'time', 'name' => 'startsAt[time]', 'value' => '12:30', 'step' => '900'])
            ->and($html)->toContainTag('input', ['type' => 'hidden', 'name' => 'startsAt[locale]'])
            ->and($html)->toContainTag('input', ['type' => 'hidden', 'name' => 'startsAt[timezone]', 'value' => 'Europe/Brussels']);
    });
});

describe('config deprecations', function () {
    it('logs a deprecation for unsupported legacy config keys', function (string $method, array $config, string $needle) {
        $logged = false;

        $mock = Mockery::mock(Deprecator::class);
        $mock->shouldReceive('log')
            ->once()
            ->withArgs(function (string $key, string $message) use (&$logged, $needle) {
                $logged = true;

                return str_contains($message, $needle);
            });
        app()->scoped(Deprecator::class, fn () => $mock);

        FormFields::$method($config);

        expect($logged)->toBeTrue();
    })->with([
        'lightswitch descriptionId' => ['lightswitchFromConfig', ['descriptionId' => 'custom'], 'descriptionId'],
        'button spinner' => ['buttonFromConfig', ['label' => 'Save', 'spinner' => true], 'spinner'],
    ]);

    it('logs nothing for faithfully mapped configs', function () {
        $mock = Mockery::mock(Deprecator::class);
        $mock->shouldNotReceive('log');
        app()->scoped(Deprecator::class, fn () => $mock);

        FormFields::lightswitchFromConfig(['id' => 'ls', 'on' => true, 'label' => 'Enabled']);
        FormFields::buttonFromConfig(['label' => 'Save', 'type' => 'submit', 'busyMessage' => 'Saving…']);
        FormFields::checkboxFromConfig(['id' => 'cb', 'label' => 'Agree', 'checked' => true]);
        FormFields::buttonGroupFromConfig(['options' => [['label' => 'A', 'value' => 'a']], 'static' => true]);

        expect(true)->toBeTrue();
    });
});

describe('editable tables', function () {
    it('preserves keyed legacy cells, per-cell choices, and submitted metadata', function (bool $twig, bool $static) {
        $config = [
            'id' => 'sites',
            'name' => 'settings[sites]',
            'label' => 'Sites',
            'static' => $static,
            'includeRowId' => true,
            'cols' => [
                'heading' => ['type' => 'heading', 'heading' => 'Site'],
                'uri' => ['type' => 'singleline', 'heading' => 'URI', 'code' => true],
                'choice' => ['type' => 'select', 'heading' => 'Choice', 'options' => ['default' => 'Default']],
                'enabled' => ['type' => 'checkbox', 'heading' => 'Enabled', 'value' => 'yes'],
            ],
            'rows' => ['primary' => [
                'heading' => '<strong>Primary</strong>',
                'uri' => ['value' => 'articles/{slug}', 'hasErrors' => true],
                'choice' => ['value' => 'override', 'options' => ['override' => 'Override']],
                'enabled' => false,
                'rowId' => 'stable-row',
                'hiddenInputs' => ['token' => 'retained'],
            ]],
        ];
        $html = $twig
            ? app(TemplateManager::class)->renderString('{% import "_includes/forms" as forms %}{{ forms.editableTableField(config) }}', ['config' => $config], TemplateMode::Cp)
            : FormFields::editableTableFieldHtml($config);
        $input = new Crawler($html);
        $payload = json_decode($input->filter('craft-table-form')->attr('data-payload'), true, flags: JSON_THROW_ON_ERROR);
        $control = $payload['nodes'][0]['control'];
        $row = $payload['values']['settings']['sites']['primary'];
        $cells = collect($control['forms'][0]['nodes'])->keyBy(fn (array $node): string => end($node['control']['path']));

        expect($row)->toMatchArray([
            'uri' => 'articles/{slug}', 'choice' => 'override', 'enabled' => false,
            'rowId' => 'stable-row', 'token' => 'retained',
        ])
            ->and($control['mode'])->toBe($static ? 'readOnly' : 'editable')
            ->and($control['props']['errors']['primary']['uri'])->toBeTrue()
            ->and($cells['choice']['control']['props']['options'])->toBe([['label' => 'Override', 'value' => 'override']])
            ->and($cells['enabled']['control']['props']['checkedValue'])->toBe('yes')
            ->and($cells['token']['control']['path'])->toBe(['settings', 'sites', 'primary', 'token']);
    })->with([
        'PHP editable' => [false, false],
        'Twig editable' => [true, false],
        'PHP static' => [false, true],
        'Twig static' => [true, true],
    ]);
});

describe('field helper methods', function () {
    it('renders expected markers', function (string $needle, string $method, array $config = []) {
        $html = FormFields::$method($config);

        expect($html)->toContain($needle);
    })->with([
        ['type="checkbox"', 'checkboxFieldHtml'],
        ['color-input', 'colorFieldHtml'],
        ['editable', 'editableTableFieldHtml', ['name' => 'test']],
        ['lightswitch', 'lightswitchFieldHtml'],
        ['<craft-input-password', 'passwordFieldHtml'],
        ['<select', 'selectFieldHtml'],
        ['type="text"', 'textFieldHtml'],
        ['slot="suffix"', 'textFieldHtml', ['unit' => 'Test unit']],
        ['>Test unit</div>', 'textFieldHtml', ['unit' => 'Test unit']],
        ['<textarea', 'textareaFieldHtml'],
    ]);

    it('maps text expander triggers onto text fields', function () {
        $html = FormFields::textFieldHtml([
            'id' => 'path',
            'name' => 'path',
            'textExpanderTriggers' => [[
                'trigger' => '$',
                'boundary' => 'start',
                'options' => [['label' => '$BASE_PATH', 'value' => '$BASE_PATH']],
            ]],
        ]);

        expect($html)->toContainTag('craft-text-expander', ['for' => 'path']);
    });
});

describe('addressFieldsHtml', function () {
    it('renders required markers from the live address ruleset', function () {
        $address = new TestAddressForFormFields(['countryCode' => 'US']);
        $originalScenario = $address->ruleset->getScenario();

        $html = FormFields::addressFieldsHtml($address);

        expect((bool) preg_match('/<craft-field[^>]*data-attribute="addressLine1"[^>]*\brequired\b/', $html))->toBeTrue()
            ->and((bool) preg_match('/<craft-field[^>]*data-attribute="sortingCode"[^>]*\brequired\b/', $html))->toBeFalse()
            ->and($address->ruleset->getScenario())->toBe($originalScenario);
    });
});

describe('selectizeHtml', function () {
    it('keeps the native multiple select, which posts an array', function () {
        $html = FormFields::selectizeHtml([
            'name' => 'values',
            'values' => ['live', 'pending'],
            'options' => [
                ['value' => 'live', 'label' => 'Live'],
                ['value' => 'pending', 'label' => 'Pending'],
                ['value' => 'expired', 'label' => 'Expired'],
            ],
            'multi' => true,
        ]);

        // `values[]` is the name legacy condition rules post under, and it has
        // to be on the control whether or not anything is selected.
        expect($html)->toContain('name="values[]"')
            ->and($html)->toContain('<option value="live" selected>')
            ->and($html)->toContain('<option value="pending" selected>')
            ->and($html)->not->toContain('<option value="expired" selected>')
            ->and($html)->not->toContain('<craft-combobox');
    });

    it('renders a single select as a combobox', function () {
        $html = FormFields::selectizeHtml([
            'name' => 'status',
            'value' => 'live',
            'options' => [['value' => 'live', 'label' => 'Live']],
        ]);

        expect($html)->toContain('<craft-combobox')
            ->and($html)->toContain('name="status"');
    });
});
