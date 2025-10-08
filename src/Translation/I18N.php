<?php

namespace CraftCms\Cms\Translation;

use CraftCms\Cms\Config\GeneralConfig;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use ResourceBundle;
use Stringable;
use Yiisoft\Translator\Translator;

#[Singleton]
final readonly class I18N
{
    public function __construct(
        private GeneralConfig $generalConfig,
        private Translator $translator,
    ) {
    }

    /** @return Collection<string> */
    public function getAllLocaleIds(): Collection
    {
        return once(function () {
            return collect(ResourceBundle::getLocales(''))
                ->map(function (string $locale) {
                    return str_replace('_', '-', $locale);
                })
                ->when(
                    !empty($this->generalConfig->localeAliases),
                    fn(Collection $localeIds) => $localeIds
                        ->merge(array_keys($this->generalConfig->localeAliases))
                        ->unique()
                        ->sort(),
                );
        });
    }

    public function translate(string|Stringable $message, array $parameters = [], ?string $category = null, ?string $locale = null): string
    {
        $translation = $this->translator->translate($message, $parameters, $category, $locale);

        if ($this->generalConfig->translationDebugOutput) {
            $char = match ($category) {
                'site' => '$',
                'app' => '@',
                default => '%',
            };

            $translation = $char . $translation . $char;
        }

        return $translation;
    }
}
