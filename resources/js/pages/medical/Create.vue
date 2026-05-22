<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { route } from 'ziggy-js';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import Label from '@/components/ui/label/Label.vue';
import Input from '@/components/ui/input/Input.vue';
import { DateFormatter, getLocalTimeZone, today, CalendarDate } from '@internationalized/date'
import { CalendarIcon } from 'lucide-vue-next'
import { ref, watch } from 'vue'
import { Calendar } from '@/components/ui/calendar'
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover'
import { cn } from '@/lib/utils'
import Textarea from '@/components/ui/textarea/Textarea.vue';

const date = ref<CalendarDate | undefined>()
const defaultPlaceholder = today(getLocalTimeZone())
const currentDate = today(getLocalTimeZone()) // today
const minDate = new CalendarDate(currentDate.year, 1, 1) 
const page = usePage();

interface AuthUser {
  empnum?: string;
  name?: string;
}

const rawAuthUser = page.props.auth?.user as AuthUser | undefined;

const authUser = {
  empnum: rawAuthUser?.empnum ?? '',
  name: rawAuthUser?.name ?? '',
};

// Get the balanceAmount from Inertia props
const balance = page.props.balance as number | undefined;
const MAX_AMOUNT: number = balance ?? 0;

const form = useForm<{
    empnum: string;
    empname: string;
    receiptNumber: string;
    amount: number;
    transactionDate: string;
    images: File[];
    employeeNotes: string;
}>({
    empnum: authUser?.empnum ?? '',
    empname: authUser?.name ?? '',
    receiptNumber: '',
    amount: 0,
    transactionDate: '',
    images: [],
    employeeNotes: '',
});

console.log('MAX_AMOUNT:', MAX_AMOUNT);
const handleImageSelection = (e: Event) => {
    const target = e.target as HTMLInputElement; // type assertion
    if (target.files) {
        form.images = Array.from(target.files); // important fix!
    }
};

const handleSubmit = () => {

    form.clearErrors('amount');

    if (form.amount !== null && form.amount > MAX_AMOUNT) {
        form.errors.amount = `Amount should not exceed ${MAX_AMOUNT}`;
        form.amount = parseFloat(form.amount.toFixed(2));
        return;
    }
    form.post('/medical', { forceFormData: true });

}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'File Medical Re-imbursement',
        href: route('medical.create'),
    },
];

watch(date, (newDate) => {
    if (newDate) {
        // Convert CalendarDate to YYYY-MM-DD
        form.transactionDate = `${newDate.year}-${String(newDate.month).padStart(2, '0')}-${String(newDate.day).padStart(2, '0')}`
    } else {
        form.transactionDate = ''
    }
})

</script>

<template>

    <Head title="New Medical Re-imbursement" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div className="p-4">
            <form @submit.prevent="handleSubmit" class="w-4/12 space-y-4">
                <div class="space-y-2">
                    <Label for="Employee number">Employee Number</Label>
                    <Input type="text" placeholder="Employee Number" v-model="form.empnum" readonly></Input>
                    <div class="text-sm text-red-600" v-if="form.errors.empnum">{{ form.errors.empnum }}</div>
                </div>
                <div class="space-y-2">
                    <Label for="Employee name">Employee Name</Label>
                    <Input type="text" placeholder="Employee Name" v-model="form.empname" readonly></Input>
                    <div class="text-sm text-red-600" v-if="form.errors.empname">{{ form.errors.empname }}</div>
                </div>
                <div class="space-y-2">
                    <Label for="Receipt number">Offical Receipt Number</Label>
                    <Input type="text" placeholder="Receipt number or Invoice number"
                        v-model="form.receiptNumber"></Input>
                    <div class="text-sm text-red-600" v-if="form.errors.receiptNumber">{{ form.errors.receiptNumber }}
                    </div>
                </div>
                <div class="space-y-2">
                    <Label for="Amount">Amount</Label>
                    <Input type="number" step="0.01" placeholder="Amount in number"
                        v-model.number="form.amount"></Input>
                    <div class="text-sm text-red-600" v-if="form.errors.amount">{{ form.errors.amount }}</div>
                </div>
                <div class="space-y-2">
                    <Label for="Transaction date">Transaction Date</Label>
                    <Popover>
                        <PopoverTrigger as-child>
                            <Button variant="outline" class="w-full justify-start text-left font-normal">
                                <CalendarIcon class="mr-2 h-4 w-4" />
                                {{ date ?
                                    `${date.year}-${String(date.month).padStart(2, '0')}-${String(date.day).padStart(2,
                                        '0')}`
                                    : "Pick a date" }}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0">
                            <Calendar v-model="date" :initial-focus="true" :default-placeholder="defaultPlaceholder" :min-value="minDate" :max-value="currentDate"
                                layout="month-and-year" />
                        </PopoverContent>
                    </Popover>
                    <div class="text-sm text-red-600" v-if="form.errors.transactionDate">{{ form.errors.transactionDate
                    }}</div>
                </div>
                <div class="space-y-2">
                    <Label>Upload Receipt</Label>
                    <Input type="file" accept="image/*" multiple @change="handleImageSelection" required></Input>
                    <div class="text-sm text-red-600" v-if="form.errors.images">{{ form.errors.images }}</div>
                </div>
                <div class="space-y-2">
                    <Label for="Employee notes">Employee Notes</Label>
                    <Textarea id="Employee notes" placeholder="Notes" v-model="form.employeeNotes"
                        
                        rows="4"></textarea>
                    <div class="text-sm text-red-600" v-if="form.errors.employeeNotes">{{ form.errors.employeeNotes }}
                    </div>
                </div>
                <Button type="submit" :disabled="form.processing" class="flex items-center justify-center">
                    <span v-if="form.processing" class="loader mr-2"></span>
                    <span>{{ form.processing ? 'Submitting...' : 'Submit' }}</span>
                </Button>
            </form>
        </div>
    </AppLayout>
</template>

<style>
.loader {
  border: 2px solid transparent;
  border-top: 2px solid white;
  border-right: 2px solid white;
  border-radius: 50%;
  width: 16px;
  height: 16px;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>