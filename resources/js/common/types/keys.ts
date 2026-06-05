import type {InjectionKey} from 'vue';
import type {QueueService} from '@/common/services/Queue';
import type {AxiosInstance} from 'axios';

export const Queue: InjectionKey<QueueService> = Symbol('Queue');
export const Axios: InjectionKey<AxiosInstance> = Symbol('Axios');
