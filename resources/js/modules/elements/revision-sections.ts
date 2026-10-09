import type {
  ElementContextMenuItem,
  ElementEditPayload,
} from '@/modules/elements/composables/useElementEditor';

export interface RevisionItem {
  id: string | number;
  label: string;
  description?: string;
  active?: boolean;
  href?: string;
}

export interface RevisionGroup {
  id: string;
  label?: string;
  items: Array<RevisionItem>;
}

function toItem(item: ElementContextMenuItem, index: number): RevisionItem {
  return {
    // Hrefs are unique per draft/revision; the index only covers the
    // theoretical item with no href.
    id: item.href ?? `item-${index}`,
    label: item.label ?? '',
    description: item.description,
    href: item.href,
    active: item.selected,
  };
}

export interface RevisionSections {
  groups: Array<RevisionGroup>;
  footer: Array<RevisionItem>;
}

/**
 * The editor's drafts and revisions, from the same payload as the breadcrumb's
 * revision switcher: a flat list where `heading` rows stand in for the nesting.
 * Folded back into groups here — a leading unlabeled group for "Current", then
 * one per heading — with anything past the `hr` as the "View all revisions"
 * footer.
 */
export function revisionSections(
  payload: ElementEditPayload
): RevisionSections {
  const groups: Array<RevisionGroup> = [];
  const footer: Array<RevisionItem> = [];
  let group: RevisionGroup | null = null;
  let inFooter = false;

  (payload.contextMenu?.items ?? []).forEach((item, index) => {
    // The rule separates the list proper from the "View all revisions" link
    // the server appends when there are more revisions than it sent.
    if (item.type === 'hr') {
      inFooter = true;
      group = null;

      return;
    }

    if (item.type === 'heading') {
      group = {id: `group-${index}`, label: item.label, items: []};
      groups.push(group);

      return;
    }

    if (inFooter) {
      footer.push(toItem(item, index));

      return;
    }

    // "Current" arrives before any heading.
    if (!group) {
      group = {id: `group-${index}`, items: []};
      groups.push(group);
    }

    group.items.push(toItem(item, index));
  });

  return {groups, footer};
}
