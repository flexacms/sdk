/**
 * Error thrown for every non-2xx response from the content API.
 */
export class FlexaError extends Error {
    override readonly name = 'FlexaError';

    constructor(
        message: string,
        /** HTTP status, or 0 when the request never reached the server. */
        readonly status: number,
        /** Validation errors keyed by field (422 responses). */
        readonly errors: Record<string, string[]> = {},
        /** Parsed response body, when there was one. */
        readonly body: unknown = null,
    ) {
        super(message);
    }

    get isNotFound(): boolean {
        return this.status === 404;
    }

    get isUnauthorized(): boolean {
        return this.status === 401;
    }

    get isForbidden(): boolean {
        return this.status === 403;
    }

    get isValidationError(): boolean {
        return this.status === 422;
    }

    get isRateLimited(): boolean {
        return this.status === 429;
    }
}

export function isFlexaError(error: unknown): error is FlexaError {
    return error instanceof FlexaError;
}
