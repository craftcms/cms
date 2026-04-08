<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use craft\base\ElementInterface;
use CraftCms\Cms\Address\Elements\Address as AddressElement;
use CraftCms\Cms\Address\Models\Address;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Override;

class AddressFactory extends Factory
{
    #[Override]
    protected $model = Address::class;

    #[Override]
    public function definition(): array
    {
        return [
            'id' => Element::factory()->set('type', AddressElement::class),
            'countryCode' => fake()->countryCode(),
            'dateCreated' => $created = $this->faker->dateTime(),
            'dateUpdated' => $created,
        ];
    }

    public function createElement(array $attributes = []): AddressElement
    {
        $model = $this->create($attributes);

        return AddressElement::find()->id($model->id)->one();
    }

    public function withOwnedElement(
        ElementInterface $owner,
        int $sortOrder,
        ?int $primaryOwnerId = null,
    ): self {
        return $this
            ->state(fn () => ['primaryOwnerId' => $primaryOwnerId ?? $owner->id])
            ->afterCreating(function (Address $address) use ($owner, $sortOrder) {
                DB::table(Table::ELEMENTS_OWNERS)->insert([
                    'elementId' => $address->id,
                    'ownerId' => $owner->id,
                    'sortOrder' => $sortOrder,
                ]);
            });
    }
}
