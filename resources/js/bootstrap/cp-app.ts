import {ConfigService} from '@craftcms/ui';
import {defineAsyncComponent, type App} from 'vue';
import {QueueService} from '@/modules/queue/queue';
import {Config, Queue} from '@/common/types/keys';
import CpLink from '@/common/components/CpLink.vue';
import {cpComponentRegistry} from './components';
import {elementDetailsPanelRegistry} from './element-details-panels';
import {registerUiComponents} from '@/modules/ui/register';
import {registerWidgetComponents} from '@/modules/dashboard/register';
import {registerActivityComponents} from '@/modules/activity/register';
import {registerWorkflowComponents} from '@/modules/workflows/register';
import {registerLayoutComponents} from '@/common/layouts/register';
import {useUnderlineLinks} from '@/common/composables/useUnderlineLinks';
import {useTheme} from '@/common/composables/useTheme';

/**
 * The utility and settings screens' components, which only their own pages
 * render, so they load when one of those pages first does.
 */
const pageComponents = {
  QueueManager: defineAsyncComponent(
    () =>
      import('@/modules/utilities/components/queue-manager/QueueManager.vue')
  ),
  QueueManagerToolbar: defineAsyncComponent(
    () =>
      import('@/modules/utilities/components/queue-manager/QueueManagerToolbar.vue')
  ),
  DeprecationErrors: defineAsyncComponent(
    () =>
      import('@/modules/utilities/components/deprecation-errors/DeprecationErrors.vue')
  ),
  DeprecationErrorsToolbar: defineAsyncComponent(
    () =>
      import('@/modules/utilities/components/deprecation-errors/DeprecationErrorsToolbar.vue')
  ),
  ClearCaches: defineAsyncComponent(
    () => import('@/modules/utilities/components/clear-caches/ClearCaches.vue')
  ),
  FindReplace: defineAsyncComponent(
    () => import('@/modules/utilities/components/find-replace/FindReplace.vue')
  ),
  DatabaseBackup: defineAsyncComponent(
    () => import('@/modules/utilities/components/DatabaseBackup.vue')
  ),
  Migrations: defineAsyncComponent(
    () => import('@/modules/utilities/components/Migrations.vue')
  ),
  Updates: defineAsyncComponent(
    () => import('@/modules/updater/components/Updates.vue')
  ),
  ProjectConfig: defineAsyncComponent(
    () =>
      import('@/modules/utilities/components/project-config/ProjectConfig.vue')
  ),
  AssetIndexes: defineAsyncComponent(
    () =>
      import('@/modules/utilities/components/asset-indexes/AssetIndexes.vue')
  ),
  SystemMessages: defineAsyncComponent(
    () =>
      import('@/modules/utilities/components/system-messages/SystemMessages.vue')
  ),
  AiGuidance: defineAsyncComponent(
    () => import('@/modules/ai/guidance/AiGuidance.vue')
  ),
  AiSkills: defineAsyncComponent(
    () => import('@/modules/ai/skills/AiSkills.vue')
  ),
};

export const config = ConfigService.getInstance();
export const queue = QueueService.getInstance();

registerUiComponents(cpComponentRegistry);
registerWidgetComponents(cpComponentRegistry);
registerActivityComponents(cpComponentRegistry);
registerWorkflowComponents(cpComponentRegistry, elementDetailsPanelRegistry);
registerLayoutComponents(cpComponentRegistry);

export function installCpApp(app: App): void {
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');

  app.provide(Queue, queue);
  app.provide(Config, config);

  for (const [name, component] of Object.entries(pageComponents)) {
    app.component(name, component);
  }
  app.component('CpLink', CpLink);

  app.runWithContext(useUnderlineLinks);
  app.runWithContext(useTheme);

  cpComponentRegistry.install(app);
  app.onUnmount(() => cpComponentRegistry.uninstall(app));
}
