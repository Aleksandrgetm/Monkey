export type WarrantyStatus = "active" | "expiring" | "expired" | null;
export interface User {
  id: number;
  name: string;
  email: string;
  role: number;
  status: number;
  created_at: string;
  email_notifications: boolean;
  in_app_notifications: boolean;
  reminder_days: number;
  reminder_days_override: number | null;
  appearance: "light" | "dark" | "system";
}
export interface Category {
  id: number;
  name: string;
  documents_count?: number;
  products_count?: number;
}
export interface Product {
  id: number;
  name: string;
  category_id: number;
  category?: Category;
  merchant: string | null;
  amount: string | null;
  purchase_date: string | null;
  warranty_end_date: string | null;
  note: string | null;
  documents_count?: number;
  documents?: DocumentRecord[];
  created_at?: string;
}
export interface DocumentRecord {
  id: number;
  user_id: number;
  category_id: number;
  product_id: number | null;
  category?: Category;
  product?: Product | null;
  owner?: User;
  name: string | null;
  file_name: string;
  file_type: string;
  file_size: number;
  kind: "receipt" | "warranty" | "other";
  merchant: string | null;
  amount: string | null;
  purchase_date: string | null;
  warranty_end_date: string | null;
  note: string | null;
  warranty_status: WarrantyStatus;
  created_at: string;
}
export interface Reminder {
  id: number;
  document_id: number | null;
  message: string;
  kind: "warranty" | "system";
  status: number;
  notification_date: string;
  created_at: string;
  document?: Pick<DocumentRecord, "id" | "name" | "file_name"> | null;
}
export interface Paginated<T> {
  data: T[];
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
}
export interface Dashboard {
  documents: number;
  receipts: number;
  warranties: number;
  products: number;
  expiring: number;
  unread: number;
  recent: DocumentRecord[];
}
