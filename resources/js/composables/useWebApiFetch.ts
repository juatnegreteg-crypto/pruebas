export async function webApiFetch(
    input: RequestInfo | URL,
    init: RequestInit = {},
): Promise<Response> {
    const headers = new Headers(init.headers ?? {});

    if (!headers.has('Accept')) {
        headers.set('Accept', 'application/json');
    }

    if (!headers.has('X-Requested-With')) {
        headers.set('X-Requested-With', 'XMLHttpRequest');
    }

    const csrfTokenFromMeta =
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? '';

    if (csrfTokenFromMeta !== '' && !headers.has('X-CSRF-TOKEN')) {
        headers.set('X-CSRF-TOKEN', csrfTokenFromMeta);
    }

    const xsrfTokenFromCookie = document.cookie
        .split('; ')
        .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
        ?.split('=')[1];

    if (
        xsrfTokenFromCookie !== undefined &&
        xsrfTokenFromCookie !== '' &&
        !headers.has('X-XSRF-TOKEN')
    ) {
        headers.set('X-XSRF-TOKEN', decodeURIComponent(xsrfTokenFromCookie));
    }

    return fetch(input, {
        credentials: 'include',
        ...init,
        headers,
    });
}
