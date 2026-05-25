<?php

return [

    'employees' => [

        'label' => 'Employees',

        'model' => App\Models\Employee::class,

        'table' => 'employees',

        'columns' => [

            [
                'field' => 'employees.id',
                'label' => 'ID',
                'type' => 'number',
            ],

            [
                'field' => 'employees.empnum',
                'label' => 'Employee Number',
                'type' => 'string',
            ],

            [
                'field' => 'employees.firstName',
                'label' => 'First Name',
                'type' => 'string',
            ],

            [
                'field' => 'employees.lastName',
                'label' => 'Last Name',
                'type' => 'string',
            ],

            [
                'field' => 'employees.deptCode',
                'label' => 'Department Code',
                'type' => 'string',
            ],

            [
                'field' => 'employees.deptName',
                'label' => 'Department Name',
                'type' => 'string',
            ],

            [
                'field' => 'employees.jobTitle',
                'label' => 'Job Title',
                'type' => 'string',
            ],

            [
                'field' => 'employees.salary',
                'label' => 'Salary',
                'type' => 'number',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | JOINS
        |--------------------------------------------------------------------------
        |
        | No joins yet because there is no departments table.
        |
        */

        'joins' => [],
    ],

    'payroll' => [

        'label' => 'Payroll',

        'model' => App\Models\PayrollRegister::class,

        'table' => 'payrolls',

        'columns' => [

            [
                'field' => 'payrolls.basicPay',
                'label' => 'Basic Pay',
                'type' => 'number',
            ],

            [
                'field' => 'payrolls.grossPay',
                'label' => 'Gross Pay',
                'type' => 'number',
            ],
        ],

        'joins' => [],
    ],

];