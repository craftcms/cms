import {actionClient} from '@craftcms/ui';
import {ref, type Ref} from 'vue';
import AboutCraftController from '@actions/App/AboutCraftController';
import type {AboutCraftInfo} from '@/common/components/about-craft';
import useCraftData from '@/common/composables/useCraftData';

/**
 * What the “About Craft CMS” view shows, for the places that open it on
 * demand: the command palette and the header's edition menu. Craft's version
 * and edition come with the page, so they show straight away; its trials and
 * updates are fetched by `load()`, as they aren't.
 */
export default function useAboutCraftInfo(): {
  info: Ref<AboutCraftInfo>;
  load: () => Promise<void>;
} {
  const {app} = useCraftData();

  const info = ref<AboutCraftInfo>({
    version: app.value.version,
    edition: app.value.edition.name,
    trial: null,
    updates: null,
  });

  async function load(): Promise<void> {
    try {
      const {data} = await actionClient.get<AboutCraftInfo>(
        AboutCraftController.url()
      );
      info.value = data;
    } catch {
      // The version and edition are already showing, without the trials.
    }
  }

  return {info, load};
}
