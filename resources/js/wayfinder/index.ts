export type QueryParams = Record<
    string,
    | string
    | number
    | boolean
    | string[]
    | null
    | undefined
    | Record<string, string | number | boolean>
>;

type Method = "get" | "post" | "put" | "delete" | "patch" | "head" | "options";

let urlDefaults: Record<string, unknown> = {};

export type RouteDefinition<TMethod extends Method | Method[]> = {
    url: string;
} & (TMethod extends Method[] ? { methods: TMethod } : { method: TMethod });

export type RouteFormDefinition<TMethod extends Method> = {
    action: string;
    method: TMethod;
};

export type RouteQueryOptions = {
    query?: QueryParams;
    mergeQuery?: QueryParams;
};

export const queryParams = (options?: RouteQueryOptions) => {
    if (!options || (!options.query && !options.mergeQuery)) {
        return "";
    }

    const query = options.query ?? options.mergeQuery;
    const includeExisting = options.mergeQuery !== undefined;

    const getValue = (value: string | number | boolean) => {
        if (value === true) {
            return "1";
        }

        if (value === false) {
            return "0";
        }

        return value.toString();
    };

    const params = new URLSearchParams(
        includeExisting && typeof window !== "undefined"
            ? window.location.search
            : "",
    );

    for (const key in query) {
        if (query[key] === undefined || query[key] === null) {
            params.delete(key);
            continue;
        }

        if (Array.isArray(query[key])) {
            if (params.has(`${key}[]`)) {
                params.delete(`${key}[]`);
            }

            query[key].forEach((value) => {
                params.append(`${key}[]`, value.toString());
            });
        } else if (typeof query[key] === "object") {
            params.forEach((_, paramKey) => {
                if (paramKey.startsWith(`${key}[`)) {
                    params.delete(paramKey);
                }
            });

            for (const subKey in query[key]) {
                if (typeof query[key][subKey] === "undefined") {
                    continue;
                }

                if (
                    ["string", "number", "boolean"].includes(
                        typeof query[key][subKey],
                    )
                ) {
                    params.set(
                        `${key}[${subKey}]`,
                        getValue(query[key][subKey]),
                    );
                }
            }
        } else {
            params.set(key, getValue(query[key]));
        }
    }

    const str = params.toString();

    return str.length > 0 ? `?${str}` : "";
};

export const setUrlDefaults = (params: Record<string, unknown>) => {
    urlDefaults = params;
};

export const addUrlDefault = (
    key: string,
    value: string | number | boolean,
) => {
    urlDefaults[key] = value;
};

export const applyUrlDefaults = <T extends Record<string, unknown> | undefined>(
    existing: T,
): T => {
    const existingParams = { ...(existing ?? ({} as Record<string, unknown>)) };

    for (const key in urlDefaults) {
        if (
            existingParams[key] === undefined &&
            urlDefaults[key] !== undefined
        ) {
            (existingParams as Record<string, unknown>)[key] = urlDefaults[key];
        }
    }

    return existingParams as T;
};

export const validateParameters = (
    args: Record<string, unknown> | undefined,
    optional: string[],
) => {
    const missing = optional.filter((key) => !args?.[key]);
    const expectedMissing = optional.slice(missing.length * -1);

    for (let i = 0; i < missing.length; i++) {
        if (missing[i] !== expectedMissing[i]) {
            throw Error(
                "Unexpected optional parameters missing. Unable to generate a URL.",
            );
        }
    }
};
