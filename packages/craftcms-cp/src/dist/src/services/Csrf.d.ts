export declare class Csrf {
    tokenName: string | null;
    tokenValue: string | null;
    refreshPromise: Promise<string | null> | null;
    constructor();
    getToken(): Promise<string | null>;
    refreshToken(): Promise<string | null>;
    clearToken(): void;
}
