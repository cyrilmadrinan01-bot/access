import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    permissions: string[];
}

export interface PageProps {
    name: string;
    quote: { message: string; author: string }; // <-- change from string to object
    sidebarOpen: boolean;
    auth: {
        user: AuthUser | null;
    };
    flash?: {
        message?: string;
    };
}


export interface BreadcrumbItem {
    title: string;
    href: string;
}

/*export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
    children?: NavItem[];
}*/

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: {
        user: AuthUser; // non-nullable
    };
    sidebarOpen: boolean;
};


export interface User {
    id: number;
    empnum: string; 
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

type NavBase = {
    title: string
    icon?: LucideIcon
    isActive?: boolean
    count?: number  
}


export type NavItem =
    | (NavBase & {
          href: NonNullable<InertiaLinkProps['href']>;
          children?: never;
          count?: number; // <- add this
      })
    | (NavBase & {
          href?: never;
          children: NavItem[];
          count?: number; // <- add this
      })

