import {ConfigService} from '@craftcms/ui';
import type {App} from 'vue';
import axios from 'axios';
import {QueueService} from '@/modules/queue/queue';
import {Axios, Config, Queue} from '@/common/types/keys';
import QueueManager from '@/modules/utilities/components/queue-manager/QueueManager.vue';
import QueueManagerToolbar from '@/modules/utilities/components/queue-manager/QueueManagerToolbar.vue';
import DeprecationErrors from '@/modules/utilities/components/deprecation-errors/DeprecationErrors.vue';
import DeprecationErrorsToolbar from '@/modules/utilities/components/deprecation-errors/DeprecationErrorsToolbar.vue';
import ClearCaches from '@/modules/utilities/components/clear-caches/ClearCaches.vue';
import FindReplace from '@/modules/utilities/components/find-replace/FindReplace.vue';
import DatabaseBackup from '@/modules/utilities/components/DatabaseBackup.vue';
import Migrations from '@/modules/utilities/components/Migrations.vue';
import Updates from '@/modules/updater/components/Updates.vue';
import ProjectConfig from '@/modules/utilities/components/project-config/ProjectConfig.vue';
import AssetIndexes from '@/modules/utilities/components/asset-indexes/AssetIndexes.vue';
import SystemMessages from '@/modules/utilities/components/system-messages/SystemMessages.vue';
import CpLink from '@/common/components/CpLink.vue';
import {cpComponentRegistry} from './components';
import {registerFormComponents} from '@/modules/forms/register';

export const config = ConfigService.getInstance();
export const queue = QueueService.getInstance();

registerFormComponents(cpComponentRegistry);

export function installCpApp(app: App): void {
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');

  app.provide(Queue, queue);
  app.provide(Axios, axios);
  app.provide(Config, config);

  app.component('QueueManager', QueueManager);
  app.component('QueueManagerToolbar', QueueManagerToolbar);
  app.component('DeprecationErrors', DeprecationErrors);
  app.component('DeprecationErrorsToolbar', DeprecationErrorsToolbar);
  app.component('ClearCaches', ClearCaches);
  app.component('FindReplace', FindReplace);
  app.component('DatabaseBackup', DatabaseBackup);
  app.component('Migrations', Migrations);
  app.component('Updates', Updates);
  app.component('ProjectConfig', ProjectConfig);
  app.component('AssetIndexes', AssetIndexes);
  app.component('SystemMessages', SystemMessages);
  app.component('CpLink', CpLink);

  cpComponentRegistry.install(app);
  app.onUnmount(() => cpComponentRegistry.uninstall(app));
}
