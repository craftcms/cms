<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Contracts;

use Illuminate\Contracts\Support\MessageBag;

interface Validatable
{
    /**
     * Returns the validation rules for attributes.
     *
     * @return array<string, mixed>
     */
    public function getRules(): array;

    /**
     * Returns custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function getMessages(): array;

    // /**
    //  * This method is invoked before validation starts.
    //  * The default implementation returns true, allowing validation to proceed.
    //  * Override this method to perform pre-validation logic or to conditionally skip validation.
    //  *
    //  * @return bool whether the validation should be executed.
    //  */
    // public function beforeValidate(): bool;

    /**
     * Validates the attributes.
     *
     * @param  string|array<string>|null  $attributeNames  the attribute names to validate. If null, all attributes will be validated.
     * @param  bool  $clearErrors  whether to clear existing errors before validation.
     * @return bool whether the validation succeeded without any error.
     */
    public function validate(string|array|null $attributeNames = null, bool $clearErrors = true): bool;

    // /**
    //  * This method is invoked after validation ends.
    //  * Override this method to perform additional validation or add custom errors to the validator.
    //  *
    //  * @param Validator $validator the validator instance that performed the validation.
    //  */
    // public function afterValidate(Validator $validator): void;

    /**
     * Returns the first error message for each attribute that has errors.
     *
     * @return array<string, string>
     */
    public function getFirstErrors(): array;

    /**
     * Returns the validation error messages.
     */
    public function errors(): MessageBag;

    /**
     * Sets attribute values.
     *
     * @param  array<string, mixed>  $values  attribute values to set (attribute name => value).
     * @param  bool  $safeOnly  whether to only set safe attributes (currently unused).
     */
    public function setAttributes(array $values, bool $safeOnly = true): void;

    /**
     * Returns all attribute values.
     * By default, this returns all public non-static properties of the class.
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array;

    /**
     * Returns the list of attribute names.
     * By default, this returns all public non-static property names of the class.
     *
     * @return string[] list of attribute names.
     */
    public function attributes(): array;

    /**
     * Returns human-readable labels for attributes.
     * These labels are used in validation error messages. For example, given an attribute
     * `firstName`, a label `First Name` can be declared for display to end users.
     * The default implementation returns an empty array.
     *
     * @return array<string, string>
     */
    public function attributeLabels(): array;
}
