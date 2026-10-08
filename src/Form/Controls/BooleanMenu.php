<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Support\Env;

use function CraftCms\Cms\t;

/**
 * A yes/no menu, which can also offer the boolean environment variables.
 *
 * ```php
 * Field::make(t('Status'), BooleanMenu::make('enabled')
 *     ->yesLabel(t('Enabled'))
 *     ->noLabel(t('Disabled'))
 *     ->includeEnvVars());
 * ```
 *
 * Its value is `'1'`, `'0'`, or an environment variable reference such as `$SITE_ENABLED`.
 * Pass a stored value through {@see optionValue()} to get one of those.
 *
 * @since 6.0.0
 */
class BooleanMenu extends Combobox
{
    private ?string $yesLabel = null;

    private ?string $noLabel = null;

    private bool $includeEnvVars = false;

    /**
     * Returns the option a stored value selects: `'1'` or `'0'` for a boolean or a
     * boolean-like string, and an environment variable reference as it is.
     */
    public static function optionValue(bool|int|string|null $value): string
    {
        if (is_string($value) && str_starts_with($value, '$')) {
            return $value;
        }

        return Env::normalizeBooleanValue($value) ? '1' : '0';
    }

    public function yesLabel(?string $yesLabel): static
    {
        $this->yesLabel = $yesLabel;

        return $this;
    }

    public function noLabel(?string $noLabel): static
    {
        $this->noLabel = $noLabel;

        return $this;
    }

    /**
     * Lists the environment variables that hold a boolean value after the two fixed options,
     * each hinting at what it currently resolves to.
     */
    public function includeEnvVars(bool $includeEnvVars = true): static
    {
        $this->includeEnvVars = $includeEnvVars;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        $yesLabel = $this->yesLabel ?? t('Yes');
        $noLabel = $this->noLabel ?? t('No');

        $this
            ->requireOptionMatch()
            ->options([
                [
                    'label' => $yesLabel,
                    'value' => '1',
                    'data' => ['indicator' => ['variant' => 'success']],
                ],
                [
                    'label' => $noLabel,
                    'value' => '0',
                    'data' => ['indicator' => ['variant' => 'empty']],
                ],
                ...($this->includeEnvVars ? self::envOptions($yesLabel, $noLabel) : []),
            ]);

        return parent::props($value);
    }

    /** @return list<array<string, mixed>> */
    private static function envOptions(string $yesLabel, string $noLabel): array
    {
        $groups = SelectOptions::getBooleanEnvOptions();
        $groups[0]['options'] = $groups[0]['options']
            ->map(function (array $option) use ($yesLabel, $noLabel): array {
                $enabled = $option['data']['boolean'] === '1';

                return [
                    ...$option,
                    'data' => [
                        ...$option['data'],
                        'hint' => $enabled ? $yesLabel : $noLabel,
                        'indicator' => [
                            'variant' => $enabled ? 'success' : 'empty',
                        ],
                    ],
                ];
            })
            ->all();

        return $groups;
    }
}
