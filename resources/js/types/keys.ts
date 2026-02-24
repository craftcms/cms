import type {InjectionKey} from 'vue';
import type {QueueService} from '@craftcms/cp/src/services/Queue.js';
import type {AxiosInstance} from 'axios';
import type {ConfigService} from '@craftcms/cp/src/services/Config.js';

export const Queue: InjectionKey<QueueService> = Symbol('Queue');
export const Axios: InjectionKey<AxiosInstance> = Symbol('Axios');
export const Config: InjectionKey<ConfigService> = Symbol('Config');
