import { h } from 'vue'
import { ColumnDef } from "@tanstack/react-table"
import DropdownAction from '@/components/medical/data-table-dropdown.vue'
import { ArrowUpDown, ChevronDown } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

export type Medical = {
    id: string
    reqid: string
    empnum: number
    empname: string
    receiptNumber: string
    amount: number
    transactionDate: Date
    employeeNotes: Text
    status: number
    status_name: string
}

export const columns: ColumnDef<Medical>[] = [
    {
        accessorKey: 'reqid',
        header: ({ column }) => {
            return h(Button, {
                variant: 'ghost',
                onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
            }, () => ['REQUEST ID', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
        },
        cell: ({ row }) => h('div', { class: 'uppercase' }, row.getValue('reqid')),
    },
    {
        accessorKey: "empnum",
        header: "Employee Number",
    },
    {
        accessorKey: "empname",
        header: "Employee Name",
    },
    {
        accessorKey: "receiptNumber",
        header: "Official Receipt Number",
    },
    {
        accessorKey: 'amount',
        header: () => h('div', { class: 'text-right' }, 'Amount'),
        cell: ({ row }) => {
            const amount = Number.parseFloat(row.getValue('amount'))
            const formatted = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'PHP',
            }).format(amount)

            return h('div', { class: 'text-right font-medium' }, formatted)
        },
    },
    {
        accessorKey: "transactionDate",
        header: "Transaction Date",
    },
    {
        accessorKey: "employeeNotes",
        header: "Employee Notes",
    },
    {
        accessorKey: "status_name",
        header: "Status",
    },
    {
    id: 'actions',
    enableHiding: false,
    cell: ({ row }) => {
      const medical = row.original

      return h('div', { class: 'relative' }, h(DropdownAction, {
        medical,
      }))
    },
  },
]
