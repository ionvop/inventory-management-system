import { createContext, useContext, useState, useCallback, useEffect, type ReactNode } from "react";
import { setUserIdGetter } from "@/lib/api";
import type { User } from "@/types";

interface UserContextType {
  user: User | null;
  setUser: (user: User | null) => void;
  logout: () => void;
  isAuthenticated: boolean;
}

const UserContext = createContext<UserContextType | undefined>(undefined);

const STORAGE_KEY = "inventory_current_user";

function loadUser(): User | null {
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored) {
      return JSON.parse(stored);
    }
  } catch {
    // Corrupted data
  }
  return null;
}

function saveUser(user: User | null) {
  if (user) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(user));
  } else {
    localStorage.removeItem(STORAGE_KEY);
  }
}

export function UserProvider({ children }: { children: ReactNode }) {
  const [user, setUserState] = useState<User | null>(loadUser);

  const setUser = useCallback((newUser: User | null) => {
    setUserState(newUser);
    saveUser(newUser);
  }, []);

  const logout = useCallback(() => {
    setUserState(null);
    saveUser(null);
  }, []);

  // Keep the API layer in sync with the current user
  useEffect(() => {
    setUserIdGetter(() => {
      // We need to read from localStorage directly to avoid stale closure
      try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored) {
          const u = JSON.parse(stored) as User;
          return u.id;
        }
      } catch {
        // ignore
      }
      return null;
    });
  }, []);

  // Also update when user changes
  useEffect(() => {
    setUserIdGetter(() => (user ? user.id : null));
  }, [user]);

  return (
    <UserContext.Provider
      value={{ user, setUser, logout, isAuthenticated: !!user }}
    >
      {children}
    </UserContext.Provider>
  );
}

export function useUser(): UserContextType {
  const context = useContext(UserContext);
  if (context === undefined) {
    throw new Error("useUser must be used within a UserProvider");
  }
  return context;
}