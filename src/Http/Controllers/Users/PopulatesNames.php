<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use Illuminate\Http\Request;

trait PopulatesNames
{
    protected function populateNameAttributes(Request $request, object $model): void
    {
        if ($request->has('fullName')) {
            $model->fullName = $request->input('fullName');

            // Unset firstName and lastName so NameTrait::prepareNamesForSave() can set them
            $model->firstName = $model->lastName = null;
        } elseif ($request->hasAny(['firstName', 'lastName'])) {
            // Still check for firstName/lastName in case a front-end form is still posting them
            if ($request->has('firstName')) {
                $model->firstName = $request->input('firstName');
            }
            if ($request->has('lastName')) {
                $model->lastName = $request->input('lastName');
            }

            // Unset fullName so NameTrait::prepareNamesForSave() can set it
            $model->fullName = null;
        }
    }
}
