import {afterEach, expect, it, vi} from 'vite-plus/test';
import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {refreshComponentInstances} from './component-instances';

afterEach(() => {
  vi.restoreAllMocks();
  document.body.innerHTML = '';
});

it('re-renders a component’s chips, keeping their actions, indicators and inputs', async () => {
  document.body.innerHTML = `
    <div class="chip" data-type="EntryType" data-id="3" data-settings='{"ui":"chip"}'>
      <div class="chip-label">Old name<span class="indicators">!</span></div>
      <div class="chip-actions"><button id="menu">…</button></div>
      <input type="hidden" name="entryTypes[]" value="3">
    </div>`;
  const post = vi.spyOn(actionClient, 'post').mockResolvedValue({
    data: {
      components: {
        EntryType: {
          3: [
            '<div class="chip small" data-id="3"><div class="chip-label">New name</div><div class="chip-actions"></div></div>',
          ],
        },
      },
    },
  } as never);

  await refreshComponentInstances('EntryType', 3);

  expect(post).toHaveBeenCalledWith('app/render-components', {
    components: [{type: 'EntryType', id: 3, instances: [{ui: 'chip'}]}],
  });
  const chip = document.querySelector<HTMLElement>('.chip')!;
  expect(chip.classList).toContain('small');
  expect(chip.querySelector('.chip-label')!.textContent).toBe('New name!');
  expect(chip.querySelector('.chip-actions #menu')).not.toBeNull();
  expect(chip.querySelector('input[name="entryTypes[]"]')).not.toBeNull();
});
