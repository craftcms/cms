<?php

declare(strict_types=1);

namespace Workbench\App\Elements;

use CraftCms\Cms\Element\Eloquent\EloquentElement;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\TextField;
use Illuminate\Validation\Rule;
use Workbench\App\Models\Customer as CustomerModel;

/**
 * @extends EloquentElement<CustomerModel>
 */
class Customer extends EloquentElement
{
    public static function modelClass(): string
    {
        return CustomerModel::class;
    }

    public static function cpNavIcon(): string
    {
        return 'address-card';
    }

    public static function defineFieldLayout(FieldLayout $layout): FieldLayout
    {
        return $layout
            ->tab('Contact', fn (FieldLayoutTab $tab) => $tab->add(
                new TextField(['attribute' => 'name', 'label' => 'Name']),
                new TextField(['attribute' => 'email', 'label' => 'Email']),
            ))
            ->tab('Company', fn (FieldLayoutTab $tab) => $tab
                ->add(new TextField(['attribute' => 'company', 'label' => 'Company']))
                ->tip('Shown on invoices.'))
            ->cardView(['email', 'company']);
    }

    public function modelRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('customers', 'email')->ignore($this->getModel()->getKey())],
            'company' => ['nullable', 'string', 'max:255'],
        ];
    }
}
