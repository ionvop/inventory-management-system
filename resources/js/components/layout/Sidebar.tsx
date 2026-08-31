import { NavLink, useLocation, useNavigate } from "react-router-dom";
import { useUser } from "@/contexts/UserContext";
import { useTheme } from "next-themes";
import {
  LayoutDashboard,
  Package,
  ArrowLeftRight,
  BarChart3,
  Settings,
  User,
  Users,
  Sun,
  Moon,
  LogOut,
} from "lucide-react";
import { Button } from "@/components/ui/button";

const tabs = [
  { to: "/dashboard", icon: LayoutDashboard, label: "Dashboard" },
  { to: "/items", icon: Package, label: "Items" },
  { to: "/transactions", icon: ArrowLeftRight, label: "Transactions" },
  { to: "/reports", icon: BarChart3, label: "Reports" },
  { to: "/users", icon: Users, label: "Users" },
  { to: "/settings", icon: Settings, label: "Settings" },
];

export default function Sidebar() {
  const location = useLocation();
  const { user, logout } = useUser();
  const { theme, setTheme } = useTheme();
  const navigate = useNavigate();

  const handleSwitchUser = () => {
    logout();
    navigate("/");
  };

  return (
    <aside className="hidden md:flex flex-col fixed left-0 top-0 bottom-0 w-60 border-r border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 z-40">
      {/* Logo */}
      <div className="flex items-center gap-3 h-14 px-4 border-b border-gray-200 dark:border-gray-800">
        <div className="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center flex-shrink-0">
          <span className="text-white font-bold text-sm">I</span>
        </div>
        <span className="font-semibold text-gray-900 dark:text-white">
          Inventory
        </span>
      </div>

      {/* Navigation */}
      <nav className="flex-1 py-4 px-3 space-y-1 overflow-y-auto">
        {tabs.map(({ to, icon: Icon, label }) => {
          const isActive = location.pathname.startsWith(to);
          return (
            <NavLink
              key={to}
              to={to}
              className={`flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors ${
                isActive
                  ? "bg-indigo-50 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-300"
                  : "text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white"
              }`}
            >
              <Icon className="w-5 h-5 flex-shrink-0" />
              {label}
            </NavLink>
          );
        })}
      </nav>

      {/* User & Theme Footer */}
      <div className="border-t border-gray-200 dark:border-gray-800 p-3 space-y-3">
        {user && (
          <div className="flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-800">
            <User className="w-4 h-4 text-indigo-500 flex-shrink-0" />
            <span className="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">
              {user.username}
            </span>
          </div>
        )}

        <div className="flex items-center gap-2">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => setTheme(theme === "dark" ? "light" : "dark")}
            className="flex-1 justify-start gap-2 h-9 rounded-lg text-gray-600 dark:text-gray-400"
          >
            {theme === "dark" ? (
              <Sun className="w-4 h-4" />
            ) : (
              <Moon className="w-4 h-4" />
            )}
            <span className="text-xs">{theme === "dark" ? "Light" : "Dark"} Mode</span>
          </Button>
          <Button
            variant="ghost"
            size="sm"
            onClick={handleSwitchUser}
            className="flex-1 justify-start gap-2 h-9 rounded-lg text-gray-600 dark:text-gray-400"
          >
            <LogOut className="w-4 h-4" />
            <span className="text-xs">Switch</span>
          </Button>
        </div>
      </div>
    </aside>
  );
}