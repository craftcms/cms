<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Contracts;

use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Validation\Validator;

interface Validatable
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
     * This method is invoked before validation starts.
     * The default implementation returns true, allowing validation to proceed.
     * Override this method to perform pre-validation logic or to conditionally skip validation.
     *
     * @return bool whether the validation should be executed.
     */
    public function beforeValidate(): bool;

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
     * TODO: Remove optionality of validator after components no longer rely on craft/base/Model
     *
     * @param  ?Validator  $validator  the validator instance that performed the validation.
     */
    public function afterValidate(?Validator $validator = null): void;

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
     * Adds errors from another model, with a given attribute name prefix.
     */
    public function addModelErrors(Validatable $model, string $attrPrefix = ''): void;

    /**
     * Sets attribute values.
     *
     * @param  array<string, mixed>  $values  attribute values to set (attribute name => value).
     */
    public function setAttributes(array $values): void;

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
     * Returns the attribute names that are safe to be massively assigned in the current scenario.
     *
     * @return string[] safe attribute names
     */
    public function safeAttributes(): array;

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
     * Sets the current validation scenario.
     *
     * Scenarios allow components to use different validation rules based on context.
     * For example, a 'create' scenario might require certain fields, while an 'update'
     * scenario might have different requirements.
     *
     * @param  string  $scenario  The scenario name to set
     */
    public function setScenario(string $scenario): void;

    /**
     * Returns the current validation scenario.
     *
     * @return string The active scenario name
     */
    public function getScenario(): string;

    /**
     * Returns a mapping of scenario names to their active attributes.
     *
     * Each scenario defines which attributes should be validated. The returned array
     * maps scenario names (keys) to either:
     * - An array of attribute names that should be validated in that scenario
     * - null to indicate all attributes should be validated
     *
     * Example:
     * ```php
     * [
     *     'create' => ['title', 'slug', 'body'],
     *     'update' => ['title', 'body'],
     *     'default' => null, // All attributes
     * ]
     * ```
     *
     * @return array<string, array<string>|null>
     */
    public function scenarios(): array;

    /**
     * Checks if the current scenario matches any of the provided scenarios.
     *
     * @param  string  ...$scenarios  One or more scenario names to check against
     * @return bool True if the current scenario matches any provided scenario
     */
    public function inScenarios(string ...$scenarios): bool;
}
