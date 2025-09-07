import { AxiosError } from "axios";

export interface ApiErrorShape {
  message: string;
  status?: number;
  code?: string;
  errors?: Record<string, string[]>;
}

export function toApiError(e: unknown): ApiErrorShape {
  if (e && typeof e === "object" && (e as AxiosError).isAxiosError) {
    const err = e as AxiosError<any>;
    const status = err.response?.status;
    const data = err.response?.data ?? {};
    const message =
      typeof data?.message === "string"
        ? data.message
        : err.message || "Request failed";
    const code =
      typeof (data as any)?.code === "string" ? (data as any).code : undefined;
    const errors =
      data && typeof data === "object" && "errors" in data
        ? (data.errors as Record<string, string[]>)
        : undefined;
    return { message, status, code, errors };
  }
  return { message: "Unknown error" };
}
