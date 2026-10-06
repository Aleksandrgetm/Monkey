export class ApiError extends Error {
  status: number;
  errors: Record<string, string[]>;
  constructor(
    status: number,
    message: string,
    errors: Record<string, string[]> = {},
  ) {
    super(message);
    this.status = status;
    this.errors = errors;
  }
}
let csrfToken: string | null = null;
let csrfPending: Promise<void> | undefined;
export function resetCsrf() {
  csrfToken = null;
}
async function ensureCsrf(): Promise<void> {
  if (csrfToken) return;
  csrfPending ??= fetch("/api/auth/csrf", {
    credentials: "same-origin",
    headers: { Accept: "application/json" },
  })
    .then(async (response) => {
      if (!response.ok)
        throw new ApiError(
          response.status,
          "Neizdevās izveidot drošu savienojumu. Mēģini vēlreiz.",
        );
      csrfToken = (await response.json()).token;
    })
    .finally(() => {
      csrfPending = undefined;
    });
  return csrfPending;
}
export async function api<T>(
  path: string,
  options: RequestInit = {},
): Promise<T> {
  const method = (options.method || "GET").toUpperCase();
  const mutates = !["GET", "HEAD"].includes(method);
  try {
    if (mutates) await ensureCsrf();
    const headers = new Headers(options.headers);
    headers.set("Accept", "application/json");
    headers.set("X-Requested-With", "XMLHttpRequest");
    if (mutates && csrfToken) headers.set("X-CSRF-TOKEN", csrfToken);
    if (options.body && !(options.body instanceof FormData))
      headers.set("Content-Type", "application/json");
    const response = await fetch(`/api${path}`, {
      ...options,
      method,
      headers,
      credentials: "same-origin",
    });
    if (!response.ok) {
      const data = await response.json().catch(() => ({}));
      if (response.status === 419) resetCsrf();
      if (response.status === 401 && !path.startsWith("/auth/"))
        window.dispatchEvent(new Event("scan:unauthenticated"));
      const fallback =
        response.status === 419
          ? "Sesija ir beigusies. Lūdzu, mēģini vēlreiz."
          : response.status === 429
            ? "Pārāk daudz mēģinājumu. Uzgaidi un mēģini vēlreiz."
            : "Darbība neizdevās. Mēģini vēlreiz.";
      throw new ApiError(
        response.status,
        data.message || fallback,
        data.errors || {},
      );
    }
    if (response.status === 204) return undefined as T;
    return (await response.json()) as T;
  } catch (error) {
    if (error instanceof ApiError) throw error;
    throw new ApiError(
      0,
      "Nav savienojuma ar serveri. Pārbaudi savienojumu un mēģini vēlreiz.",
    );
  }
}
export function extractError(error: unknown): string {
  if (error instanceof ApiError) {
    const messages = Object.values(error.errors).flat();
    return messages.length ? messages.join(" ") : error.message;
  }
  return error instanceof Error ? error.message : "Neizdevās izpildīt darbību.";
}
export async function downloadFile(
  path: string,
  filename: string,
): Promise<void> {
  const response = await fetch(`/api${path}`, {
    credentials: "same-origin",
    headers: { Accept: "*/*" },
  });
  if (!response.ok) {
    const data = await response.json().catch(() => ({}));
    throw new ApiError(
      response.status,
      data.message || "Lejupielāde neizdevās.",
    );
  }
  const url = URL.createObjectURL(await response.blob());
  const link = document.createElement("a");
  link.href = url;
  link.download = filename;
  link.click();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}
