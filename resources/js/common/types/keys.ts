import type {InjectionKey} from 'vue';
import type {QueueService} from '@/modules/queue/queue';
import type {AxiosInstance} from 'axios';
import type {ConfigService} from '@craftcms/ui/services/Config';

export const Queue: InjectionKey<QueueService> = Symbol('Queue');
export const Axios: InjectionKey<AxiosInstance> = Symbol('Axios');
export const Config: InjectionKey<ConfigService> = Symbol('Config');
