<?php

declare(strict_types=1);

namespace CraftCms\Cms\Address\Conditions;

use CommerceGuys\Addressing\Subdivision\SubdivisionRepository as BaseSubdivisionRepository;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Facades\Addresses;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Controls\Combobox;
use CraftCms\Cms\Ui\Nodes\Field;
use Illuminate\Database\Query\Builder;
use Override;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class AdministrativeAreaConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public static function isSelectableForCondition(ConditionInterface $condition): bool
    {
        if (! $condition instanceof AddressCondition) {
            return false;
        }

        return true;
    }

    public string $countryCode = 'US';

    public function __construct(object|array $config = [])
    {
        parent::__construct($config);

        // Craft 5.12 and earlier stored GB counties by their names rather than ISO codes
        if ($this->countryCode === 'GB' && $this->getValues() !== []) {
            $codesByName = array_flip((new BaseSubdivisionRepository)->getList(['GB'], 'en')) + [
                'Bristol, City of' => 'BST',
                'Edinburgh, City of' => 'EDH',
                'London, City of' => 'LND',
                'Durham, County' => 'DUR',
                'Vale of Glamorgan, The' => 'VGL',
            ];
            $this->setValues(array_map(static fn (string $value): string => $codesByName[$value] ?? $value, $this->getValues()));
        }
    }

    /** @return array<string, mixed> */
    #[Override]
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'countryCode' => $this->countryCode,
        ]);
    }

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'countryCode' => ['required', 'string'],
        ]);
    }

    public function getLabel(): string
    {
        return t('Administrative Area');
    }

    protected function options(): array
    {
        $administrativeAreas = Addresses::getSubdivisionRepository()->getList([$this->countryCode], app()->getLocale());
        // Allow custom states that are currently in the administrative areas list to remain in the list.
        foreach ($this->getValues() as $val) {
            if (! array_key_exists($val, $administrativeAreas)) {
                $administrativeAreas[$val] = $val;
            }
        }

        return $administrativeAreas;
    }

    public function modifyQuery(Builder $query, ElementQueryInterface $elementQuery): void
    {
        AddressQuery::applyAdministrativeArea($query, $this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Address $element */
        return $this->matchValue($element->administrativeArea);
    }

    /** @return list<Node> */
    #[Override]
    protected function inputNodes(): array
    {
        return [
            Field::make(t('Country'), Choice::make('countryCode')
                ->options($this->uiOptions(Addresses::getCountryList()))
                ->withoutPlaceholder()
                ->value($this->countryCode)
                ->reactive()),
            Field::make($this->getLabel(), Combobox::make('values')
                ->multiple()
                ->options($this->uiOptions($this->options()))
                ->showAllOnEmpty()
                ->value($this->getValues())),
        ];
    }
}
