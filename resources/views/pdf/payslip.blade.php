@php

    $money = fn ($v) => number_format($v ?? 0, 2);

    /*
    |--------------------------------------------------------------------------
    | COMPUTED VALUES
    |--------------------------------------------------------------------------
    */
    $holidayPay =
        ($payslip->lhReg ?? 0) +
        ($payslip->shReg ?? 0) +
        ($payslip->lhrdReg ?? 0) +
        ($payslip->shrdReg ?? 0);

    $otherIncome =
        ($payslip->ot ?? 0) +
        ($payslip->nsdReg ?? 0) +
        ($payslip->rdReg ?? 0) +
        $holidayPay;

    $loans =
        ($payslip->HdmfLoanAdj ?? 0) +
        ($payslip->sssSalaryLoanAdj ?? 0) +
        ($payslip->coopLoan ?? 0);

    /*
    |--------------------------------------------------------------------------
    | BENEFITS ARRAY
    |--------------------------------------------------------------------------
    */
    $benefits = [

        [
            'label' => 'Medical Cash Allowance',
            'current' => $payslip->medicalCashAllowance ?? 0,
            'ytd' => $ytd->medicalCashAllowance ?? 0,
        ],

        [
            'label' => 'Laundry Allowance',
            'current' => $payslip->laundryAllowance ?? 0,
            'ytd' => $ytd->laundryAllowance ?? 0,
        ],

        [
            'label' => 'Uniform & Clothing Allowance',
            'current' => $payslip->uniformClothingAllowance ?? 0,
            'ytd' => $ytd->uniformClothingAllowance ?? 0,
        ],

        [
            'label' => 'Transportation Allowance',
            'current' => $payslip->transpoAllowance ?? 0,
            'ytd' => $ytd->transpoAllowance ?? 0,
        ],

        [
            'label' => 'Rice Allowance',
            'current' => $payslip->riceAllowance ?? 0,
            'ytd' => $ytd->riceAllowance ?? 0,
        ],
    ];

    /*
    |--------------------------------------------------------------------------
    | DEDUCTIONS ARRAY
    |--------------------------------------------------------------------------
    */
    $deductions = [

        [
            'label' => 'SSS MPF Employee',
            'current' => $payslip->sssMpfEe ?? 0,
            'ytd' => $ytd->sssMpfEe ?? 0,
        ],

        [
            'label' => 'SSS',
            'current' => $payslip->sssEmployee ?? 0,
            'ytd' => $ytd->sssEmployee ?? 0,
        ],

        [
            'label' => 'PhilHealth',
            'current' => $payslip->philhealthEe ?? 0,
            'ytd' => $ytd->philhealthEe ?? 0,
        ],

        [
            'label' => 'Pag-IBIG',
            'current' => $payslip->pagibigEe ?? 0,
            'ytd' => $ytd->pagibigEe ?? 0,
        ],

        [
            'label' => 'Loans',
            'current' => $loans,
            'ytd' =>
                ($ytd->hdmf_loan ?? 0) +
                ($ytd->sss_loan ?? 0) +
                ($ytd->coop_loan ?? 0),
        ],

        [
            'label' => 'Withholding Tax',
            'current' => $payslip->employeeTax ?? 0,
            'ytd' => $ytd->withholding_tax ?? 0,
        ],
    ];

@endphp

<!DOCTYPE html>
<html>
<head>

<meta charset="utf-8">

