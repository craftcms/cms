import type {InjectionKey} from 'vue';
import type {QueueService} from '@craftcms/cp/services/Queue.ts.mjs';
import type {AxiosInstance} from 'axios';

export const Queue: InjectionKey<QueueService> = Symbol('Queue');
export const Axios: InjectionKey<AxiosInstance> = Symbol('Axios');
