// ============ Core Entities ============

export interface User {
  id: number;
  username: string;
  time: number;
}

export interface Item {
  id: number;
  name: string;
  unit: string;
  current_stock: number;
  minimum_stock: number;
  is_low_stock: boolean;
  time: number;
}

export type MovementType = "in" | "out";

export interface Transaction {
  id: number;
  item_id: number;
  item?: Item;
  user_id: number;
  user?: User;
  movement: MovementType;
  quantity: number;
  time: number;
  posted_time: number;
}

export interface Setting {
  id: number;
  key: string;
  value: string;
  created_at: number;
  updated_at: number;
}

// ============ API Response Types ============

export interface PaginatedResponse<T> {
  data: T[];
  pagination: {
    page: number;
    limit: number;
    total: number;
    total_pages: number;
  };
}

export interface ApiError {
  error?: {
    message: string;
    details?: Record<string, string[]>;
  };
  message?: string;
  errors?: Record<string, string[]>;
}

export interface DashboardSummary {
  total_items: number;
  low_stock_count: number;
  today_transactions: {
    in_count: number;
    out_count: number;
  };
  low_stock_items: Item[];
  recent_transactions: Transaction[];
}

export interface Report {
  generated_time: string;
  format: "json" | "csv" | "pdf";
  date_from?: string;
  date_to?: string;
  items: ReportItem[];
}

export interface ReportItem {
  id: number;
  name: string;
  unit: string;
  current_stock: number;
  minimum_stock: number;
  is_low_stock: boolean;
  transactions: Transaction[];
}

// ============ API Request Types ============

export interface CreateItemRequest {
  name: string;
  unit: string;
  minimum_stock: number;
}

export interface UpdateItemRequest extends Partial<CreateItemRequest> {}

export interface CreateTransactionRequest {
  item_id: number;
  movement: MovementType;
  quantity: number;
  posted_time?: number;
}

export interface UpdateTransactionRequest {
  movement?: MovementType;
  quantity?: number;
  posted_time?: number;
}

export interface UpdateSettingsRequest {
  settings: Record<string, string>;
}

export interface ReportRequest {
  date_from?: string;
  date_to?: string;
  format: "json" | "csv" | "pdf";
}

// ============ Query Params ============

export interface PaginationParams {
  page?: number;
  limit?: number;
}

export interface ItemFilters extends PaginationParams {
  search?: string;
  sort?: string;
  order?: "asc" | "desc";
}

export interface TransactionFilters extends PaginationParams {
  item_id?: number;
  movement?: MovementType;
  date_from?: string;
  date_to?: string;
  sort?: string;
  order?: "asc" | "desc";
}