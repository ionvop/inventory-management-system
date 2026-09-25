import { Monitor, Moon, Sun, type LucideIcon } from 'lucide-react';
import type { HTMLAttributes } from 'react';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';

const options: { value: Appearance; icon: LucideIcon; label: string }[] = [
    { value: 'light', icon: Sun, label: 'Light' },
    { value: 'dark', icon: Moon, label: 'Dark' },
    { value: 'system', icon: Monitor, label: 'System' },
];

export default function AppearanceToggle({
    className = '',
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    const { appearance, updateAppearance } = useAppearance();

    return (
        <div
            role="group"
            aria-label="Color theme"
            className={cn(
                'inline-flex items-center gap-1 rounded-lg border border-border bg-muted p-1',
                className,
            )}
            {...props}
        >
            {options.map(({ value, icon: Icon, label }) => {
                const isActive = appearance === value;

                return (
                    <button
                        key={value}
                        type="button"
                        onClick={() => updateAppearance(value)}
                        aria-pressed={isActive}
                        title={`${label} theme`}
                        className={cn(
                            'flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium transition-colors',
                            isActive
                                ? 'bg-card text-foreground shadow-sm'
                                : 'text-muted-foreground hover:bg-card/60 hover:text-foreground',
                        )}
                    >
                        <Icon className="h-3.5 w-3.5" aria-hidden="true" />
                        <span className="hidden sm:inline">{label}</span>
                    </button>
                );
            })}
        </div>
    );
}
