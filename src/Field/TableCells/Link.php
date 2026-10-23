<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\TableCells;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Concerns\ProvidesLinkField;
use CraftCms\Cms\Field\Contracts\ElementAwareTableCellInterface;
use CraftCms\Cms\Field\Data\LinkData;
use CraftCms\Cms\Field\Link as LinkField;
use CraftCms\Cms\Field\LinkResolver;
use CraftCms\Cms\Gql\Types\Generators\LinkDataType;
use CraftCms\Cms\Gql\Types\Input\LinkData as LinkDataInput;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Controls\Number;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use GraphQL\Type\Definition\Type;
use InvalidArgumentException;

use function CraftCms\Cms\t;

/**
 * Link cells store link values. Values that name an unavailable link type are kept as-is; they can be removed but not edited.
 *
 * @since 6.0.0
 */
class Link extends TableCell implements ElementAwareTableCellInterface
{
    use ProvidesLinkField;

    /** @var list<string> */
    public array $types = ['entry', 'url'];

    /** @var array<string, array<string, mixed>> */
    public array $typeSettings = [];

    public bool $showLabelField = false;

    /** @var list<'urlSuffix'|'target'|'title'|'class'|'id'|'rel'|'ariaLabel'|'download'> */
    public array $advancedFields = [];

    public int $maxLength = 255;

    private LinkResolver $resolver;

    /** @param array<string, mixed>|object $config */
    public function __construct(array|object $config = [])
    {
        parent::__construct($this->prepareLinkSettingsConfig((array) $config));
    }

    public static function displayName(): string
    {
        return t('Link');
    }

    public function uiControl(TableCellContext $context): Control
    {
        return $this->resolver()->control($context->path, $context->value)
            ->compact()
            ->registeredTypes(array_keys(LinkField::types()));
    }

    public function normalizeValue(mixed $value, bool $fromRequest = false): mixed
    {
        try {
            return $this->resolver()->normalize($value);
        } catch (InvalidArgumentException) {
            return $value;
        }
    }

    /** @return array<string, mixed>|null */
    public function serializeValue(mixed $value, bool $forDb = false): ?array
    {
        $value = $this->normalizeValue($value);

        return $value instanceof LinkData ? $value->serialize() : $value;
    }

    public function getValueRules(): array
    {
        return [
            function (string $attribute, mixed $value, Closure $fail): void {
                $link = $this->normalizeValue($value);
                if (! $link instanceof LinkData) {
                    return;
                }

                $error = $this->resolver()->validate($link, ':attribute');
                if ($error !== null) {
                    $fail($error);
                }
            },
        ];
    }

    public function searchKeywords(mixed $value): string
    {
        return $value instanceof LinkData ? (string) $value : '';
    }

    public function gqlType(): Type
    {
        return LinkDataType::generateType($this);
    }

    public function gqlInputType(): Type
    {
        return LinkDataInput::getType();
    }

    public function localizeValues(array $values, ElementInterface $element): array
    {
        return $this->resolver()->localizeAll($values, $element);
    }

    public function referenceTargetIds(mixed $value): array
    {
        return $value instanceof LinkData ? $this->resolver()->referenceTargetIds($value) : [];
    }

    public function replaceReferences(mixed $value, array $oldTargetIds, int $newTargetId): mixed
    {
        if (! $value instanceof LinkData) {
            return null;
        }

        $newValue = $this->resolver()->replaceReference($value, $oldTargetIds, $newTargetId);

        return $newValue === null ? null : $this->normalizeValue($newValue);
    }

    public function getRules(): array
    {
        return [
            ...parent::getRules(),
            'maxLength' => ['required', 'integer', 'min:10'],
            ...$this->linkSettingsRules(),
        ];
    }

    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            ...$this->linkSettingsAttributeLabels(),
            'maxLength' => t('Max Length'),
        ];
    }

    public function settingsUi(UiContext $context = new UiContext): ?Ui
    {
        return Ui::make($this->linkSettingsNodes())->add(
            Field::make(t('Max Length'))
                ->instructions(t('The maximum length (in bytes) the field can hold.'))
                ->control(Number::make('maxLength')->min(10)->value($this->maxLength)),
        );
    }

    private function resolver(): LinkResolver
    {
        return $this->resolver ??= new LinkResolver(
            types: $this->types,
            typeSettings: $this->typeSettings,
            showLabelField: $this->showLabelField,
            advancedFields: $this->advancedFields,
            maxLength: $this->maxLength,
        );
    }
}
