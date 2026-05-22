<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { route } from "ziggy-js";

/* -------------------------
  STEPS
--------------------------*/
import StepTimekeeping from "@/steps/payroll/StepTimekeeping.vue";
import StepOtherIncome from "@/steps/payroll/StepOtherIncome.vue";
import StepDeduction from "@/steps/payroll/StepDeduction.vue";
import StepMedical from "@/steps/payroll/StepMedical.vue";
import StepSSS from "@/steps/payroll/StepSSS.vue";
import StepPagibig from "@/steps/payroll/StepPagibig.vue";
import StepPhilHealth from "@/steps/payroll/StepPhilHealth.vue";
import StepPayroll from "@/steps/payroll/StepPayroll.vue";
import StepBank from "@/steps/payroll/StepBank.vue";
import StepPayslip from "@/steps/payroll/StepPayslip.vue";

/* -------------------------
  TYPES
--------------------------*/
type StepKey =
  | "timekeeping"
  | "other_income"
  | "deduction"
  | "medical"
  | "sss"
  | "pagibig"
  | "philhealth"
  | "payroll"
  | "bank"
  | "payslip";

type Cutoff = {
  id: number;
  cutOffStart: string;
  cutOffEnd: string;
  payrollDate: string;
};

type StepsStatus = Record<StepKey, boolean>;

type OtherIncomeRow = {
  id: number;
  empnum: string;
  empname: string;
  income_type: string;
  amount: number;
  is_taxable: number;
};

type EmployeeDeductionRow = {
  id: number;
  empnum: string;
  empname: string;
  deduction_type: string;
  amount: number;
  is_pre_tax: number;
};

/* -------------------------
  PROPS
--------------------------*/
const props = defineProps<{
  cutoffs: Cutoff[];
  selectedCutoffId?: number | null;
  stepsStatus: StepsStatus;

  timekeepingRows?: any[];

  otherIncomeRows?: OtherIncomeRow[];
  otherIncomeUploadedAt?: string | null;
  hasOtherIncomeData?: boolean;

  employeeDeductionRows?: EmployeeDeductionRow[];
  deductionUploadedAt?: string | null;
  hasDeductionData?: boolean;

  medicalRows?: any[];
  hasMedicalData?: boolean;

  sssRows?: any[];
  hasSSSData?: boolean;

  pagibigRows?: any[];
  hasPagibigData?: boolean;

  philhealthRows?: any[];
  hasPhilhealthData?: boolean;

  payrollRows?: any[];
  hasPayrollData?: boolean;

  bankRows?: any[];
  hasBankData?: boolean;

  payrollPayslipRows?: any[];
  hasPayslipData?: boolean;
}>();

/* -------------------------
  STATE
--------------------------*/
const selectedCutoffId = ref<number | null>(props.selectedCutoffId ?? null);

const activeStep = ref<number>(0);

/* -------------------------
  STEPS CONFIG
--------------------------*/
const steps: { key: StepKey; label: string }[] = [
  { key: "timekeeping", label: "Process Timekeeping" },
  { key: "other_income", label: "Upload Other Income" },
  { key: "deduction", label: "Upload Employee Deductions" },
  { key: "medical", label: "Process Medical Re-imbursement" },
  { key: "sss", label: "Generate SSS Contribution" },
  { key: "pagibig", label: "Generate Pag-Ibig Contribution" },
  { key: "philhealth", label: "Generate PhilHealth Contribution" },
  { key: "payroll", label: "Process Payroll Register" },
  { key: "bank", label: "Generate Bank File" },
  { key: "payslip", label: "Generate Payslip" },
];

/* -------------------------
  FORMAT
--------------------------*/
function formatDate(dateTime: string) {
  if (!dateTime) return "—";
  const [date] = dateTime.split("T");
  const [y, m, d] = date.split("-");
  return `${m}-${d}-${y}`;
}

/* -------------------------
  STEP CONTROL
--------------------------*/
function canAccessStep(index: number) {
  const stepKey = steps[index].key;

  // ✅ Allow if already completed
  if (props.stepsStatus?.[stepKey]) return true;

  // ✅ Allow current step
  if (index === activeStep.value) return true;

  // ❌ Otherwise only allow if previous step is completed
  if (index === 0) return true;

  const prevKey = steps[index - 1].key;
  return props.stepsStatus?.[prevKey] === true;
}

function getStepState(index: number) {
  const key = steps[index].key;

  if (props.stepsStatus?.[key]) return "completed";
  if (index === activeStep.value) return "active";
  return "locked";
}

function goToStep(index: number) {
  if (!canAccessStep(index)) return;
  activeStep.value = index;
}

function goNextStep() {
  if (activeStep.value < steps.length - 1) {
    activeStep.value++;
  }
}

/* -------------------------
  START PROCESS
--------------------------*/
function goToFirstStep() {
  if (!selectedCutoffId.value) {
    alert("Please select a cutoff first.");
    return;
  }

  activeStep.value = 0;
}

