/**
 * Asking the server which existing elements a draft step's matching would update, going
 * through its source's rows in order, without importing anything.
 */
import {actionClient} from '@craftcms/ui';
import {testMatch as testMatchAction} from '@actions/Import/ImportPlansController';
import type {ImportStep, MappingValues} from './types';

export interface MatchResult {
  /** The 1-based row of the source it was matched from. */
  row: number;
  id: number;
  label: string;
  url: string | null;
}

export interface TestMatchResults {
  matches: MatchResult[];
  /** How many rows were gone through to find them. */
  checked: number;
  /** How many rows the source has. */
  total: number;
}

export async function testMatch(
  step: ImportStep,
  values: MappingValues
): Promise<TestMatchResults> {
  // PHP sends empty settings as `[]`
  const settings = Array.isArray(step.settings) ? {} : (step.settings ?? {});
  const {data} = await actionClient.post<TestMatchResults>(
    testMatchAction().url,
    {step: {...step, settings: {...settings, ...values}}}
  );

  return data;
}
