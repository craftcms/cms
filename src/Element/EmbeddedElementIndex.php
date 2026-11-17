<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Enums\ElementIndexViewMode;
use CraftCms\Cms\Http\ViewModels\EmbeddedIndexViewModel;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Ui\Controls\ElementIndex;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

use function CraftCms\Cms\currentUser;

/**
 * Describes a read-only element index that is embedded in a screen and lists the elements matching fixed criteria,
 * like a customer’s orders on their user screen.
 *
 * The definition is encrypted into a token that the embedded index sends back with each request,
 * so the criteria it was given can’t be changed from the browser.
 *
 * ```php
 * $index = EmbeddedElementIndex::make(Order::class)
 *     ->criteria(['customerId' => $user->id, 'isCompleted' => true])
 *     ->storageKey('orders:completed')
 *     ->defaultTableColumns(['orderStatus', 'dateOrdered']);
 *
 * Field::make('Orders', $index->uiControl(['orders']));
 * ```
 *
 * @since 6.0.0
 */
class EmbeddedElementIndex
{
    /** @var array<string, mixed> */
    private array $criteria = [];

    /** @var array<string, mixed> */
    private array $config = [];

    /** @param  class-string<ElementInterface>  $elementType */
    final public function __construct(private readonly string $elementType) {}

    /** @param  class-string<ElementInterface>  $elementType */
    public static function make(string $elementType): static
    {
        return new static($elementType);
    }

    /** @return class-string<ElementInterface> */
    public function elementType(): string
    {
        return $this->elementType;
    }

    /**
     * Sets the element query criteria that every request is limited to.
     *
     * @param  array<string, mixed>  $criteria
     */
    public function criteria(array $criteria): static
    {
        $this->criteria = $criteria;

        return $this;
    }

    /** Sets the key the index’s view state is stored under in the browser. */
    public function storageKey(string $key): static
    {
        $this->config['storageKey'] = $key;

        return $this;
    }

    /** @param  list<string>  $columns */
    public function defaultTableColumns(array $columns): static
    {
        $this->config['defaultTableColumns'] = $columns;

        return $this;
    }

    public function defaultSort(string $attribute, string $direction = 'asc'): static
    {
        $this->config['defaultSort'] = [$attribute, $direction];

        return $this;
    }

    /** @param  list<ElementIndexViewMode|string>  $modes */
    public function allowedViewModes(array $modes): static
    {
        $this->config['allowedViewModes'] = array_map(
            fn (ElementIndexViewMode|string $mode): string => $mode instanceof ElementIndexViewMode ? $mode->value : $mode,
            $modes,
        );

        return $this;
    }

    public function defaultViewMode(ElementIndexViewMode|string $mode): static
    {
        $this->config['defaultViewMode'] = $mode instanceof ElementIndexViewMode ? $mode->value : $mode;

        return $this;
    }

    public function pageSize(int $pageSize): static
    {
        $this->config['pageSize'] = $pageSize;

        return $this;
    }

    public function showHeaderColumn(bool $show = true): static
    {
        $this->config['showHeaderColumn'] = $show;

        return $this;
    }

    /** @return array<string, mixed> */
    public function getCriteria(): array
    {
        return $this->criteria;
    }

    /** @return array<string, mixed> */
    public function getConfig(): array
    {
        return $this->config;
    }

    public function sourceSettings(): ElementIndexSourceSettings
    {
        return ElementIndexSourceSettings::forCriteria($this->elementType, $this->criteria, $this->config);
    }

    /**
     * Returns the token that identifies this definition in index requests.
     */
    public function token(): string
    {
        return Crypt::encryptString(Json::encode([
            'elementType' => $this->elementType,
            'criteria' => $this->criteria,
            'config' => $this->config,
            'userId' => currentUser()?->getCraftUserId(),
        ]));
    }

    /**
     * Restores a definition from a token, or returns `null` if it isn’t valid for the current user.
     */
    public static function fromToken(string $token): ?static
    {
        try {
            $data = Json::decode(Crypt::decryptString($token));
        } catch (DecryptException) {
            return null;
        }

        if (
            ! is_array($data) ||
            ! is_string($data['elementType'] ?? null) ||
            ! is_a($data['elementType'], ElementInterface::class, true) ||
            ($data['userId'] ?? null) !== currentUser()?->getCraftUserId()
        ) {
            return null;
        }

        return new static($data['elementType'])
            ->criteria(is_array($data['criteria'] ?? null) ? $data['criteria'] : [])
            ->withConfig(is_array($data['config'] ?? null) ? $data['config'] : []);
    }

    /** @param  string|list<string>  $path */
    public function uiControl(string|array $path): ElementIndex
    {
        return ElementIndex::make($path)
            ->token($this->token())
            ->elementType($this->elementType)
            ->storageKey($this->config['storageKey'] ?? null)
            ->initial(EmbeddedIndexViewModel::forDefinition($this)->payload());
    }

    /** @param  array<string, mixed>  $config */
    private function withConfig(array $config): static
    {
        $this->config = $config;

        return $this;
    }
}
