import { h } from 'vue'
//import { ColumnDef } from "@tanstack/react-table"
import { ColumnDef } from "@tanstack/vue-table"
import DropdownAction from '@/components/timekeeping/data-table-dropdown.vue'
import { ArrowUpDown } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

// -------------------- TYPES --------------------
export type Overtime = {
  id: number
  hours: number
  status: 'Pending' | 'Approved' | 'Rejected' | 'Deleted' | 'Adjusted'
}

export type ShiftCode = {
  id: number
  shiftCode: string
}

export type TimekeepingCorrection = {
  id: number
  timekeeping_id: number | null
  time_in: string
  time_out: string
  reason_id: number | null
  shiftcode_id: number | null
  status: 'Pending' | 'Approved' | 'Rejected' | 'Superseded' | 'Adjusted'
  reason?: Reason
  other_reason?: string
  shiftCodeRelation?: ShiftCode
}

export type Reason = {
  id: number
  reasonName: string
}

export type PayrollAdjustment = {
  id: number
  shiftcode_id: number
  time_in: string
  time_out: string
  shiftcode?: ShiftCode
  adjusted_hours?: string
  reason_id?: string
  reason?: Reason
  other_reason?: string
  created_at?: string 
}

export type Timekeeping = {
  id: string
  dated: string
  payrollDate?: string | null
  dayType: string
  shiftCode: string
  shiftcode_id: number | null
  corrected_shiftcode_id?: number | null
  timeIn: string
  timeOut: string
  correctedShiftCode: string
  correctedTimeIn: string
  correctedTimeOut: string
  typeCode: string
  hoursWorked: number
  overtime: number
  reason: string
  otherReason?: string

  corrections: TimekeepingCorrection | null
  adjustment?: PayrollAdjustment | null
  overtimes?: Overtime[]
}

// -------------------- HELPERS --------------------
const formatDateTime = (value?: string | null) => {
  if (!value) return '-'
  return value.replace('T', ' ').replace('Z', '').slice(0, 19)
}

const formatDate = (value?: string | null) => {
  if (!value) return '-'
  return new Date(value).toLocaleDateString('en-CA', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  })
}

const statusColor = (status: string) => {
  switch (status) {
    case 'Pending': return 'text-yellow-600'
    case 'Approved': return 'text-green-600'
    case 'Rejected': return 'text-red-600'
    case 'Adjusted': return 'text-blue-600'
    default: return 'text-gray-500'
  }
}

const resolveReasonDisplay = (reasonName?: string | null, otherReason?: string | null) => {
  if (!reasonName) return '-'
  if (reasonName === 'Others') return otherReason ? `Others - ${otherReason}` : 'Others'
  return reasonName
}

const formatTime = (value?: string | null) => {
  if (!value) return '-'

  // Case 1: "2026-02-01 07:01:00"
  if (value.includes(' ')) {
    return value.split(' ')[1]?.slice(0, 8) ?? '-'
  }

  // Case 2: ISO "2026-02-01T07:01:00"
  if (value.includes('T')) {
    return value.split('T')[1]?.slice(0, 8) ?? '-'
  }

  // Case 3: already "07:01:00"
  if (value.length >= 8) {
    return value.slice(0, 8)
  }

  return value
}