<style>

    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 11px;
        color: #111;
        margin: 28px;
    }

    .logo {
        text-align: center;
        font-size: 52px;
        font-weight: bold;
        color: #1e40af;
        margin-bottom: 8px;
        letter-spacing: -1px;
    }

    .bar {
        background: #d1d5db;
        font-weight: bold;
        padding: 4px 6px;
        margin-top: 10px;
        text-transform: uppercase;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    td, th {
        padding: 2px 4px;
        vertical-align: top;
    }

    .right {
        text-align: right;
    }

    .center {
        text-align: center;
    }

    .bold {
        font-weight: bold;
    }

    .summary-line {
        border-top: 1px dashed #000;
        font-weight: bold;
    }

    .netpay {
        border-top: 2px solid #000;
        border-bottom: 2px solid #000;
        font-weight: bold;
        font-size: 15px;
    }

    .space-row td {
        padding-top: 12px;
    }

</style>

</head>

<body>

<!-- LOGO -->

<div class="logo">
    maxeon
</div>

<!-- PERSONAL DETAILS -->

<div class="bar">
    PERSONAL DETAILS
</div>

<table>

    <tr>

        <td width="18%" class="bold">Employee Name</td>
        <td width="32%">: {{ $payslip->empname }}</td>

        <td width="18%" class="bold">SSS No</td>
        <td width="32%">: {{ $employee->sss ?? '-' }}</td>

    </tr>

    <tr>

        <td class="bold">Employee ID</td>
        <td>: {{ $payslip->empnum }}</td>

        <td class="bold">PhilHealth No</td>
        <td>: {{ $employee->philhealth ?? '-' }}</td>

    </tr>

    <tr>

        <td class="bold">Department Name</td>
        <td>: {{ $employee->deptName ?? '-' }}</td>

        <td class="bold">TIN No</td>
        <td>: {{ $employee->tin ?? '-' }}</td>

    </tr>

    <tr>

        <td class="bold">Payroll Period</td>
        <td>: {{ $payrollDate }}</td>

        <td class="bold">PAGIBIG No</td>
        <td>: {{ $employee->pagibig ?? '-' }}</td>

    </tr>

</table>

<!-- PAYSLIP TABLE -->

<table style="margin-top:18px;">

    <thead>

        <tr style="background:#d1d5db; font-weight:bold;">

            <td width="50%">DETAILS:</td>
            <td width="15%" class="center">Hours/Days</td>
            <td width="17%" class="right">Current</td>
            <td width="18%" class="right">Year To Date</td>

        </tr>

    </thead>

    <tbody>

        <!-- EARNINGS -->

        <tr>
            <td class="bold">EARNINGS</td>
            <td></td>
            <td></td>
            <td></td>
        </tr>

        @if(($payslip->basic_pay ?? 0) > 0)
        <tr>
            <td>Basic Pay</td>
            <td></td>
            <td class="right">
                {{ $money($payslip->basic_pay) }}
            </td>
            <td class="right">
                {{ $money($ytd->basic_pay ?? 0) }}
            </td>
        </tr>
        @endif

        @if($otherIncome > 0)
        <tr>
            <td>Other Income</td>
            <td></td>
            <td class="right">
                {{ $money($otherIncome) }}
            </td>
            <td class="right">
                {{ $money($ytd->other_income ?? 0) }}
            </td>
        </tr>
        @endif

        <tr class="summary-line">
            <td class="bold">GROSS PAY</td>
            <td></td>
            <td class="right bold">
                {{ $money($payslip->gross_pay) }}
            </td>
            <td class="right bold">
                {{ $money($ytd->gross_pay ?? 0) }}
            </td>
        </tr>

        <!-- BENEFITS -->

        <tr class="space-row">
            <td class="bold">BENEFITS</td>
            <td></td>
            <td></td>
            <td></td>
        </tr>

        @foreach($benefits as $benefit)

            @if(($benefit['current'] ?? 0) > 0)

            <tr>

                <td>{{ $benefit['label'] }}</td>

                <td></td>

                <td class="right">
                    {{ $money($benefit['current']) }}
                </td>

                <td class="right">
                    {{ $money($benefit['ytd']) }}
                </td>

            </tr>

            @endif

        @endforeach

        <tr class="summary-line">

            <td class="bold">TOTAL INCOME</td>

            <td></td>

            <td class="right bold">
                {{ $money($payslip->total_income) }}
            </td>

            <td class="right bold">
                {{ $money($ytd->total_income ?? 0) }}
            </td>

        </tr>

        <!-- DEDUCTIONS -->

        <tr class="space-row">

            <td class="bold">DEDUCTIONS</td>

            <td></td>

            <td></td>

            <td></td>

        </tr>

        @foreach($deductions as $deduction)

            @if(($deduction['current'] ?? 0) > 0)

            <tr>

                <td>{{ $deduction['label'] }}</td>

                <td></td>

                <td class="right">
                    {{ $money($deduction['current']) }}
                </td>

                <td class="right">
                    {{ $money($deduction['ytd']) }}
                </td>

            </tr>

            @endif

        @endforeach

        <tr class="summary-line">

            <td class="bold">TOTAL DEDUCTIONS</td>

            <td></td>

            <td class="right bold">
                {{ $money($payslip->total_deduction) }}
            </td>

            <td class="right bold">
                {{ $money($ytd->total_deduction ?? 0) }}
            </td>

        </tr>

        <!-- NET PAY -->

        <tr class="netpay">

            <td>NET PAY</td>

            <td></td>

            <td class="right">
                {{ $money($payslip->net_pay) }}
            </td>

            <td class="right">
                {{ $money($ytd->net_pay ?? 0) }}
            </td>

        </tr>

    </tbody>

</table>

</body>
</html>