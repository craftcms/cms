import {valueAt} from './binding';
import type {FormValues} from './types';

type VisibilityCondition =
  CraftCms.Cms.Cp.FormDefinitions.Data.VisibilityConditionData;
type ComparisonCondition = Extract<VisibilityCondition, {name: string}>;
type VisibilityScalar = boolean | number | string | null;

export function evaluateVisibilityCondition(
  condition: VisibilityCondition,
  values: FormValues,
  bindingScope: string
): boolean {
  if ('all' in condition) {
    return condition.all.every((child) =>
      evaluateVisibilityCondition(child, values, bindingScope)
    );
  }

  if ('any' in condition) {
    return condition.any.some((child) =>
      evaluateVisibilityCondition(child, values, bindingScope)
    );
  }

  return evaluateComparison(
    condition,
    valueAt(
      values,
      bindingScope ? `${bindingScope}.${condition.name}` : condition.name
    )
  );
}

function evaluateComparison(
  condition: ComparisonCondition,
  actual: unknown
): boolean {
  switch (condition.operator) {
    case 'equals':
      return (
        comparable(actual, condition.value) && equal(actual, condition.value)
      );
    case 'notEquals':
      return (
        comparable(actual, condition.value) && !equal(actual, condition.value)
      );
    case 'lessThan':
      return (
        numbers(actual, condition.value) && actual < (condition.value as number)
      );
    case 'lessThanOrEqual':
      return (
        numbers(actual, condition.value) &&
        actual <= (condition.value as number)
      );
    case 'greaterThan':
      return (
        numbers(actual, condition.value) && actual > (condition.value as number)
      );
    case 'greaterThanOrEqual':
      return (
        numbers(actual, condition.value) &&
        actual >= (condition.value as number)
      );
    case 'beginsWith':
      return (
        strings(actual, condition.value) &&
        normalize(actual).startsWith(normalize(condition.value as string))
      );
    case 'endsWith':
      return (
        strings(actual, condition.value) &&
        normalize(actual).endsWith(normalize(condition.value as string))
      );
    case 'contains':
      return contains(actual, condition.value);
    case 'in':
      return membership(actual, condition.value) === true;
    case 'notIn': {
      const result = membership(actual, condition.value);

      return result === null ? false : !result;
    }
    case 'empty':
      return (
        compatibleValue(actual) &&
        (actual === null ||
          actual === '' ||
          (Array.isArray(actual) && actual.length === 0))
      );
    case 'notEmpty':
      return (
        compatibleValue(actual) &&
        actual !== null &&
        actual !== '' &&
        (!Array.isArray(actual) || actual.length > 0)
      );
  }
}

function contains(actual: unknown, expected: unknown): boolean {
  if (typeof actual === 'string' && typeof expected === 'string') {
    return normalize(actual).includes(normalize(expected));
  }

  return scalarList(actual) && scalar(expected)
    ? actual.some((value) => equal(value, expected))
    : false;
}

function membership(actual: unknown, expected: unknown): boolean | null {
  if (!scalarList(expected) || (!scalar(actual) && !scalarList(actual))) {
    return null;
  }

  const values = Array.isArray(actual) ? actual : [actual];

  return values.some((value) =>
    expected.some((candidate) => equal(value, candidate))
  );
}

function comparable(actual: unknown, expected: unknown): boolean {
  if (scalar(actual) && scalar(expected)) {
    return actual === null || expected === null
      ? actual === null && expected === null
      : typeof actual === typeof expected;
  }

  return scalarList(actual) && scalarList(expected);
}

function equal(actual: unknown, expected: unknown): boolean {
  if (Array.isArray(actual) && Array.isArray(expected)) {
    return (
      actual.length === expected.length &&
      actual.every((value, index) => equal(value, expected[index]))
    );
  }

  return actual === expected;
}

function numbers(actual: unknown, expected: unknown): actual is number {
  return (
    typeof actual === 'number' &&
    Number.isFinite(actual) &&
    typeof expected === 'number' &&
    Number.isFinite(expected)
  );
}

function strings(actual: unknown, expected: unknown): actual is string {
  return typeof actual === 'string' && typeof expected === 'string';
}

function normalize(value: string): string {
  return value.toLowerCase();
}

function compatibleValue(value: unknown): boolean {
  return scalar(value) || scalarList(value);
}

function scalar(value: unknown): value is VisibilityScalar {
  return (
    value === null ||
    typeof value === 'boolean' ||
    typeof value === 'string' ||
    (typeof value === 'number' && Number.isFinite(value))
  );
}

function scalarList(value: unknown): value is VisibilityScalar[] {
  return Array.isArray(value) && value.every(scalar);
}
