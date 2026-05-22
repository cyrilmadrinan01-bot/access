<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, watch, computed } from 'vue'
import { type BreadcrumbItem } from '@/types'
import { route } from 'ziggy-js'


const props = defineProps<{
  cutoff: {
    id: number
    cutOffStart: string
    cutOffEnd: string
    payrollDate: string
  } | null

  employees: {
    empnum: string
    totals: Record<string, number>
  }[]

  stepsStatus: {
    timekeeping: boolean
    other_income: boolean
    deduction: boolean
    medical: boolean
    sss: boolean
    pagibig: boolean
    philhealth: boolean
    payroll: boolean
    bank: boolean
    payslip: boolean
  }

  cutoffs: {
    id: number
    cutOffStart: string
    cutOffEnd: string
    payrollDate: string
    current: string
    lockDate: string
    lockTime: string
    hasRunTimekeeping?: boolean
  }[]

  selectedCutoffId?: number | null

  timekeepingRows?: {
    empnum: string
    reg: number
    nsd_reg: number
    overtime_reg: number
    overtime_nsd_reg: number
    overtime_lh: number
    overtime_lh_8: number
    overtime_lh_12: number
    overtime_nsd_lh: number
    overtime_sh: number
    overtime_sh_8: number
    overtime_sh_12: number
    overtime_nsd_sh: number
    overtime_lhrd: number
    overtime_lhrd_8: number
    overtime_lhrd_12: number
    overtime_nsd_lhrd: number
    overtime_shrd: number
    overtime_shrd_8: number
    overtime_shrd_12: number
    overtime_nsd_shrd: number
    overtime_rd: number
    overtime_rd_8: number
    overtime_rd_12: number
    overtime_nsd_rd: number
    late_reg: number
    undertime: number
    absent: number
    adjusted_hours: number
    adjusted_nsd: number
    adjusted_ot_hours: number
    adjusted_ot_nsd: number
  }[]

  otherIncomeRows?: {
    id: number
    empnum: string
    empname: string
    income_type: string
    amount: number
    is_taxable: boolean
  }[]

  otherIncomeUploadedAt?: string | null
  // ✅ ADD
  employeeDeductionRows?: {
    id: number
    empnum: string
    empname: string
    deduction_type: string
    amount: number
  }[]
  employeeDeductionUploadedAt: string | null

  medicalRows?: {
    id: number
    empnum: string
    empname: string
    total_amount: number
    processed_at: string | null
  }[]

  sssRows?: {
    id: number
    empnum: string
    empname: string
    sss_number: string
    year: string
    month: string
    employee: string
    employer: string
    ec: string
    processed_at: string | null
  }[]

  pagibigRows?: {
    id: number
    empnum: string
    empname: string
    pagibig_number: string
    year: string
    month: string
    employee: string
    employer: string
    processed_at: string | null
  }[]

  philhealthRows?: {
    id: number
    empnum: string
    empname: string
    philhealth_number: string
    year: string
    month: string
    employee: string
    employer: string
    processed_at: string | null
  }[]

  payrollRows?: {
    id: number
    empnum: string
    empname: string
    accountNumber: string
    payrollType: string
    factor: number
    annual_salary: number
    monthly_rate: number
    hourly_rate: number
    basic_pay: number
    late: number
    absent: number
    nsdReg: number
    ot: number
    nsdOt: number
    rdReg: number
    rdOt: number
    nsdRdReg: number
    nsdRdOt: number
    lhReg: number
    lhOt: number
    nsdLh: number
    nsdLhOt: number
    shReg: number
    shOt: number
    nsdSh: number
    nsdShOt: number
    lhrdReg: number
    lhrdOt: number
    nsdLhRd: number
    nsdLhRdOt: number
    shrdReg: number
    shrdOt: number
    nsdShRd: number
    nsdShRdOt: number
    dtrAdjustment: number
    otAdjustment: number
    mealAllowanceAdj: number
    uniformClothingAllowance: number
    transpoAllowance: number
    laundryAllowance: number
    busMarshallAllowance: number
    monthlyHomeSubsidy: number
    gasAllowance: number
    medicalAssistance: number
    medicalCashAllowance: number
    retentionBonus: number
    mealOt: number
    gross: number
    employeeTax: number
    sssEmployee: number
    sssMpdfEe: number
    sssEmployer: number
    sssMpfEr: number
    sssEc: number
    philhealthEe: number
    philhealthEr: number
    pagibigEe: number
    pagibigEr: number
    employeeSavings: number
    HdmfLoanAdj: number
    coopLoan: number
    sssSalaryLoanAdj: number
    taxAdjustment: number
    totalDeduction: number
    net: number
    atm: number
    riceAllowance: number
    undertime: number
  }[]
}>()

const {
  timekeepingRows,
  otherIncomeRows,
  employeeDeductionRows,
  medicalRows,
  sssRows,
  pagibigRows,
  philhealthRows,
  payrollRows,
  cutoffs,
  stepsStatus,
} = props

const hasTimekeepingData = computed(() => {
  return (props.timekeepingRows?.length ?? 0) > 0
})

const hasOtherIncomeData = computed(() => {
  return (props.otherIncomeRows?.length ?? 0) > 0
})

const hasDeductionData = computed(() => {
  return (props.employeeDeductionRows?.length ?? 0) > 0
})

const hasMedicalData = computed(() => {
  return (props.medicalRows?.length ?? 0) > 0
})

const hasSSSData = computed(() => {
  return (props.sssRows?.length ?? 0) > 0
})

const hasPagibigData = computed(() => {
  return (props.pagibigRows?.length ?? 0) > 0
})

const hasPhilHealthData = computed(() => {
  return (props.philhealthRows?.length ?? 0) > 0
})

const deductionFile = ref<File | null>(null)