// -------------------- COLUMNS FACTORY --------------------
export const columns = (currentPayrollDate: string | null): ColumnDef<Timekeeping, any>[] => [
  {
    accessorKey: 'dated',
    header: ({ column }) => {
      return h(Button, {
        variant: 'ghost',
        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
      }, () => ['Date', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
    },
    cell: ({ row }) => h('div', { class: 'uppercase' }, formatDate(row.getValue('dated'))),
  },
  {
    accessorKey: 'dated',
    header: 'Day',
    cell: ({ row }) => {
      const dateStr = row.getValue('dated') as string
      const dayName = new Date(dateStr).toLocaleDateString('en-US', { weekday: 'short' })
      return h('div', { class: 'capitalize' }, dayName)
    },
  },
  { accessorKey: 'dayType', header: 'Day Type' },
  { accessorKey: 'shiftCode', header: 'Shift' },
  {
  accessorKey: 'timeIn',
  header: 'TimeIn',
  cell: ({ row }) => h('div', formatTime(row.getValue('timeIn'))),
},
{
  accessorKey: 'timeOut',
  header: 'TimeOut',
  cell: ({ row }) => h('div', formatTime(row.getValue('timeOut'))),
},
  { accessorKey: 'hoursWorked', header: 'Hours Worked' },
  { accessorKey: 'typeCode', header: 'Type' },
  {
    accessorKey: 'correctedShiftCode',
    header: 'Corrected Shift',
    cell: ({ row }) => {
      const c = row.original.corrections
      const a = row.original.adjustment
      if (c?.status === 'Adjusted' && a) {
        return h('div', { class: 'text-blue-600 font-medium' }, a.shiftcode?.shiftCode ?? '-')
      }
      if (!c) return h('div', '-')
      return h('div', { class: `${statusColor(c.status)} font-medium` }, c.shiftCodeRelation?.shiftCode ?? '-')
    },
  },
  {
    accessorKey: 'correctedTimeIn',
    header: 'Corrected Time In',
    cell: ({ row }) => {
      const c = row.original.corrections
      const a = row.original.adjustment
      if (c?.status === 'Adjusted' && a) return h('div', { class: 'text-blue-600 font-medium' }, formatDateTime(a.time_in))
      if (!c?.time_in) return h('div', '-')
      return h('div', { class: `${statusColor(c.status)} font-medium` }, formatDateTime(c.time_in))
    },
  },
  {
    accessorKey: 'correctedTimeOut',
    header: 'Corrected Time Out',
    cell: ({ row }) => {
      const c = row.original.corrections
      const a = row.original.adjustment
      if (c?.status === 'Adjusted' && a) return h('div', { class: 'text-blue-600 font-medium' }, formatDateTime(a.time_out))
      if (!c?.time_out) return h('div', '-')
      return h('div', { class: `${statusColor(c.status)} font-medium` }, formatDateTime(c.time_out))
    },
  },
  {
    id: 'reason',
    header: 'Reason for Correction',
    cell: ({ row }) => {
      const c = row.original.corrections
      const a = row.original.adjustment
      if (c?.status === 'Adjusted' && a) return h('div', { class: 'text-blue-600 font-medium' }, resolveReasonDisplay(a.reason?.reasonName, c.other_reason))
      if (!c) return h('div', '-')
      return h('div', { class: `${statusColor(c.status)} font-medium` }, resolveReasonDisplay(c.reason?.reasonName, c.other_reason))
    },
  },
  {
    id: 'approvedOvertime',
    header: 'OT Hours',
    cell: ({ row }) => {
      const overtimes = row.original.overtimes ?? []
      const approvedHours = overtimes.filter(o => o.status === 'Approved').reduce((sum, o) => sum + Number(o.hours), 0)
      return h('div', { class: approvedHours > 0 ? 'text-green-600 font-semibold' : 'text-gray-400' }, approvedHours > 0 ? approvedHours.toFixed(2) : '-')
    },
  },
  {
    id: 'actions',
    enableHiding: false,
    cell: ({ row }) => {
      const timekeeping = row.original
      const adjustment = timekeeping.adjustment
        ? {
            ...timekeeping.adjustment,
            shiftcode_id: String(timekeeping.adjustment.shiftcode_id ?? ''),
            adjusted_hours: timekeeping.adjustment.adjusted_hours ?? '0',
            reason_id: timekeeping.adjustment.reason_id ?? '',
            created_at: timekeeping.adjustment.created_at ?? new Date().toISOString(),
          }
        : undefined

      return h(DropdownAction, {
        timekeeping,
        adjustment,
        currentPayrollDate, // ✅ correctly passed from factory
      })
    },
  },
]
