export declare function tokenizePattern(pattern: string): Array<string | string[]> | false;
export declare function parseToken(token: string[], args?: Record<string, any>): string | number | boolean | null | false;
export declare function formatMessage(pattern: string, params: object): string;
export declare function t(category: string, message: string, params: Record<any, any>, store?: Record<string, any>): string;