function uploadDeduction() {
  if (!selectedCutoffId.value) {
    alert('Please select cutoff first.')
    return
  }

  if (!deductionFile.value) {
    alert('Please select an Excel file.')
    return
  }

  const formData = new FormData()
  formData.append('cutoff_id', selectedCutoffId.value.toString())
  formData.append('file', deductionFile.value)

  router.post(
    route('admin.payroll.employee-deduction.upload'),
    formData,
    {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        activeStep.value = 2
        router.reload({ only: ['employeeDeductionRows', 'stepsStatus', 'employeeDeductionUploadedAt'] })
      }
    }
  )
}

const selectedFile = ref<File | null>(null)
const isTaxable = ref<number>(1)

function handleFileUpload(event: Event) {
  const input = event.target as HTMLInputElement
  if (input.files && input.files.length > 0) {
    selectedFile.value = input.files[0]
  }
}

function computeMonthlyRate(annual: number) {
  return annual / 12
}

function computeHourlyRate(annual: number, factor: number) {
  return annual / factor
}

function computeLateDeduction(annual: number, factor: number, lateHours: number) {
  const hourly = annual / factor
  return hourly * lateHours
}

function processPayrollRegister() {
  if (!selectedCutoffId.value) {
    alert('Please select cutoff first.')
    return
  }

  router.post(
    route('admin.payroll.register.process', selectedCutoffId.value),
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        goToNextStep('payroll')
        router.reload({ only: ['payrollRows', 'stepsStatus'] })
      }
    }
  )
}


function handleFileUploadDeduct(event: Event) {
  const input = event.target as HTMLInputElement
  if (input.files && input.files.length > 0) {
    deductionFile.value = input.files[0]  // ✅ CORRECT
  }
}

function uploadOtherIncome() {
  if (!selectedCutoffId.value) {
    alert('Please select cutoff first.')
    return
  }

  if (!selectedFile.value) {
    alert('Please select an Excel file.')
    return
  }

  const formData = new FormData()
  formData.append('file', selectedFile.value)
  formData.append('cutoff_id', selectedCutoffId.value.toString())
  formData.append('is_taxable', isTaxable.value.toString())

  router.post(
    route('other-income.upload'),
    formData,
    {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        activeStep.value = 2
        router.reload({ only: ['stepsStatus', 'otherIncomeUploadedAt'] })
      }
    }
  )
}

function processMedical() {
  if (!selectedCutoffId.value) {
    alert('Please select cutoff first.')
    return
  }

  router.post(
    route('admin.payroll.medical.process', selectedCutoffId.value),
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        goToNextStep('medical')
        router.reload({ only: ['medicalRows', 'stepsStatus'] })
      }
    }
  )
}


const sssYear = ref(new Date().getFullYear())
const sssMonth = ref(new Date().getMonth() + 1)

function processSSS() {
  if (!selectedCutoffId.value) {
    alert('Please select cutoff first.')
    return
  }

  router.post(
    route('admin.payroll.sss.process', selectedCutoffId.value),
    { year: sssYear.value, month: sssMonth.value },
    {
      preserveScroll: true,
      onSuccess: () => {
        goToNextStep('sss')
        router.reload({ only: ['sssRows', 'stepsStatus'] })
      }
    }
  )
}


function processPagibig() {
  if (!selectedCutoffId.value) {
    alert('Please select cutoff first.')
    return
  }

  router.post(
    route('admin.payroll.pagibig.process', selectedCutoffId.value),
    { year: sssYear.value, month: sssMonth.value },
    {
      preserveScroll: true,
      onSuccess: () => {
        goToNextStep('pagibig')
        router.reload({ only: ['pagibigRows', 'stepsStatus'] })
      }
    }
  )
}

function processPhilHealth() {
  if (!selectedCutoffId.value) {
    alert('Please select cutoff first.')
    return
  }

  router.post(
    route('admin.payroll.philhealth.process', selectedCutoffId.value),
    { year: sssYear.value, month: sssMonth.value },
    {
      preserveScroll: true,
      onSuccess: () => {
        goToNextStep('philhealth')
        router.reload({ only: ['philhealthRows', 'stepsStatus'] })
      }
    }
  )
}

function skipStep(stepKey: StepKey) {
  if (!selectedCutoffId.value) return

  let routeName = ''

  if (stepKey === 'other_income') routeName = 'other-income.skip'
  if (stepKey === 'deduction') routeName = 'admin.payroll.employee-deduction.skip'
  if (stepKey === 'medical') routeName = 'admin.payroll.medical.skip'
  if (stepKey === 'sss') routeName = 'admin.payroll.sss.skip'
  if (stepKey === 'pagibig') routeName = 'admin.payroll.pagibig.skip'
  if (stepKey === 'philhealth') routeName = 'admin.payroll.philhealth.skip'

  if (!routeName) return

  router.post(
    route(routeName, { cutoff: selectedCutoffId.value }),
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        goToNextStep(stepKey)
        router.reload({ only: ['stepsStatus'] })
      }
    }
  )
}


type StepKey = 'timekeeping' | 'other_income' | 'deduction' | 'medical' | 'sss' | 'pagibig' | 'philhealth' | 'payroll' | 'bank' | 'payslip'

const steps: { key: StepKey; label: string }[] = [
  { key: 'timekeeping', label: 'Process Timekeeping' },
  { key: 'other_income', label: 'Upload Other Income' },
  { key: 'deduction', label: 'Upload Employee Deductions' },
  { key: 'medical', label: 'Process Medical Re-imbursement' },
  { key: 'sss', label: 'Generate SSS Contribution' },
  { key: 'pagibig', label: 'Generate Pag-Ibig Contribution' },
  { key: 'philhealth', label: 'Generate PhilHealth Contribution' },
  { key: 'payroll', label: 'Process Payroll Register' },
  { key: 'bank', label: 'Generate Bank File' },
  { key: 'payslip', label: 'Generate Payslip' },

]

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Payroll', href: '#' },
  { title: 'Payroll Process', href: '#' },
]

