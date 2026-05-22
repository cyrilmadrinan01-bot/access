export type StepKey =
  | 'timekeeping'
  | 'other_income'
  | 'deduction'
  | 'medical'
  | 'sss'
  | 'pagibig'
  | 'philhealth'
  | 'payroll'
  | 'bank'
  | 'payslip'

export const payrollSteps: { key: StepKey; label: string }[] = [
  { key: 'timekeeping', label: 'Process Timekeeping' },
  { key: 'other_income', label: 'Upload Other Income' },
  { key: 'deduction', label: 'Upload Employee Deductions' },
  { key: 'medical', label: 'Process Medical Reimbursement' },
  { key: 'sss', label: 'Generate SSS Contribution' },
  { key: 'pagibig', label: 'Generate Pag-Ibig Contribution' },
  { key: 'philhealth', label: 'Generate PhilHealth Contribution' },
  { key: 'payroll', label: 'Process Payroll Register' },
  { key: 'bank', label: 'Generate Bank File' },
  { key: 'payslip', label: 'Generate Payslip' },
]