/* -------------------------
  RUN TIMEKEEPING
--------------------------*/
function runTimekeepingProcess(cutoffId: number) {
  router.post(
    route("timekeeping.run.process", { cutoff: cutoffId }),
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        router.visit(route("payroll.process", { cutoff: cutoffId }));
      },
    }
  );
}

function handleOtherIncomeUpload(payload: { file: File; is_taxable: number }) {
  if (!selectedCutoffId.value) {
    alert("Please select cutoff first.");
    return;
  }

  const formData = new FormData();
  formData.append("file", payload.file);
  formData.append("cutoff_id", selectedCutoffId.value.toString());
  formData.append("is_taxable", payload.is_taxable.toString());

  router.post(
    route("other-income.upload", { cutoff: selectedCutoffId.value }),
    formData,
    {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        goNextStep();

        router.reload({
          only: ["otherIncomeRows", "stepsStatus"],
        });
      },
      onError: (e) => {
        console.error(e);
        alert("Upload failed");
      },
    }
  );
}

function handleDeductionUpload(payload: { file: File }) {
  if (!selectedCutoffId.value) {
    alert("Please select cutoff first.");
    return;
  }

  const formData = new FormData();
  formData.append("file", payload.file);
  formData.append("cutoff_id", selectedCutoffId.value.toString());

  router.post(route("deduction.upload", { cutoff: selectedCutoffId.value }), formData, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      goNextStep();

      router.reload({
        only: ["employeeDeductionRows", "stepsStatus"],
      });
    },
    onError: (e) => {
      console.error(e);
      alert("Upload failed");
    },
  });
}

function processMedical() {
  if (!selectedCutoffId.value) {
    alert("Please select cutoff first.");
    return;
  }

  router.post(
    route("medical.run.process", selectedCutoffId.value),
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        goNextStep();

        router.reload({
          only: ["medicalRows", "stepsStatus"],
        });
      },
    }
  );
}

function processSSS() {
  if (!selectedCutoffId.value) {
    alert("Please select cutoff first.");
    return;
  }

  router.post(
    route("sss.run.process", selectedCutoffId.value),
    {
      year: new Date().getFullYear(),
      month: new Date().getMonth() + 1,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        goNextStep();

        router.reload({
          only: ["sssRows", "stepsStatus"],
        });
      },
    }
  );
}

function processPagibig() {
  if (!selectedCutoffId.value) {
    alert("Please select cutoff first.");
    return;
  }

  router.post(
    route("pagibig.run.process", selectedCutoffId.value),
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        goNextStep();

        router.reload({
          only: ["pagibigRows", "stepsStatus"],
        });
      },
    }
  );
}

function processPhilhealth() {
  if (!selectedCutoffId.value) {
    alert("Please select cutoff first.");
    return;
  }

  router.post(
    route("philhealth.run.process", selectedCutoffId.value),
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        goNextStep();

        router.reload({
          only: ["philhealthRows", "stepsStatus"],
        });
      },
    }
  );
}

function processPayroll() {
  if (!selectedCutoffId.value) {
    alert("Please select cutoff first.");
    return;
  }

  router.post(
    route("payroll.run.process", selectedCutoffId.value),
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        goNextStep();

        router.reload({
          only: ["payrollRows", "stepsStatus"],
        });
      },
    }
  );
}

function processPayslip() {
  if (!selectedCutoffId.value) {
    alert("Please select cutoff first.");
    return;
  }

  router.post(
    route("payslip.run.process", selectedCutoffId.value),
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        goNextStep();

        router.reload({
          only: ["payrollPayslipRows", "stepsStatus"],
        });
      },
    }
  );
}

function skipStep(stepKey: StepKey) {
  if (!selectedCutoffId.value) return;

  let routeName = "";

  if (stepKey === "other_income") routeName = "other-income.skip";
  if (stepKey === "deduction") routeName = "deduction.skip";
  if (stepKey === "sss") routeName = "sss.skip";
  if (stepKey === "pagibig") routeName = "pagibig.skip";
  if (stepKey === "philhealth") routeName = "philhealth.skip";
  if (stepKey === "payroll") routeName = "payroll.skip";
  if (stepKey === "payslip") routeName = "payslip.skip";

  if (!routeName) return;

  router.post(
    route(routeName, { cutoff: selectedCutoffId.value }),
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        goNextStep();

        router.reload({
          only: ["stepsStatus"],
        });
      },
    }
  );
}

/* -------------------------
  COMPUTED SAFETY
--------------------------*/
const status = computed(() => props.stepsStatus ?? {});
</script>

