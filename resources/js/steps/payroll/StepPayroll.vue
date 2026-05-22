<!-- resources/js/steps/payroll/StepPayroll.vue -->
<script setup lang="ts">
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";

const props = defineProps<{
  cutoffId: number | null;

  payrollRows?: {
    id: number;
    empnum: string;
    empname: string;
    accountNumber: string;
    payrollType: string;
    factor: number;
    annual_salary: number;
    monthly_rate: number;
    hourly_rate: number;
    basic_pay: number;
    late: number;
    absent: number;
    undertime: number;
    ot: number;
    nsdReg: number;
    nsdOt: number;
    rdReg: number;
    rdOt: number;
    nsdRdReg: number;
    nsdRdOt: number;
    lhReg: number;
    lhOt: number;
    nsdLh: number;
    nsdLhOt: number;
    shReg: number;
    shOt: number;
    nsdSh: number;
    nsdShOt: number;
    lhrdReg: number;
    lhrdOt: number;
    nsdLhRd: number;
    nsdLhRdOt: number;
    shrdReg: number;
    shrdOt: number;
    nsdShRd: number;
    nsdShRdOt: number;
    dtrAdjustment: number;
    otAdjustment: number;
    mealAllowanceAdj: number;
    uniformClothingAllowance: number;
    transpoAllowance: number;
    laundryAllowance: number;
    busMarshallAllowance: number;
    monthlyHomeSubsidy: number;
    gasAllowance: number;
    medicalAssistance: number;
    medicalCashAllowance: number;
    retentionBonus: number;
    mealOt: number;
    riceAllowance: number;
    employeeTax: number;
    employeeSavings: number;
    HdmfLoanAdj: number;
    coopLoan: number;
    sssSalaryLoanAdj: number;
    taxAdjustment: number;
    totalDeduction: number;
    sssEmployee: number;
    sssMpdfEe: number;
    sssEmployer: number;
    sssMpfEr: number;
    sssEc: number;
    philhealthEe: number;
    philhealthEr: number;
    pagibigEe: number;
    pagibigEr: number;
    gross: number;
    net: number;
    atm: number;
  }[];
}>();

const emit = defineEmits<{
  (e: "update:cutoffId", value: number | null): void;
  (e: "next"): void;
  (e: "reload"): void;
  (e: "skip"): void;
}>();

const taxationType = ref("Semi-Monthly");

function computeMonthlyRate(annual: number) {
  return annual / 12;
}

function computeHourlyRate(annual: number, factor: number) {
  return annual / factor;
}

function computeLateDeduction(annual: number, factor: number, lateHours: number) {
  const hourly = annual / factor;
  return hourly * lateHours;
}

function processPayroll() {
  if (!props.cutoffId) {
    alert("Please select cutoff first.");
    return;
  }

  router.post(
    route("payroll.run.process", props.cutoffId),
    {
      payrollType: taxationType.value, // send to controller
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        emit("next");
        emit("reload");
      },
    }
  );
}

function goNext() {
  emit("next");
}

function skipStep() {
  emit("skip");
  emit("next"); // ✅ move forward after skip
}
</script>

<template>
  <div class="space-y-6">
    <h2 class="text-xl font-bold">Step 8: Payroll Register</h2>

    <div class="max-w-xs">
      <label class="block text-sm font-medium mb-1">Taxation Type</label>
      <select v-model="taxationType" class="w-full border rounded-lg px-3 py-2">
        <option value="Semi-Monthly">Semi-Monthly</option>
        <option value="Annual">Annual</option>
      </select>
    </div>

    <!-- ACTION -->
    <div class="flex gap-3">
      <button
        @click="processPayroll"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
      >
        Generate Payroll Register
      </button>

      <button
        @click="skipStep"
        class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
      >
        Skip
      </button>

      <button
        @click="goNext"
        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
      >
        Next
      </button>
    </div>

    <!-- TABLE -->
    <div
      v-if="payrollRows?.length"
      class="mt-6 w-full overflow-auto max-h-[600px] rounded-xl border"
    >
      <table class="min-w-max text-sm border-collapse">
        <!-- HEADER -->
        <thead class="bg-gray-100 dark:bg-gray-800 sticky top-0 z-20">
          <tr>
            <th class="px-3 py-2 border sticky left-0 z-30 bg-gray-100 dark:bg-gray-800">
              Emp #
            </th>

            <th
              class="px-3 py-2 border sticky left-[90px] z-30 bg-gray-100 dark:bg-gray-800"
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
            <th class="px-3 py-2 border text-right">Uniform/Clothing Allowance</th>
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
            <td class="px-3 py-2 border sticky left-0 bg-white dark:bg-gray-900 z-10">
              {{ row.empnum }}
            </td>

            <td
              class="px-3 py-2 border sticky left-[90px] bg-white dark:bg-gray-900 z-10"
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
                computeLateDeduction(row.annual_salary, row.factor, row.late).toFixed(2)
              }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="text-gray-500">No payroll register generated yet.</div>
  </div>
</template>
