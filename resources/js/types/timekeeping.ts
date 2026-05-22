// types.ts

export type ShiftCode = {
  id: number;
  shiftCode: string;
};

export type PayrollAdjustment = {
  id: number;
  shiftcode_id: string;
  time_in: string;
  time_out: string;
  shiftcode?: ShiftCode;
  adjusted_hours?: string;
  reason_id?: string;
  other_reason?: string;
  created_at?: string;
};

export type TimekeepingCorrection = {
  id: number;
  timekeeping_id: number | null;
  time_in: string;
  time_out: string;
  reason_id: number | null;
  shiftcode_id: number | null;
  status: "Pending" | "Approved" | "Rejected" | "Superseded" | "Adjusted";
  other_reason?: string;
  shiftCodeRelation?: ShiftCode;
};

export type Overtime = {
  id: number;
  hours: number;
  status: "Pending" | "Approved" | "Rejected" | "Deleted" | "Adjusted";
};

export type Timekeeping = {
  id: string;
  dated: string;
  dayType: string;
  shiftCode: string;
  shiftcode_id: number | null;
  corrected_shiftcode_id?: number | null;
  timeIn: string;
  timeOut: string;
  correctedShiftCode: string;
  correctedTimeIn: string;
  correctedTimeOut: string;
  typeCode: string;
  hoursWorked: number;
  overtime: number;
  reason: string;
  otherReason?: string;
  corrections: TimekeepingCorrection | null;
  adjustment?: PayrollAdjustment | null;
  overtimes?: Overtime[];
};
