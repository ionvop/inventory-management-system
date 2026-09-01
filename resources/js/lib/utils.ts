import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

/**
 * Append a unit suffix to a numeric value, e.g. withUnit(10, "pcs") => "10 pcs".
 * Falls back to the bare value when no unit is provided.
 */
export function withUnit(value: number, unit?: string): string {
  return unit ? `${value} ${unit}` : `${value}`;
}
