import type {InjectionKey} from 'vue';
import type {QueueService} from '@craftcms/cp/services/Queue';
import type {AxiosInstance} from 'axios';
import type {ConfigService} from '@craftcms/cp/services/Config';

export const Queue: InjectionKey<QueueService> = Symbol('Queue');
export const Axios: InjectionKey<AxiosInstance> = Symbol('Axios');
export const Config: InjectionKey<ConfigService> = Symbol('Config');
