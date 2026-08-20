<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Address\Validation\AddressRules;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Deprecator\Deprecator;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Twig\Exceptions\TemplateLoaderException;
use CraftCms\Cms\View\TemplateManager;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\RulesetValidation\Attributes\Ruleset;
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

    it('throws for an invalid site id in multi-site mode', function () {
        if (! Sites::isMultiSite()) {
            expect(true)->toBeTrue();

            return;
        }

        expect(fn () => FormFields::fieldHtml('<input>', ['siteId' => -1]))
            ->toThrow(InvalidArgumentException::class);
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
