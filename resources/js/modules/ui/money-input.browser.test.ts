import {beforeEach, describe, expect, it} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser/context';
import '@craftcms/ui/components/input-money/input-money';

async function createInputMoney(modelValue: string) {
  const element = document.createElement('craft-input-money');
  element.label = 'Amount';
  element.modelValue = modelValue;
  document.body.append(element);
  await element.updateComplete;

  return {
    element,
    input: element.querySelector<HTMLInputElement>('input[slot="input"]')!,
  };
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-input-money typing', () => {
  it('types a negative value over a selected negative value', async () => {
    const {input} = await createInputMoney('-0.05');

    input.focus();
    input.select();
    await userEvent.keyboard('-0.03');

    expect(input.value).toBe('-0.03');
  });

  it('types a negative value over a selected positive value', async () => {
    const {input} = await createInputMoney('0.05');

    input.focus();
    input.select();
    await userEvent.keyboard('-0.03');

    expect(input.value).toBe('-0.03');
  });

  it('still toggles the sign when only the caret is in a negative value', async () => {
    const {input} = await createInputMoney('-0.05');

    input.focus();
    input.setSelectionRange(0, 0);
    await userEvent.keyboard('-');

    expect(input.value).toBe('0.05');
  });
});
