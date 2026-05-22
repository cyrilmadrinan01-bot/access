import type { Updater } from '@tanstack/vue-table';
import type { ClassValue } from "clsx";
import type { Ref } from 'vue';
import { clsx } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

export function toUrl(path: string): string {
  return `/assets/${path}`;
}

// utils.ts
export function urlIsActive(url: string, currentUrl: string): boolean {
  return url === currentUrl;
}


export function valueUpdater<T>(updaterOrValue: T | ((prev: T) => T), ref: Ref<T>) {
  ref.value = typeof updaterOrValue === 'function'
    ? (updaterOrValue as (prev: T) => T)(ref.value)
    : updaterOrValue;
}

// lib/utils.ts
// Converts input to a valid Date or null
export function toValidDate(value: unknown): Date | null {
  if (value instanceof Date) return value
  if (typeof value === "string" && value.trim() !== "") {
    const d = new Date(value)
    return isNaN(d.getTime()) ? null : d
  }
  return null
}

export function toDatetimeLocal(value: string | Date | null | undefined, fallbackDate?: Date) {
  let date: Date

  if (value) {
    // If value exists, convert it
    if (value instanceof Date) {
      date = value
    } else {
      date = new Date(value.replace(' ', 'T'))
    }
  } else if (fallbackDate) {
    // If null, use fallback date with time 00:00
    date = new Date(fallbackDate)
    date.setHours(0, 0, 0, 0) // set time to 00:00
  } else {
    // Fallback to today 00:00 if everything else fails
    date = new Date()
    date.setHours(0, 0, 0, 0)
  }

  // Format to "YYYY-MM-DDTHH:MM"
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')

  return `${year}-${month}-${day}T${hours}:${minutes}`
}




// Converts a Date or string to MM/DD/YYYY hh:mm AM/PM (for display in table/modal)
export function formatDateTimeToMMDDYYYYhhmm(value: string | Date | null | undefined): string {
  if (!value) return ''
  const date = value instanceof Date ? value : new Date(value)

  return date.toLocaleString('en-US', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  })
}



// Resolves the main date, or fallback date, or today
export function resolveDate(time: unknown, dated: unknown): Date {
  return toValidDate(time) ?? toValidDate(dated) ?? new Date()
}