const activeStep = ref(0)

function canAccessStep(index: number) {
  if (index === 0) return true

  const prevStep = steps[index - 1]
  return stepsStatus[prevStep.key]
}

function goToNextStep(currentKey: StepKey) {
  const index = steps.findIndex(s => s.key === currentKey)
  if (index !== -1 && index < steps.length - 1) {
    activeStep.value = index + 1
  }
}

// Selected cutoff
const selectedCutoffId = ref<number | null>(props.selectedCutoffId ?? null)

watch(selectedCutoffId, (id, oldId) => {
  if (!id || id === oldId) return

  router.get(
    route('admin.payroll-cutoff.timekeeping', { cutoff: id }),
    {},
    {
      preserveScroll: true,
      replace: true,
    }
  )
})

// Run payroll timekeeping process
function runProcessTimekeeping() {
  if (!selectedCutoffId.value) {
    alert('Please select a payroll cutoff.')
    return
  }

  router.post(
    route('timekeeping.process.run', { cutoff: selectedCutoffId.value }),
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        goToNextStep('timekeeping')
        router.reload({ only: ['timekeepingRows', 'stepsStatus'] })
      }
    }
  )
}

function processTimekeeping(id: number) {
  router.get(
    route('admin.payroll-cutoff.timekeeping', id),
    {},
    { preserveScroll: true }
  )
}

function formatISOToDate(dateTime: string | null | undefined) {
  if (!dateTime) return '—'
  const clean = dateTime.split('.')[0].replace('T', ' ')
  const [date, time] = clean.split(' ')
  if (!date || !time) return dateTime
  const [year, month, day] = date.split('-')
  return `${month}-${day}-${year}`
}

function resolveActiveStep() {
  for (let i = 0; i < steps.length; i++) {
    if (!stepsStatus[steps[i].key]) {
      return i
    }
  }
  return 0
}

activeStep.value = resolveActiveStep()
</script>