<template>
  <Head title="Payroll Process" />

  <AppLayout>
    <div class="p-4 flex flex-col gap-4">
      <h1 class="text-2xl font-bold">Payroll Process</h1>

      <!-- CUT-OFF SELECT -->
      <div class="flex gap-4 items-center">
        <select
          v-model="selectedCutoffId"
          class="border rounded px-3 py-2 dark:bg-gray-800"
        >
          <option :value="null">Select Cutoff</option>

          <option v-for="c in cutoffs" :key="c.id" :value="c.id">
            {{ formatDate(c.cutOffStart) }} -
            {{ formatDate(c.cutOffEnd) }}
            (Pay: {{ formatDate(c.payrollDate) }})
          </option>
        </select>
        <!--
        <button
          @click="goToFirstStep"
          class="px-4 py-2 bg-blue-600 text-white rounded"
          :disabled="!selectedCutoffId"
        >
          Continue Process
        </button>
-->
      </div>

      <!-- STEPPER -->
      <div class="flex border-b mt-4 overflow-x-auto">
        <button
          v-for="(step, index) in steps"
          :key="step.key"
          @click="goToStep(index)"
          :disabled="!canAccessStep(index)"
          class="px-4 py-2 border-b-2 whitespace-nowrap transition"
          :class="{
            'border-green-500 text-green-600': getStepState(index) === 'completed',
            'border-blue-500 text-blue-600 font-bold': getStepState(index) === 'active',
            'border-gray-300 text-gray-400 cursor-not-allowed':
              getStepState(index) === 'locked',
          }"
        >
          {{ step.label }}

          <span v-if="getStepState(index) === 'completed'">✔</span>
        </button>
      </div>

      <!-- STEP CONTENT -->
      <div class="mt-6">
        <!-- STEP 1 -->
        <StepTimekeeping
          v-if="activeStep === 0"
          v-model:cutoffId="selectedCutoffId"
          :cutoffs="cutoffs"
          :timekeepingRows="timekeepingRows"
          @run="runTimekeepingProcess"
          @next="goNextStep"
        />

        <!-- STEP 2 -->
        <StepOtherIncome
          v-if="activeStep === 1"
          v-model:cutoffId="selectedCutoffId"
          :cutoffs="cutoffs"
          :otherIncomeRows="otherIncomeRows"
          :otherIncomeUploadedAt="otherIncomeUploadedAt"
          @run="handleOtherIncomeUpload"
          @skip="() => skipStep('other_income')"
          @next="goNextStep"
        />

        <!-- STEP 2 -->
        <StepDeduction
          v-if="activeStep === 2"
          v-model:cutoffId="selectedCutoffId"
          :cutoffs="cutoffs"
          :employeeDeductionRows="employeeDeductionRows"
          :employeeDeductionUploadedAt="deductionUploadedAt"
          @run="handleDeductionUpload"
          @skip="() => skipStep('deduction')"
          @next="goNextStep"
        />

        <StepMedical
          v-if="activeStep === 3"
          v-model:cutoffId="selectedCutoffId"
          :cutoffs="cutoffs"
          :medicalRows="medicalRows"
          @run="processMedical"
          @skip="() => skipStep('medical')"
          @next="goNextStep"
        />

        <StepSSS
          v-if="activeStep === 4"
          :cutoffId="selectedCutoffId"
          :sssRows="sssRows"
          @next="goNextStep"
          @run="processSSS"
          @skip="() => skipStep('sss')"
          @reload="() => router.reload({ only: ['sssRows', 'stepsStatus'] })"
        />

        <!-- STEP PAG-IBIG -->
        <StepPagibig
          v-if="activeStep === 5"
          :cutoffId="selectedCutoffId"
          :pagibigRows="pagibigRows"
          @next="goNextStep"
          @run="processPagibig"
          @skip="() => skipStep('pagibig')"
          @reload="() => router.reload({ only: ['pagibigRows', 'stepsStatus'] })"
        />

        <!-- STEP PHILHEALTH -->
        <StepPhilHealth
          v-if="activeStep === 6"
          :cutoffId="selectedCutoffId"
          :philhealthRows="philhealthRows"
          @next="goNextStep"
          @run="processPhilhealth"
          @skip="() => skipStep('philhealth')"
          @reload="() => router.reload({ only: ['philhealthRows', 'stepsStatus'] })"
        />

        <!-- STEP PAYROLL -->
        <StepPayroll
          v-if="activeStep === 7"
          :cutoffId="selectedCutoffId"
          :payrollRows="payrollRows"
          @next="goNextStep"
          @run="processPayroll"
          @skip="() => skipStep('payroll')"
          @reload="() => router.reload({ only: ['payrollRows', 'stepsStatus'] })"
        />

        <StepBank
          v-if="activeStep === 8"
          :cutoffId="selectedCutoffId"
          :bankRows="bankRows"
          @next="goNextStep"
          @reload="() => router.reload({ only: ['bankRows', 'stepsStatus'] })"
        />

        <StepPayslip
          v-if="activeStep === 9"
          v-model:cutoffId="selectedCutoffId"
          :cutoffs="cutoffs"
          :payrollPayslipRows="payrollPayslipRows"
          @run="processPayslip"
          @reload="() => router.reload({ only: ['payrollPayslipRows', 'stepsStatus'] })"
        />
      </div>
    </div>
  </AppLayout>
</template>
