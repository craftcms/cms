<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use BackedEnum;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Enums\ElementIndexViewMode;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Ui\Nodes\ElementIndex;
use DateTimeInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;

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
 * $index->uiNode('orders');
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
     * Elements are stored as their IDs, dates as ISO 8601 strings and backed enums as their values,
     * so the criteria can be carried in the index's token.
     *
     * @param  array<string, mixed>  $criteria
     *
     * @throws InvalidArgumentException if a criteria value can't be stored in the token
     */
    public function criteria(array $criteria): static
    {
        $this->criteria = $this->normalizeCriteria($criteria);

        return $this;
    }

    /**
     * Sets the field layouts whose custom fields can be shown as columns, sorted by, and filtered on.
     *
     * @param  list<FieldLayout>  $fieldLayouts
     *
     * @throws InvalidArgumentException if a field layout hasn’t been saved
     */
    public function fieldLayouts(array $fieldLayouts): static
    {
        $this->config['fieldLayouts'] = array_map(function (FieldLayout $layout): string {
            if ($layout->id === null) {
                throw new InvalidArgumentException('Embedded element indexes only support saved field layouts.');
            }

            return $layout->uid;
        }, $fieldLayouts);

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
        $config = $this->config;

        if (isset($config['fieldLayouts'])) {
            $config['fieldLayouts'] = array_values(array_filter(array_map(
                fn (mixed $uid): ?FieldLayout => is_string($uid) ? Fields::getLayoutByUid($uid) : null,
                $config['fieldLayouts'],
            )));
        }

        return ElementIndexSourceSettings::forCriteria($this->elementType, $this->criteria, $config);
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

    public function uiNode(string $uid): ElementIndex
    {
        return ElementIndex::make($uid, $this);
    }

    /**
     * Converts criteria values to ones that survive JSON encoding.
     *
     * @param  array<array-key, mixed>  $criteria
     * @return array<array-key, mixed>
     */
    private function normalizeCriteria(array $criteria, string $path = ''): array
    {
        foreach ($criteria as $key => $value) {
            $criteria[$key] = match (true) {
                $value === null, is_scalar($value) => $value,
                is_array($value) => $this->normalizeCriteria($value, "$path$key."),
                $value instanceof ElementInterface => $value->id,
                $value instanceof DateTimeInterface => $value->format(DateTimeInterface::ATOM),
                $value instanceof BackedEnum => $value->value,
                default => throw new InvalidArgumentException(sprintf(
                    'The `%s%s` criteria value can’t be used in an embedded element index.',
                    $path,
                    $key,
                )),
            };
        }

        return $criteria;
    }

    /** @param  array<string, mixed>  $config */
    private function withConfig(array $config): static
    {
        $this->config = $config;

        return $this;
    }
}
