import { useUser } from "@/contexts/UserContext";
import { useTheme } from "next-themes";
import { useNavigate } from "react-router-dom";
import { User, Sun, Moon, LogOut } from "lucide-react";
import { Button } from "@/components/ui/button";

export default function TopHeader() {
  const { user, logout } = useUser();
  const { theme, setTheme } = useTheme();
  const navigate = useNavigate();

  const handleSwitchUser = () => {
    logout();
    navigate("/");
  };

  return (
    <header className="sticky top-0 z-30 flex items-center justify-between h-14 px-4 border-b border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-950/80 backdrop-blur-sm">
      <div className="flex items-center gap-2">
        <div className="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center">
          <span className="text-white font-bold text-sm">I</span>
        </div>
        <span className="font-semibold text-gray-900 dark:text-white text-sm hidden sm:block">
          Inventory
        </span>
      </div>

      <div className="flex items-center gap-2">
        {/* Theme Toggle */}
        <Button
          variant="ghost"
          size="icon"
          onClick={() => setTheme(theme === "dark" ? "light" : "dark")}
          className="h-9 w-9 rounded-lg"
          aria-label="Toggle theme"
        >
          <Sun className="h-4 w-4 rotate-0 scale-100 transition-all dark:-rotate-90 dark:scale-0" />
          <Moon className="absolute h-4 w-4 rotate-90 scale-0 transition-all dark:rotate-0 dark:scale-100" />
        </Button>

        {/* User Badge */}
        {user && (
          <div className="flex items-center gap-2">
            <div className="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-800">
              <User className="w-3.5 h-3.5 text-indigo-500" />
              <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                {user.username}
              </span>
            </div>
            <Button
              variant="ghost"
              size="sm"
              onClick={handleSwitchUser}
              className="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 h-8"
            >
              <LogOut className="w-3.5 h-3.5 mr-1" />
              <span className="hidden sm:inline">Switch</span>
            </Button>
          </div>
        )}
      </div>
    </header>
  );
}