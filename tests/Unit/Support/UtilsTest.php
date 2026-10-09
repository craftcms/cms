<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Utils;

it('reads backed and computed properties without reading write-only hooks', function () {
    $object = new class
    {
        public string $name = 'Alpha';

        public string $alias {
            set {
                $this->rename($value);
            }
        }

        public function rename(string $value): void
        {
            $this->name = $value;
        }

        public string $label {
            get => strtoupper($this->name);
        }
    };
    $object->alias = 'Beta';

    expect(Utils::getPublicProperties($object))->toBe(['name' => 'Beta', 'label' => 'BETA']);
});