<template>
  <Head title="Payroll Process" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4 flex flex-col gap-4">
      <h1 class="text-2xl font-bold mb-4">Payroll Process</h1>

      <!-- Stepper Tabs -->
      <div class="flex border-b border-gray-200 dark:border-gray-700 mb-4">
        <button
          v-for="(step, index) in steps"
          :key="step.key"
          @click="canAccessStep(index) && (activeStep = index)"
          :disabled="!canAccessStep(index)"
          :class="[
            'px-4 py-2 border-b-2 flex items-center gap-2 transition',
            activeStep === index
              ? 'border-blue-600 text-blue-600 font-bold'
              : 'border-transparent',
            !canAccessStep(index)
              ? 'text-gray-400 cursor-not-allowed'
              : 'text-gray-600 dark:text-gray-300',
          ]"
        >
          <span>{{ step.label }}</span>

          <!-- Completed Check -->
          <span v-if="stepsStatus[step.key]" class="text-green-600 text-sm"> ✔ </span>
        </button>
      </div>

      <!-- Step Content -->
      <div>
        <!-- Step 1: Process Timekeeping -->
        <div v-if="activeStep === 0">
          <!-- Payroll Cutoff Selection -->
          <div class="flex items-end gap-4 mb-4">
            <div class="flex flex-col">
              <select
                v-model="selectedCutoffId"
                class="border rounded px-3 py-2 dark:bg-gray-800 dark:border-gray-700"
              >
                <option :value="null">Select Cutoff</option>
                <option v-for="c in cutoffs" :key="c.id" :value="c.id">
                  {{ formatISOToDate(c.cutOffStart) }} –
                  {{ formatISOToDate(c.cutOffEnd) }} (Pay-out:
                  {{ formatISOToDate(c.payrollDate) }})
                </option>
              </select>
            </div>

            <button
              class="px-4 py-2 text-white rounded"
              :class="[
                stepsStatus.timekeeping
                  ? 'bg-yellow-600 hover:bg-yellow-700'
                  : 'bg-blue-600 hover:bg-blue-700',
              ]"
              @click="runProcessTimekeeping"
            >
              {{
                hasTimekeepingData
                  ? "Re-run Process Timekeeping"
                  : "Run Process Timekeeping"
              }}
            </button>
          </div>

          <div v-if="timekeepingRows?.length" class="mt-6">
            <h2 class="text-xl font-semibold mb-2">Timekeeping Summary</h2>

            <div class="overflow-x-auto rounded-xl border">
              <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100 dark:bg-gray-800">
                  <tr>
                    <th class="px-3 py-2 border">Emp #</th>
                    <th class="px-3 py-2 border">Reg</th>
                    <th class="px-3 py-2 border">NSD</th>
                    <th class="px-3 py-2 border">OT</th>
                    <th class="px-3 py-2 border">OT NSD</th>
                    <th class="px-3 py-2 border">OT LH</th>
                    <th class="px-3 py-2 border">OT LH 8H</th>
                    <th class="px-3 py-2 border">OT LH 12H</th>
                    <th class="px-3 py-2 border">OT LHRD</th>
                    <th class="px-3 py-2 border">OT LHRD 8H</th>
                    <th class="px-3 py-2 border">OT LHRD 12H</th>
                    <th class="px-3 py-2 border">OT SH</th>
                    <th class="px-3 py-2 border">OT SH 8H</th>
                    <th class="px-3 py-2 border">OT SH 12H</th>
                    <th class="px-3 py-2 border">OT SHRD</th>
                    <th class="px-3 py-2 border">OT SHRD 8H</th>
                    <th class="px-3 py-2 border">OT SHRD 12H</th>
                    <th class="px-3 py-2 border">OT RD</th>
                    <th class="px-3 py-2 border">OT RD 8H</th>
                    <th class="px-3 py-2 border">OT RD 12H</th>
                    <th class="px-3 py-2 border">Late</th>
                    <th class="px-3 py-2 border">UT</th>
                    <th class="px-3 py-2 border">Absent</th>
                    <th class="px-3 py-2 border">Adjustment</th>
                    <th class="px-3 py-2 border">Adjustment NSD</th>
                    <th class="px-3 py-2 border">Adjustment OT</th>
                    <th class="px-3 py-2 border">Adjustment OT NSD</th>
                  </tr>
                </thead>

                <tbody>
                  <tr
                    v-for="row in timekeepingRows"
                    :key="row.empnum"
                    class="hover:bg-gray-50 dark:hover:bg-gray-700"
                  >
                    <td class="px-3 py-2 border">{{ row.empnum }}</td>
                    <td class="px-3 py-2 border">{{ row.reg }}</td>
                    <td class="px-3 py-2 border">{{ row.nsd_reg }}</td>
                    <td class="px-3 py-2 border">{{ row.overtime_reg }}</td>
                    <td class="px-3 py-2 border">{{ row.overtime_nsd_reg }}</td>
                    <td class="px-3 py-2 border">{{ row.overtime_lh }}</td>
                    <td class="px-3 py-2 border">{{ row.overtime_lh_8 }}</td>
                    <td class="px-3 py-2 border">{{ row.overtime_lh_12 }}</td>
                    <td class="px-3 py-2 border">{{ row.overtime_lhrd }}</td>
                    <td class="px-3 py-2 border">{{ row.overtime_lhrd_8 }}</td>
                    <td class="px-3 py-2 border">{{ row.overtime_lhrd_12 }}</td>
                    <td class="px-3 py-2 border">{{ row.overtime_sh }}</td>
                    <td class="px-3 py-2 border">{{ row.overtime_sh_8 }}</td>
                    <td class="px-3 py-2 border">{{ row.overtime_sh_12 }}</td>
                    <td class="px-3 py-2 border">{{ row.overtime_shrd }}</td>
                    <td class="px-3 py-2 border">{{ row.overtime_shrd_8 }}</td>
                    <td class="px-3 py-2 border">{{ row.overtime_shrd_12 }}</td>
                    <td class="px-3 py-2 border">{{ row.overtime_rd }}</td>
                    <td class="px-3 py-2 border">{{ row.overtime_rd_8 }}</td>
                    <td class="px-3 py-2 border">{{ row.overtime_rd_12 }}</td>
                    <td class="px-3 py-2 border">{{ row.late_reg }}</td>
                    <td class="px-3 py-2 border">{{ row.undertime }}</td>
                    <td class="px-3 py-2 border">{{ row.absent }}</td>
                    <td class="px-3 py-2 border">{{ row.adjusted_hours }}</td>
                    <td class="px-3 py-2 border">{{ row.adjusted_nsd }}</td>
                    <td class="px-3 py-2 border">{{ row.adjusted_ot_hours }}</td>
                    <td class="px-3 py-2 border">{{ row.adjusted_ot_nsd }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Step 2: Upload Other Income -->
        <div v-if="activeStep === 1" class="space-y-6">
          <!-- Upload Form -->
          <div class="space-y-4">
            <div class="flex flex-col gap-2 max-w-md">
              <label class="font-semibold">Tax Type</label>
              <select
                v-model="isTaxable"
                class="border rounded px-3 py-2 dark:bg-gray-800 dark:border-gray-700"
              >
                <option :value="1">Taxable</option>
                <option :value="0">Non-Taxable</option>
              </select>
            </div>

            <div class="flex flex-col gap-2 max-w-md">
              <label class="font-semibold">Upload Excel File</label>
              <input
                type="file"
                accept=".xlsx,.xls"
                @change="handleFileUpload"
                class="border rounded px-3 py-2 dark:bg-gray-800 dark:border-gray-700"
              />
            </div>

            <div class="flex gap-4 items-center">
              <button
                @click="uploadOtherIncome"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
              >
                {{ hasOtherIncomeData ? "Re-upload & Save" : "Upload & Save" }}
              </button>

              <button
                @click="skipStep('other_income')"
                class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
              >
                Skip
              </button>

              <div v-if="otherIncomeUploadedAt" class="text-green-600 font-semibold">
                Last uploaded: {{ formatISOToDate(otherIncomeUploadedAt) }}
              </div>
            </div>
          </div>

          <!-- Other Income Table -->
          <div v-if="otherIncomeRows?.length" class="mt-6">
            <h2 class="text-xl font-semibold mb-2">Other Income Summary</h2>
            <div class="overflow-x-auto rounded-xl border">
              <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100 dark:bg-gray-800">
                  <tr>
                    <th class="px-3 py-2 border">Emp #</th>
                    <th class="px-3 py-2 border">Employee Name</th>
                    <th class="px-3 py-2 border">Income Type</th>
                    <th class="px-3 py-2 border">Amount</th>
                    <th class="px-3 py-2 border">Taxable</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="row in otherIncomeRows ?? []"
                    :key="row.id"
                    class="hover:bg-gray-50 dark:hover:bg-gray-700"
                  >
                    <td class="px-3 py-2 border">{{ row.empnum }}</td>
                    <td class="px-3 py-2 border">{{ row.empname }}</td>
                    <td class="px-3 py-2 border">{{ row.income_type }}</td>
                    <td class="px-3 py-2 border">{{ row.amount }}</td>
                    <td class="px-3 py-2 border">{{ row.is_taxable ? "Yes" : "No" }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Step 3: Upload Employee Deductions/Loan -->
        <div v-if="activeStep === 2" class="space-y-6">
          <!-- Upload Form -->
          <div class="space-y-4">
            <div class="flex flex-col gap-2 max-w-md">
              <label class="font-semibold">Upload Excel File</label>
              <input
                type="file"
                accept=".xlsx,.xls"
                @change="handleFileUploadDeduct"
                class="border rounded px-3 py-2 dark:bg-gray-800 dark:border-gray-700"
              />
            </div>

            <div class="flex gap-4 items-center">
              <button
                @click="uploadDeduction"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
              >
                {{ hasDeductionData ? "Re-upload & Save" : "Upload & Save" }}
              </button>

              <button
                @click="skipStep('deduction')"
                class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
              >
                Skip
              </button>

              <div
                v-if="employeeDeductionUploadedAt"
                class="text-green-600 font-semibold"
              >
                Last uploaded: {{ formatISOToDate(employeeDeductionUploadedAt) }}
              </div>
            </div>
          </div>

          <!-- Other Income Table -->
          <div v-if="employeeDeductionRows?.length" class="mt-6">
            <h2 class="text-xl font-semibold mb-2">Employee Deductions</h2>
            <div class="overflow-x-auto rounded-xl border">
              <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100 dark:bg-gray-800">
                  <tr>
                    <th class="px-3 py-2 border">Emp #</th>
                    <th class="px-3 py-2 border">Employee Name</th>
                    <th class="px-3 py-2 border">Deduction Type</th>
                    <th class="px-3 py-2 border">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="row in employeeDeductionRows ?? []"
                    :key="row.id"
                    class="hover:bg-gray-50 dark:hover:bg-gray-700"
                  >
                    <td class="px-3 py-2 border">{{ row.empnum }}</td>
                    <td class="px-3 py-2 border">{{ row.empname }}</td>
                    <td class="px-3 py-2 border">{{ row.deduction_type }}</td>
                    <td class="px-3 py-2 border">{{ row.amount }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Step 4: Process Medical Re-imbursement -->
        <div v-if="activeStep === 3" class="space-y-6">
          <div v-if="!selectedCutoffId" class="text-red-500 font-semibold">
            Please select and process a payroll cutoff in Step 1 first.
          </div>

          <div v-else>
            <div class="flex justify-between items-center">
              <h2 class="text-xl font-semibold">Approved Medical Reimbursements</h2>

              <div class="flex gap-3">
                <button
                  @click="processMedical"
                  class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                >
                  Run Medical Retrieval
                </button>

                <button
                  @click="skipStep('medical')"
                  class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition"
                >
                  Skip
                </button>
              </div>
            </div>

            <div
              v-if="medicalRows?.length"
              class="mt-4 overflow-x-auto rounded-xl border"
            >
              <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100 dark:bg-gray-800">
                  <tr>
                    <th class="px-3 py-2 border">Emp #</th>
                    <th class="px-3 py-2 border">Employee Name</th>
                    <th class="px-3 py-2 border">Total Amount</th>
                    <th class="px-3 py-2 border">Processed At</th>
                  </tr>
                </thead>

                <tbody>
                  <tr
                    v-for="row in medicalRows ?? []"
                    :key="row.id"
                    class="hover:bg-gray-50 dark:hover:bg-gray-700"
                  >
                    <td class="px-3 py-2 border">{{ row.empnum }}</td>
                    <td class="px-3 py-2 border">{{ row.empname }}</td>
                    <td class="px-3 py-2 border">{{ row.total_amount }}</td>
                    <td class="px-3 py-2 border">
                      {{ formatISOToDate(row.processed_at) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-else class="text-gray-500 mt-4">
              No approved medical reimbursements for this payroll cutoff.
            </div>
          </div>
        </div>

        <!-- Step 5: Generate SSS Contribution -->
        <div v-if="activeStep === 4" class="space-y-6">
          <!-- Controls -->
          <div class="flex gap-4 items-end flex-wrap">
            <div class="flex flex-col">
              <label class="font-semibold text-gray-700 dark:text-gray-200"> Year </label>
              <input
                v-model="sssYear"
                type="number"
                class="border rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600"
              />
            </div>

            <div class="flex flex-col">
              <label class="font-semibold text-gray-700 dark:text-gray-200 mb-1">
                Month
              </label>

              <select
                v-model="sssMonth"
                class="border rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition duration-150"
              >
                <option value="1">January</option>
                <option value="2">February</option>
                <option value="3">March</option>
                <option value="4">April</option>
                <option value="5">May</option>
                <option value="6">June</option>
                <option value="7">July</option>
                <option value="8">August</option>
                <option value="9">September</option>
                <option value="10">October</option>
                <option value="11">November</option>
                <option value="12">December</option>
              </select>
            </div>

            <button
              @click="processSSS"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
            >
              Generate SSS Contribution
            </button>

            <!-- ✅ Skip Button -->
            <button
              @click="skipStep('sss')"
              class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition"
            >
              Skip
            </button>
          </div>

          <!-- Generated SSS Table -->
          <div v-if="sssRows?.length" class="mt-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-3">
              Generated SSS Contributions
            </h2>
            <div
              class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700"
            >
              <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100 dark:bg-gray-800">
                  <tr>
                    <th class="px-3 py-2 border">Emp #</th>
                    <th class="px-3 py-2 border">Employee Name</th>
                    <th class="px-3 py-2 border">SSS No.</th>
                    <th class="px-3 py-2 border text-right">Employee Share</th>
                    <th class="px-3 py-2 border text-right">Employer Share</th>
                    <th class="px-3 py-2 border text-right">EC</th>
                    <th class="px-3 py-2 border text-right font-semibold">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="row in sssRows"
                    :key="row.id"
                    class="hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                  >
                    <td class="px-3 py-2 border">{{ row.empnum }}</td>
                    <td class="px-3 py-2 border">{{ row.empname }}</td>
                    <td class="px-3 py-2 border">{{ row.sss_number }}</td>
                    <td class="px-3 py-2 border text-right">
                      {{ Number(row.employee).toFixed(2) }}
                    </td>
                    <td class="px-3 py-2 border text-right">
                      {{ Number(row.employer).toFixed(2) }}
                    </td>
                    <td class="px-3 py-2 border text-right">
                      {{ Number(row.ec).toFixed(2) }}
                    </td>
                    <td
                      class="px-3 py-2 border text-right font-semibold text-blue-600 dark:text-blue-400"
                    >
                      {{
                        (
                          Number(row.employee) +
                          Number(row.employer) +
                          Number(row.ec)
                        ).toFixed(2)
                      }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Optional Empty State -->
          <div v-else class="text-gray-500 dark:text-gray-400 mt-4">
            No SSS contributions generated yet.
          </div>
        </div>

        <!-- Step 6: Generate Pag-Ibig Contribution -->
        <div v-if="activeStep === 5" class="space-y-6">
          <div class="flex gap-4 items-end flex-wrap">
            <div class="flex flex-col">
              <label class="font-semibold">Year</label>
              <input
                v-model="sssYear"
                type="number"
                class="border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-600"
              />
            </div>

            <div class="flex flex-col">
              <label class="font-semibold">Month</label>
              <select
                v-model="sssMonth"
                class="border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-600"
              >
                <option value="1">January</option>
                <option value="2">February</option>
                <option value="3">March</option>
                <option value="4">April</option>
                <option value="5">May</option>
                <option value="6">June</option>
                <option value="7">July</option>
                <option value="8">August</option>
                <option value="9">September</option>
                <option value="10">October</option>
                <option value="11">November</option>
                <option value="12">December</option>
              </select>
            </div>

            <button
              @click="processPagibig"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              Generate Pag-Ibig Contribution
            </button>

            <button
              @click="skipStep('pagibig')"
              class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600"
            >
              Skip
            </button>
          </div>

          <div v-if="pagibigRows?.length" class="mt-6">
            <h2 class="text-xl font-semibold mb-3">Generated Pag-Ibig Contributions</h2>

            <div class="overflow-x-auto rounded-xl border">
              <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100 dark:bg-gray-800">
                  <tr>
                    <th class="px-3 py-2 border">Emp #</th>
                    <th class="px-3 py-2 border">Employee Name</th>
                    <th class="px-3 py-2 border">Pag-Ibig No.</th>
                    <th class="px-3 py-2 border text-right">Employee Share</th>
                    <th class="px-3 py-2 border text-right">Employer Share</th>
                    <th class="px-3 py-2 border text-right font-semibold">Total</th>
                  </tr>
                </thead>

                <tbody>
                  <tr
                    v-for="row in pagibigRows"
                    :key="row.id"
                    class="hover:bg-gray-50 dark:hover:bg-gray-700"
                  >
                    <td class="px-3 py-2 border">{{ row.empnum }}</td>
                    <td class="px-3 py-2 border">{{ row.empname }}</td>
                    <td class="px-3 py-2 border">{{ row.pagibig_number }}</td>

                    <td class="px-3 py-2 border text-right">
                      {{ Number(row.employee).toFixed(2) }}
                    </td>

                    <td class="px-3 py-2 border text-right">
                      {{ Number(row.employer).toFixed(2) }}
                    </td>

                    <td class="px-3 py-2 border text-right font-semibold text-blue-600">
                      {{ (Number(row.employee) + Number(row.employer)).toFixed(2) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div v-else class="text-gray-500 mt-4">
            No Pag-Ibig contributions generated yet.
          </div>
        </div>

        <!-- Step 7: Generate PhilHealth Contribution -->
        <div v-if="activeStep === 6" class="space-y-6">
          <div class="flex gap-4 items-end flex-wrap">
            <div class="flex flex-col">
              <label class="font-semibold">Year</label>
              <input
                v-model="sssYear"
                type="number"
                class="border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-600"
              />
            </div>

            <div class="flex flex-col">
              <label class="font-semibold">Month</label>
              <select
                v-model="sssMonth"
                class="border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-600"
              >
                <option value="1">January</option>
                <option value="2">February</option>
                <option value="3">March</option>
                <option value="4">April</option>
                <option value="5">May</option>
                <option value="6">June</option>
                <option value="7">July</option>
                <option value="8">August</option>
                <option value="9">September</option>
                <option value="10">October</option>
                <option value="11">November</option>
                <option value="12">December</option>
              </select>
            </div>

            <button
              @click="processPhilHealth"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              Generate PhilHealth Contribution
            </button>

            <button
              @click="skipStep('pagibig')"
              class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600"
            >
              Skip
            </button>
          </div>

          <div v-if="pagibigRows?.length" class="mt-6">
            <h2 class="text-xl font-semibold mb-3">Generated PhilHealth Contributions</h2>

            <div class="overflow-x-auto rounded-xl border">
              <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100 dark:bg-gray-800">
                  <tr>
                    <th class="px-3 py-2 border">Emp #</th>
                    <th class="px-3 py-2 border">Employee Name</th>
                    <th class="px-3 py-2 border">PhilHealth No.</th>
                    <th class="px-3 py-2 border text-right">Employee Share</th>
                    <th class="px-3 py-2 border text-right">Employer Share</th>
                    <th class="px-3 py-2 border text-right font-semibold">Total</th>
                  </tr>
                </thead>

                <tbody>
                  <tr
                    v-for="row in philhealthRows"
                    :key="row.id"
                    class="hover:bg-gray-50 dark:hover:bg-gray-700"
                  >
                    <td class="px-3 py-2 border">{{ row.empnum }}</td>
                    <td class="px-3 py-2 border">{{ row.empname }}</td>
                    <td class="px-3 py-2 border">{{ row.philhealth_number }}</td>

                    <td class="px-3 py-2 border text-right">
                      {{ Number(row.employee).toFixed(2) }}
                    </td>

                    <td class="px-3 py-2 border text-right">
                      {{ Number(row.employer).toFixed(2) }}
                    </td>

                    <td class="px-3 py-2 border text-right font-semibold text-blue-600">
                      {{ (Number(row.employee) + Number(row.employer)).toFixed(2) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div v-else class="text-gray-500 mt-4">
            No Pag-Ibig contributions generated yet.
          </div>
        </div>

        <!-- Step 8: Process Payroll Register -->
        <div v-if="activeStep === 7" class="space-y-6">
          <div v-if="!selectedCutoffId" class="text-red-500 font-semibold">
            Please select and process timekeeping first.
          </div>

          <div v-else>
            <div class="flex justify-between items-center">
              <h2 class="text-xl font-semibold">Payroll Register</h2>

              <button
                @click="processPayrollRegister"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
              >
                Generate Payroll Register
              </button>
            </div>

            <!-- Payroll Table -->
            <div
              v-if="payrollRows?.length"
              class="mt-6 w-full overflow-auto max-h-[500px] rounded-xl border"
            >
              <table class="min-w-max text-sm border-collapse">
                <!-- HEADER -->
                <thead class="bg-gray-100 dark:bg-gray-800 sticky top-0 z-20">
                  <tr>
                    <th
                      class="px-3 py-2 border sticky left-0 z-30 bg-gray-100 dark:bg-gray-800"
                    >
                      Employee No
                    </th>
                    <th
                      class="px-3 py-2 border sticky left-[100px] z-30 bg-gray-100 dark:bg-gray-800"
                    >
                      Employee Name
                    </th>

                    <th class="px-3 py-2 border">Account Number</th>
                    <th class="px-3 py-2 border">Payroll Type</th>
                    <th class="px-3 py-2 border">Factor</th>
                    <th class="px-3 py-2 border text-right">Annual Salary</th>
                    <th class="px-3 py-2 border text-right">Monthly Rate</th>
                    <th class="px-3 py-2 border text-right">Hourly Rate</th>
                    <th class="px-3 py-2 border text-right">Basic Pay</th>
                    <th class="px-3 py-2 border text-right">Late</th>
                    <th class="px-3 py-2 border text-right">Undertime</th>
                    <th class="px-3 py-2 border text-right">Absent</th>
                    <th class="px-3 py-2 border text-right">OT</th>
                    <th class="px-3 py-2 border text-right">NSD Reg</th>
                    <th class="px-3 py-2 border text-right">NSD OT</th>

                    <th class="px-3 py-2 border text-right">RD Reg</th>
                    <th class="px-3 py-2 border text-right">RD OT</th>
                    <th class="px-3 py-2 border text-right">NSD RD</th>
                    <th class="px-3 py-2 border text-right">NSD RD OT</th>

                    <th class="px-3 py-2 border text-right">LH Reg</th>
                    <th class="px-3 py-2 border text-right">LH OT</th>
                    <th class="px-3 py-2 border text-right">NSD LH</th>
                    <th class="px-3 py-2 border text-right">NSD LH OT</th>

                    <th class="px-3 py-2 border text-right">SH Reg</th>
                    <th class="px-3 py-2 border text-right">SH OT</th>
                    <th class="px-3 py-2 border text-right">NSD SH</th>
                    <th class="px-3 py-2 border text-right">NSD SH OT</th>

                    <th class="px-3 py-2 border text-right">LHRD Reg</th>
                    <th class="px-3 py-2 border text-right">LHRD OT</th>
                    <th class="px-3 py-2 border text-right">NSD LHRD</th>
                    <th class="px-3 py-2 border text-right">NSD LHRD OT</th>

                    <th class="px-3 py-2 border text-right">SHRD Reg</th>
                    <th class="px-3 py-2 border text-right">SHRD OT</th>
                    <th class="px-3 py-2 border text-right">NSD SHRD</th>
                    <th class="px-3 py-2 border text-right">NSD SHRD OT</th>

                    <th class="px-3 py-2 border text-right">DTR Adjustment</th>
                    <th class="px-3 py-2 border text-right">OT Adjustment</th>

                    <th class="px-3 py-2 border text-right">Meal Allowance Adj</th>
                    <th class="px-3 py-2 border text-right">
                      Uniform/Clothing Allowance
                    </th>
                    <th class="px-3 py-2 border text-right">Transpo Allowance</th>
                    <th class="px-3 py-2 border text-right">Laundry Allowance</th>
                    <th class="px-3 py-2 border text-right">Bus Marshall Allowance</th>
                    <th class="px-3 py-2 border text-right">Monthly Home Subsidy</th>
                    <th class="px-3 py-2 border text-right">Gas Allowance</th>
                    <th class="px-3 py-2 border text-right">Medical Assistance</th>
                    <th class="px-3 py-2 border text-right">Medical Cash Allowance</th>
                    <th class="px-3 py-2 border text-right">Retention Bonus</th>
                    <th class="px-3 py-2 border text-right">Meal OT</th>
                    <th class="px-3 py-2 border text-right">Rice Allowance</th>

                    <th class="px-3 py-2 border text-right">Employee Tax</th>
                    <th class="px-3 py-2 border text-right">Employee Savings</th>
                    <th class="px-3 py-2 border text-right">HDMF Loan Adj</th>
                    <th class="px-3 py-2 border text-right">COOP Loan</th>
                    <th class="px-3 py-2 border text-right">SSS Salary Loan Adj</th>
                    <th class="px-3 py-2 border text-right">Tax Adjustment</th>
                    <th class="px-3 py-2 border text-right">Total Deduction</th>

                    <th class="px-3 py-2 border text-right">SSS EE</th>
                    <th class="px-3 py-2 border text-right">SSS MPF EE</th>
                    <th class="px-3 py-2 border text-right">SSS ER</th>
                    <th class="px-3 py-2 border text-right">SSS MPF ER</th>
                    <th class="px-3 py-2 border text-right">SSS EC</th>
                    <th class="px-3 py-2 border text-right">Philhealth EE</th>
                    <th class="px-3 py-2 border text-right">Philhealth ER</th>
                    <th class="px-3 py-2 border text-right">Pag-Ibig EE</th>
                    <th class="px-3 py-2 border text-right">Pag- Ibig ER</th>

                    <th class="px-3 py-2 border text-right">Gross</th>
                    <th class="px-3 py-2 border text-right">Net</th>
                    <th class="px-3 py-2 border text-right">ATM</th>

                    <th class="px-3 py-2 border text-right">Late Deduction</th>
                  </tr>
                </thead>

                <!-- BODY -->
                <tbody>
                  <tr
                    v-for="row in payrollRows"
                    :key="row.id"
                    class="hover:bg-gray-50 dark:hover:bg-gray-700"
                  >
                    <!-- Frozen Column 1 -->
                    <td
                      class="px-3 py-2 border sticky left-0 bg-white dark:bg-gray-900 z-10"
                    >
                      {{ row.empnum }}
                    </td>

                    <!-- Frozen Column 2 -->
                    <td
                      class="px-3 py-2 border sticky left-[100px] bg-white dark:bg-gray-900 z-10"
                    >
                      {{ row.empname }}
                    </td>

                    <td class="px-3 py-2 border">{{ row.accountNumber }}</td>
                    <td class="px-3 py-2 border">{{ row.payrollType }}</td>
                    <td class="px-3 py-2 border">{{ row.factor }}</td>

                    <td class="px-3 py-2 border text-right">
                      {{ Number(row.annual_salary).toFixed(2) }}
                    </td>

                    <td class="px-3 py-2 border text-right">
                      {{ computeMonthlyRate(row.annual_salary).toFixed(2) }}
                    </td>

                    <td class="px-3 py-2 border text-right">
                      {{ computeHourlyRate(row.annual_salary, row.factor).toFixed(2) }}
                    </td>

                    <td class="px-3 py-2 border text-right font-semibold text-blue-600">
                      {{ computeMonthlyRate(row.annual_salary).toFixed(2) }}
                    </td>

                    <td class="px-3 py-2 border text-right">{{ row.late }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.undertime }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.absent }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.ot }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.nsdReg }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.nsdOt }}</td>

                    <td class="px-3 py-2 border text-right">{{ row.rdReg }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.rdOt }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.nsdRdReg }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.nsdRdOt }}</td>

                    <td class="px-3 py-2 border text-right">{{ row.lhReg }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.lhOt }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.nsdLh }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.nsdLhOt }}</td>

                    <td class="px-3 py-2 border text-right">{{ row.shReg }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.shOt }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.nsdSh }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.nsdShOt }}</td>

                    <td class="px-3 py-2 border text-right">{{ row.lhrdReg }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.lhrdOt }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.nsdLhRd }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.nsdLhRdOt }}</td>

                    <td class="px-3 py-2 border text-right">{{ row.shrdReg }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.shrdOt }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.nsdShRd }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.nsdShRdOt }}</td>

                    <td class="px-3 py-2 border text-right">{{ row.dtrAdjustment }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.otAdjustment }}</td>

                    <td class="px-3 py-2 border text-right">
                      {{ row.mealAllowanceAdj }}
                    </td>
                    <td class="px-3 py-2 border text-right">
                      {{ row.uniformClothingAllowance }}
                    </td>
                    <td class="px-3 py-2 border text-right">
                      {{ row.transpoAllowance }}
                    </td>
                    <td class="px-3 py-2 border text-right">
                      {{ row.laundryAllowance }}
                    </td>
                    <td class="px-3 py-2 border text-right">
                      {{ row.busMarshallAllowance }}
                    </td>
                    <td class="px-3 py-2 border text-right">
                      {{ row.monthlyHomeSubsidy }}
                    </td>
                    <td class="px-3 py-2 border text-right">{{ row.gasAllowance }}</td>
                    <td class="px-3 py-2 border text-right">
                      {{ row.medicalAssistance }}
                    </td>
                    <td class="px-3 py-2 border text-right">
                      {{ row.medicalCashAllowance }}
                    </td>
                    <td class="px-3 py-2 border text-right">{{ row.retentionBonus }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.mealOt }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.riceAllowance }}</td>

                    <td class="px-3 py-2 border text-right">{{ row.employeeTax }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.employeeSavings }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.HdmfLoanAdj }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.coopLoan }}</td>
                    <td class="px-3 py-2 border text-right">
                      {{ row.sssSalaryLoanAdj }}
                    </td>
                    <td class="px-3 py-2 border text-right">{{ row.taxAdjustment }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.totalDeduction }}</td>

                    <td class="px-3 py-2 border text-right">{{ row.sssEmployee }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.sssMpdfEe }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.sssEmployer }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.sssMpfEr }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.sssEc }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.philhealthEe }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.philhealthEr }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.pagibigEe }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.pagibigEr }}</td>

                    <td class="px-3 py-2 border text-right">{{ row.gross }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.net }}</td>
                    <td class="px-3 py-2 border text-right">{{ row.atm }}</td>

                    <td class="px-3 py-2 border text-right text-red-600 font-semibold">
                      {{
                        computeLateDeduction(
                          row.annual_salary,
                          row.factor,
                          row.late
                        ).toFixed(2)
                      }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-else class="text-gray-500 mt-4">
              No payroll register generated yet.
            </div>
          </div>
        </div>

        <div v-if="activeStep === 8" class="space-y-6"></div>

        <div v-if="activeStep === 9" class="space-y-6"></div>
      </div>
    </div>
  </AppLayout>
</template>
