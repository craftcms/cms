/**
 * @TODO Make this configurable
 */
export declare function getApiUrl(action?: string): string;
export declare const apiClient: import('axios').AxiosInstance;
export declare function sendApiRequest(method: string, uri: string, options?: Record<any, any>): Promise<import('axios').AxiosResponse<any, any, {}>>;
