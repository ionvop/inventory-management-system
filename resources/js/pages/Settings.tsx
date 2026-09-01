import ProfileSection from "@/components/settings/ProfileSection";

export default function Settings() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-bold text-gray-900 dark:text-white">
          Settings
        </h1>
        <p className="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
          Configure application preferences and your profile
        </p>
      </div>

      {/* Profile Section */}
      <ProfileSection />
    </div>
  );
}