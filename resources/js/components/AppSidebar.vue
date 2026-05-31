<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';

import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    Folder,
    LayoutGrid,
    BriefcaseMedical,
    Clock,
    Users,
    BadgeCheck,
    TimerOff,
    ClipboardCheck,
    CalendarRange,
    AlarmClockCheck,
    Banknote,
    HandCoins,
    Settings,
    Wallet,
    CalendarHeart,
    LucideScanQrCode,
    ListPlusIcon,
} from 'lucide-vue-next';

import AppLogo from './AppLogo.vue';
import { route } from 'ziggy-js';
import type { AppPageProps } from '@/types';

const page = usePage<AppPageProps>();
const user = page.props.auth.user;

/**
 * Permissions
 */
const canApproveCorrection = user?.permissions?.includes('approve correction') ?? false;
const canApproveOvertime = user?.permissions?.includes('approve overtime') ?? false;
const canApproveLeave = user?.permissions?.includes('approve leave') ?? false;

const canManageRoles = user?.permissions?.includes('manage roles') ?? false;
const canManageLeave = user?.permissions?.includes('manage leave') ?? false;
const canManageCutOff = user?.permissions?.includes('manage cutoff') ?? false;
const canManageShiftcode = user?.permissions?.includes('manage shiftcode') ?? false;
const canManageDevice = user?.permissions?.includes('manage device') ?? false;
const canManageTeam = user?.permissions?.includes('manage team') ?? false;
const canManagePayroll = user?.permissions?.includes('manage payroll') ?? false;
const canManageMedical = user?.permissions?.includes('manage medical') ?? false;
const canManageEmployees = user?.permissions?.includes('manage employees') ?? false;
const canManageHoliday = user?.permissions?.includes('manage holiday') ?? false;
const canManagePicklist = user?.permissions?.includes('manage picklist') ?? false;

/**
 * MAIN NAVIGATION
 */
const mainNavItems: NavItem[] = [
    { title: 'Dashboard', href: route('dashboard'), icon: LayoutGrid },
    { title: 'Timekeeping', href: route('timekeeping'), icon: Clock },
    { title: 'Medical Re-imbursement', href: route('medical'), icon: BriefcaseMedical },
    { title: 'File TimeOff', href: route('leaves.create'), icon: TimerOff },
    { title: 'Payslip', href: route('employees.payslips'), icon: Banknote },
    { title: 'Reports', href: route('reports.index'), icon: BookOpen },
];

/**
 * APPROVALS GROUP
 */
const approvalChildren: NavItem[] = [];

if (canApproveCorrection) {
    approvalChildren.push({
        title: 'Correction',
        href: route('approvals.timekeeping'),
        icon: BadgeCheck,
        count: Number(page.props.pendingCorrectionCount) || 0,
    });
}

if (canApproveOvertime) {
    approvalChildren.push({
        title: 'Overtime',
        href: route('approvals.overtime'),
        icon: AlarmClockCheck,
        count: Number(page.props.pendingOvertimeCount) || 0,
    });
}

if (canApproveLeave) {
    approvalChildren.push({
        title: 'Time-off',
        href: route('leave-approvals.index'),
        icon: TimerOff,
        count: Number(page.props.pendingLeaveCount) || 0,
    });
}

if (approvalChildren.length > 0) {
    mainNavItems.push({
        title: 'My Approvals',
        icon: ClipboardCheck,
        children: approvalChildren,
        count: approvalChildren.reduce((sum, item) => sum + (item.count ?? 0), 0),
    });
}

/**
 * PAYROLL ADMINISTRATION GROUP
 */
const payrollChildren: NavItem[] = [];

if (canManagePayroll) {
    payrollChildren.push({
        title: 'Payroll Process',
        href: route('payroll.index'),
        icon: Banknote,
    });
}

if (payrollChildren.length > 0) {
    mainNavItems.push({
        title: 'Payroll',
        icon: Wallet,
        children: payrollChildren,
    });
}

/**
 * ADMIN SETTINGS GROUP
 */
const adminChildren: NavItem[] = [];

if (canManageCutOff) {
    adminChildren.push({
        title: 'Cut-Off Management',
        href: route('admin.payroll-cutoff.index'),
        icon: CalendarRange,
    });
}

if (canManageDevice) {
    adminChildren.push({
        title: 'Device Management',
        href: route('devices.index'),
        icon: LucideScanQrCode,
    });
}

if (canManageEmployees) {
    adminChildren.push({
        title: 'Employee Management',
        href: route('employees.index'),
        icon: Users,
    });
}

if (canManageHoliday) {
    adminChildren.push({
        title: 'Holiday Management',
        href: route('holidays.index'),
        icon: CalendarHeart,
    });
}

if (canManageLeave) {
    adminChildren.push({
        title: 'Leave Management',
        href: route('admin.leave.balances.index'),
        icon: TimerOff,
    });
}

if (canManageMedical) {
    adminChildren.push({
        title: 'MRA Management',
        href: route('medical.approval.index'),
        icon: HandCoins,
    });
}

if (canManagePicklist) {
    adminChildren.push({
        title: 'Picklist Management',
        href: route('picklists.index'),
        icon: ListPlusIcon,
    });
}

if (canManageRoles) {
    adminChildren.push({
        title: 'Role Management',
        href: route('users.roles'),
        icon: Users,
    });
}

if (canManageShiftcode) {
    adminChildren.push({
        title: 'Shiftcode Management',
        href: route('admin.shiftcode'),
        icon: CalendarRange,
    });
}

if (canManageTeam) {
    adminChildren.push({
        title: 'Team Management',
        href: route('my-team.index'),
        icon: Users,
    });
}

if (adminChildren.length > 0) {
    mainNavItems.push({
        title: 'Admin Setting',
        icon: Settings,
        children: adminChildren,
    });
}

/**
 * FOOTER
 */
const footerNavItems: NavItem[] = [
    { title: 'Github Repo', href: 'https://github.com/laravel/vue-starter-kit', icon: Folder },
    { title: 'Documentation', href: 'https://laravel.com/docs/starter-kits#vue', icon: BookOpen },
];
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <Link :href="route('dashboard')">
              <AppLogo />
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
      <NavMain :items="mainNavItems" />
    </SidebarContent>

    <SidebarFooter>
      <NavFooter :items="footerNavItems" />
      <NavUser />
    </SidebarFooter>
  </Sidebar>

  <slot />
</template>
