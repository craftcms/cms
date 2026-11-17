import {actionClient} from '@craftcms/ui/utilities/api/actionClient';

interface RenderedComponents {
  components: Record<string, Record<string, string[]>>;
}

/**
 * Re-renders every legacy `div.chip` for a component (an entry type, a
 * field…) after it's been edited, keeping each chip's actions, indicators and
 * inputs.
 */
export async function refreshComponentInstances(
  type: string,
  id: string | number
): Promise<void> {
  const chips = [
    ...document.querySelectorAll<HTMLElement>(
      `div.chip[data-type="${CSS.escape(type)}"][data-id="${CSS.escape(String(id))}"][data-settings]`
    ),
  ];

  if (!chips.length) {
    return;
  }

  const {data} = await actionClient.post<RenderedComponents>(
    'app/render-components',
    {
      components: [
        {
          type,
          id,
          instances: chips.map((chip) =>
            JSON.parse(chip.dataset.settings ?? 'null')
          ),
        },
      ],
    }
  );

  const rendered = data.components[type]?.[String(id)] ?? [];
  const template = document.createElement('template');

  rendered.forEach((html, i) => {
    const chip = chips[i];
    template.innerHTML = html.trim();
    const replacement = template.content.firstElementChild;

    if (!chip || !replacement) {
      return;
    }

    for (const attribute of replacement.attributes) {
      if (attribute.name === 'class') {
        chip.classList.add(...attribute.value.split(/\s+/).filter(Boolean));
      } else {
        chip.setAttribute(attribute.name, attribute.value);
      }
    }

    const actions = chip.querySelector('.chip-actions');
    const indicators = chip.querySelector('.chip-label .indicators');
    actions?.remove();
    indicators?.remove();
    const inputs = [...chip.querySelectorAll('input, button')];
    inputs.forEach((input) => input.remove());

    chip.replaceChildren(...replacement.childNodes);

    if (actions) {
      chip.querySelector('.chip-actions')?.replaceWith(actions);
    }
    if (indicators) {
      chip.querySelector('.chip-label')?.append(indicators);
    }
    chip.append(...inputs);
  });
}
