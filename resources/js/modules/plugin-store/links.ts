import type {Plugin} from './types';

export function reviewUrl(plugin: Pick<Plugin, 'handle'>): string {
  return `https://console.craftcms.com/accounts/me/plugin-store/reviews/${plugin.handle}`;
}

export function reportIssueUrl(
  plugin: Pick<Plugin, 'handle' | 'name'>
): string {
  const subject = encodeURIComponent(`Issue with ${plugin.name}`);
  const body = encodeURIComponent(
    `I would like to report the following issue with ${plugin.name} (https://plugins.craftcms.com/${plugin.handle}):\n\n`
  );

  return `mailto:issues@craftcms.com?subject=${subject}&body=${body}`;
}
