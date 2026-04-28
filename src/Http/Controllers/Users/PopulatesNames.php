<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use Illuminate\Http\Request;

trait PopulatesNames
{
    protected function populateNameAttributes(Request $request, object $model): void
    {
        $fullName = $request->input('fullName');

        if ($fullName !== null) {
            $model->fullName = $fullName;

            // Unset firstName and lastName so NameTrait::prepareNamesForSave() can set them
            $model->firstName = $model->lastName = null;
        } else {
            // Still check for firstName/lastName in case a front-end form is still posting them
            $firstName = $request->input('firstName');
            $lastName = $request->input('lastName');

            if ($firstName !== null || $lastName !== null) {
                $model->firstName = $firstName ?? $model->firstName;
                $model->lastName = $lastName ?? $model->lastName;

                // Unset fullName so NameTrait::prepareNamesForSave() can set it
                $model->fullName = null;
            }
        }
    }
}
