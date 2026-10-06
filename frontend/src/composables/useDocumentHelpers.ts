import { api } from "../services/api";
import type { Paginated, Product } from "../types";

export const documentKinds: Record<string, string> = {
  receipt: "Čeks",
  warranty: "Garantija",
  other: "Cits dokuments",
};

export function formatDate(value?: string | null) {
  if (!value) return "Nav norādīts";
  const date = new Date(`${value.slice(0, 10)}T12:00:00`);
  return Number.isNaN(date.getTime())
    ? value
    : new Intl.DateTimeFormat("lv-LV").format(date);
}

export function formatAmount(value?: string | number | null) {
  if (value === null || value === undefined || value === "") return "—";
  return new Intl.NumberFormat("lv-LV", {
    style: "currency",
    currency: "EUR",
  }).format(Number(value));
}

export function formatBytes(bytes: number) {
  return bytes < 1024 * 1024
    ? `${Math.max(1, Math.round(bytes / 1024))} KB`
    : `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}

export function queryString(
  values: Record<string, string | number | undefined>,
) {
  const query = new URLSearchParams();
  for (const [key, value] of Object.entries(values)) {
    if (value !== undefined && value !== "") query.set(key, String(value));
  }
  return query.toString();
}

export async function loadProductOptions(): Promise<Product[]> {
  const products: Product[] = [];
  let page = 1;
  let last = 1;
  do {
    const response = await api<Paginated<Product>>(
      `/products?sort=name&direction=asc&page=${page}&per_page=100`,
    );
    products.push(...response.data);
    last = response.last_page;
    page += 1;
  } while (page <= last);
  return products;
}
