<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use CraftCms\Cms\Support\Json;
use Illuminate\View\ComponentAttributeBag;

class VueProps extends ComponentAttributeBag
{
    #[\Override]
    public function __toString(): string
    {
        $string = '';

        foreach ($this->attributes as $key => $value) {
            if ($value === false) {
                continue;
            }
            if (is_null($value)) {
                continue;
            }
            if ($value === true) {
                $value = $key === 'x-data' || str_starts_with((string) $key, 'wire:') || str_starts_with((string) $key, ':') ? '' : $key;
            }

            if (is_array($value) || str_starts_with((string) $key, ':')) {
                $string .= sprintf(" %s='%s'", $key, Json::encode($value));
            } else {
                $string .= ' '.$key.'="'.str_replace('"', '\\"', trim((string) $value)).'"';
            }
        }

        return trim($string);
    }
}
