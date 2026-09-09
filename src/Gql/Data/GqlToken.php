<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Data;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Gql\Gql as GqlService;
use CraftCms\Cms\Support\Arr;
use DateTimeInterface;
use Illuminate\Validation\Rule;
use Override;
use Stringable;

class GqlToken extends Component implements Stringable
{
    public const string PUBLIC_TOKEN = '__PUBLIC__';

    public ?int $id = null;

    public ?string $name = null;

    public ?int $schemaId = null;

    public string $accessToken = '';

    public bool $enabled = true;

    public ?DateTimeInterface $expiryDate = null;

    public ?DateTimeInterface $lastUsed = null;

    public ?DateTimeInterface $dateCreated = null;

    public ?string $uid = null;

    public bool $isTemporary = false;

    public ?GqlSchema $schema {
        get => $this->getSchema();
        set {
            if (! $value) {
                return;
            }

            $this->setSchema($value);
        }
    }

    public bool $isValid {
        get => $this->getIsValid();
    }

    public bool $isExpired {
        get => $this->getIsExpired();
    }

    public bool $isPublic {
        get => $this->getIsPublic();
    }

    /** @var list<string>|null */
    public ?array $scope {
        get => $this->getScope();
    }

    /** @var list<string>|null */
    private ?array $_scope = null;

    private ?GqlSchema $_schema = null;

    public function __construct(array|object $config = [])
    {
        if (is_object($config)) {
            $config = (array) $config;
        }

        unset($config['isValid'], $config['isExpired'], $config['isPublic'], $config['scope']);

        if ($schema = Arr::pull($config, 'schema')) {
            $this->setSchema($schema);
            unset($config['schemaId']);
        }

        parent::__construct($config);
    }

    #[Override]
    public function getRules(): array
    {
        return [
            'id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255', Rule::unique(Table::GQLTOKENS, 'name')->ignore($this->id)],
            'schemaId' => ['nullable', 'integer'],
            'accessToken' => ['required', 'string', 'max:255', Rule::unique(Table::GQLTOKENS, 'accessToken')->ignore($this->id)],
            'enabled' => ['boolean'],
            'expiryDate' => ['nullable', 'date'],
            'lastUsed' => ['nullable', 'date'],
            'dateCreated' => ['nullable', 'date'],
            'uid' => ['nullable', 'uuid'],
            'isTemporary' => ['boolean'],
        ];
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function getIsValid(): bool
    {
        return $this->enabled && ! $this->getIsExpired() && $this->getSchema() !== null;
    }

    public function getIsExpired(): bool
    {
        return $this->expiryDate !== null && $this->expiryDate->getTimestamp() <= now()->getTimestamp();
    }

    public function getIsPublic(): bool
    {
        return $this->accessToken === self::PUBLIC_TOKEN;
    }

    public function getSchema(): ?GqlSchema
    {
        if (! isset($this->_schema) && ! empty($this->schemaId)) {
            $this->_schema = app(GqlService::class)->getSchemaById($this->schemaId);
        }

        return $this->_schema;
    }

    public function setSchema(GqlSchema $schema): void
    {
        $this->_schema = $schema;
        $this->schemaId = $schema->id;
        $this->_scope = null;
    }

    /** @return list<string>|null */
    public function getScope(): ?array
    {
        $this->_scope ??= $this->getSchema()?->scope;

        return $this->_scope;
    }
}
