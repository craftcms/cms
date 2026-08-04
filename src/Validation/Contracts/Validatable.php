<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Contracts;

use CraftCms\RulesetValidation\Contracts\ValidatesWithRuleset;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Validation\Validator;

interface Validatable extends ValidatesWithRuleset
{
    /**
     * Returns the validation rules or ruleset for attributes.
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

    /**
     * Returns human-readable labels for attributes.
     * These labels are used in validation error messages. For example, given an attribute
     * `firstName`, a label `First Name` can be declared for display to end users.
     * The default implementation returns an empty array.
     *
     * @return array<string, string>
     */
    public function attributeLabels(): array;

    /**
     * Returns the text label for the specified attribute.
     */
    public function getAttributeLabel(string $attribute): string;

    /**
     * Generates a user-friendly attribute label based on the give attribute name.
     * This is done by replacing underscores, dashes and dots with blanks and
     * changing the first letter of each word to uppercase.
     *
     * For example, 'department_name' or 'DepartmentName' will generate 'Department Name'.
     */
    public function generateAttributeLabel(string $name): string;

    /**
     * Sets attribute values.
     *
     * @param  array<string, mixed>  $values  attribute values to set (attribute name => value).
     */
    public function setAttributes(array $values): void;

    /**
     * This method is invoked before validation starts.
     * Override this method to perform pre-validation logic.
     */
    public function prepareForValidation(): void;

    /**
     * Validates the attributes.
     *
     * @param  string|array<string>|null  $attributeNames  the attribute names to validate. If null, all attributes will be validated.
     * @param  bool  $clearErrors  whether to clear existing errors before validation.
     * @return bool whether the validation succeeded without any error.
     */
    public function validate(string|array|null $attributeNames = null, bool $clearErrors = true): bool;

    /**
     * This method is invoked after validation ends.
     * Override this method to perform additional validation or add custom errors to the validator.
     *
     * @param  Validator  $validator  the validator instance that performed the validation.
     */
    public function afterValidate(Validator $validator): void;

    /**
     * Handle a passed validation attempt.
     */
    public function passedValidation(): void;

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
     * Clears validation errors for the specified attribute or all attributes.
     */
    public function clearErrors(?string $attribute = null): void;
}
