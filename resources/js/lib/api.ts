import type {
  User,
  Item,
  Transaction,
  PaginatedResponse,
  ApiError,
  DashboardSummary,
  CreateItemRequest,
  UpdateItemRequest,
  CreateTransactionRequest,
  UpdateTransactionRequest,
  UpdateSettingsRequest,
  ItemFilters,
  TransactionFilters,
} from "@/types";

const API_PREFIX = "/api";

let getUserId: () => number | null = () => null;

export function setUserIdGetter(fn: () => number | null) {
  getUserId = fn;
}

/**
 * The browser's IANA timezone identifier, sent to the backend on each
 * request so report rendering and dashboard "today" calculations match the
 * viewer's local wall clock.
 */
export function getTimezone(): string {
  try {
    const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
    return tz || "UTC";
  } catch {
    return "UTC";
  }
}

class ApiClientError extends Error {
  status: number;
  errors?: Record<string, string[]>;

  constructor(message: string, status: number, errors?: Record<string, string[]>) {
    super(message);
    this.name = "ApiClientError";
    this.status = status;
    this.errors = errors;
  }
}

async function request<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  const userId = getUserId();
  const headers: Record<string, string> = {
    "Content-Type": "application/json",
    Accept: "application/json",
    ...(options.headers as Record<string, string>),
  };

  if (userId !== null && userId !== undefined) {
    headers["X-User-Id"] = String(userId);
  }

  headers["X-Timezone"] = getTimezone();

  const response = await fetch(endpoint, {
    ...options,
    headers,
  });

  if (!response.ok) {
    let errorData: ApiError = {};
    try {
      errorData = await response.json();
    } catch {
      // Use default error message
    }
    const message =
      errorData.error?.message ||
      errorData.message ||
      `Request failed with status ${response.status}`;
    const errors = errorData.error?.details || errorData.errors;
    throw new ApiClientError(message, response.status, errors);
  }

  // Handle 204 No Content
  if (response.status === 204) {
    return undefined as T;
  }

  return response.json();
}

function buildQueryString(params: Record<string, unknown>): string {
  const searchParams = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== "") {
      searchParams.set(key, String(value));
    }
  });
  const qs = searchParams.toString();
  return qs ? `?${qs}` : "";
}

// ============ Auth / Users ============

export const api = {
  // Users
  getUsers: async (): Promise<User[]> => {
    const res = await request<PaginatedResponse<User>>(
      `${API_PREFIX}/users?limit=100`
    );
    return res.data;
  },

  createUser: async (data: { username: string }): Promise<User> => {
    const res = await request<{ data: User }>(`${API_PREFIX}/users`, {
      method: "POST",
      body: JSON.stringify(data),
    });
    return res.data;
  },

  updateUser: (id: number, data: { username: string }) =>
    request<{ data: User }>(`${API_PREFIX}/users/${id}`, {
      method: "PUT",
      body: JSON.stringify(data),
    }).then((res) => res.data),

  deleteUser: (id: number) =>
    request<void>(`${API_PREFIX}/users/${id}`, { method: "DELETE" }),

  // Dashboard
  getDashboardSummary: async (): Promise<DashboardSummary> => {
    const res = await request<{ data: DashboardSummary }>(`${API_PREFIX}/dashboard/summary`);
    return res.data;
  },

  // Items
  getItems: (filters: ItemFilters = {}) =>
    request<PaginatedResponse<Item>>(
      `${API_PREFIX}/items${buildQueryString(filters as Record<string, unknown>)}`
    ),

  getItem: (id: number) => request<Item>(`${API_PREFIX}/items/${id}`),

  createItem: (data: CreateItemRequest) =>
    request<Item>(`${API_PREFIX}/items`, {
      method: "POST",
      body: JSON.stringify(data),
    }),

  updateItem: (id: number, data: UpdateItemRequest) =>
    request<Item>(`${API_PREFIX}/items/${id}`, {
      method: "PUT",
      body: JSON.stringify(data),
    }),

  deleteItem: (id: number) =>
    request<void>(`${API_PREFIX}/items/${id}`, { method: "DELETE" }),

  // Transactions
  getTransactions: (filters: TransactionFilters = {}) =>
    request<PaginatedResponse<Transaction>>(
      `${API_PREFIX}/transactions${buildQueryString(filters as Record<string, unknown>)}`
    ),

  getTransaction: (id: number) =>
    request<Transaction>(`${API_PREFIX}/transactions/${id}`),

  createTransaction: (data: CreateTransactionRequest) =>
    request<Transaction>(`${API_PREFIX}/transactions`, {
      method: "POST",
      body: JSON.stringify(data),
    }),

  updateTransaction: (id: number, data: UpdateTransactionRequest) =>
    request<Transaction>(`${API_PREFIX}/transactions/${id}`, {
      method: "PUT",
      body: JSON.stringify(data),
    }),

  deleteTransaction: (id: number) =>
    request<void>(`${API_PREFIX}/transactions/${id}`, { method: "DELETE" }),

  // Settings
  getSettings: async (): Promise<Record<string, string>> => {
    const res = await request<{ data: Record<string, string> }>(`${API_PREFIX}/settings`);
    return res.data;
  },

  updateSettings: (data: UpdateSettingsRequest) =>
    request<{ data: Record<string, string> }>(`${API_PREFIX}/settings`, {
      method: "PUT",
      body: JSON.stringify(data.settings),
    }),
};

export { ApiClientError };
export default api;