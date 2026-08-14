import { Outlet } from "react-router-dom";
import TopHeader from "./TopHeader";
import BottomTabBar from "./BottomTabBar";
import Sidebar from "./Sidebar";

export default function AppLayout() {
  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      <Sidebar />
      <div className="md:ml-60">
        <TopHeader />
        <main className="p-4 pb-20 md:pb-4 max-w-6xl mx-auto">
          <Outlet />
        </main>
      </div>
      <BottomTabBar />
    </div>
  );
}