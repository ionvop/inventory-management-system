export type ProfileRole = 'staff' | 'supervisor' | 'administrator';

export const roleLabels: Record<ProfileRole, string> = {
    staff: 'Staff',
    supervisor: 'Supervisor',
    administrator: 'Administrator',
};

export const roleOptions: ProfileRole[] = [
    'staff',
    'supervisor',
    'administrator',
];
