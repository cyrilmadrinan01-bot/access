<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { route } from 'ziggy-js';
import axios from 'axios';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import Label from '@/components/ui/label/Label.vue';
import Input from '@/components/ui/input/Input.vue';
import { DateFormatter, getLocalTimeZone, today, CalendarDate } from '@internationalized/date'
import { CalendarIcon } from 'lucide-vue-next'
import { ref, onMounted, computed, watch } from 'vue'
import { Calendar } from '@/components/ui/calendar'
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover'
import Textarea from '@/components/ui/textarea/Textarea.vue';
import ImageManager from '@/components/medical/ImageManager.vue';

const date = ref<CalendarDate | undefined>()
const defaultPlaceholder = today(getLocalTimeZone())
const currentDate = today(getLocalTimeZone()) // today
const minDate = new CalendarDate(currentDate.year, 1, 1)

interface MedicalImage {
    id: number;
    image_path: string;
}

const props = defineProps<{
    medical: Record<string, any>,
    medicalImages: MedicalImage[],  // <-- explicitly type here
    balance: number
}>();

// Destructure safely
const { medical, medicalImages, balance } = props;

const form = useForm({
    empnum: medical.empnum ?? '',
    empname: medical.empname ?? '',
    receiptNumber: medical.receiptNumber ?? '',
    amount: medical.amount ?? null,
    transactionDate: medical.transactionDate ?? '',
    employeeNotes: medical.employeeNotes ?? '',
    new_images: [] as File[],
    deleted_images: [] as number[],
    replaced_images: [] as { id: number; file: File | null }[],
});

onMounted(() => {
    if (form.transactionDate) {
        const [year, month, day] = form.transactionDate.split('-').map(Number)
        date.value = new CalendarDate(year, month, day)
    }
})

watch(date, (newDate) => {
    if (!newDate) return;

    form.transactionDate = `${newDate.year}-${String(newDate.month).padStart(2, '0')}-${String(newDate.day).padStart(2, '0')}`;
});

function appendReplacedImagesToFormData(formData: FormData, replaced_images: typeof form.replaced_images) {
    replaced_images.forEach((item, index) => {
        formData.append(`replaced_images[${index}][id]`, item.id.toString());
        if (item.file) {
            formData.append(`replaced_images[${index}][file]`, item.file);
        }
    });
}

const handleImageUpdate = (payload: {
        newImages: File[],
        deletedImages: number[],
        replacedImages: { id: number, file: File }[],
    }) => {
        form.new_images = payload.newImages;
        form.deleted_images = payload.deletedImages;
        form.replaced_images = payload.replacedImages;
    };

const handleSubmit = () => {
    const data = new FormData();

    // Append fields
    data.append('empnum', form.empnum);
    data.append('empname', form.empname);
    data.append('receiptNumber', form.receiptNumber);
    data.append('amount', form.amount ? form.amount.toString() : '');
    data.append('transactionDate', form.transactionDate);
    data.append('employeeNotes', form.employeeNotes);

    // Spoof PUT method
    data.append('_method', 'PUT');

    // Arrays
    form.deleted_images.forEach((id, i) => {
        data.append(`deleted_images[${i}]`, id.toString());
    });

    form.new_images.forEach((file, i) => {
        data.append(`new_images[${i}]`, file);
    });

    appendReplacedImagesToFormData(data, form.replaced_images);

    axios.post(route('medical.update', { id: medical.id }), data, {
        headers: { 'Content-Type': 'multipart/form-data' },
    })
    .then((response) => {
        // Flash message returned from Laravel
        if (response.data.message) {
            // You can replace alert() with a toast component if desired
            alert(response.data.message);
        }

        // Redirect to index page
        window.location.href = route('medical');
    })
    .catch((error) => {
        if (error.response?.status === 422) {
            const errors = error.response.data.errors as Record<string, string[]>;

            // Map validation errors safely to form.errors
            (Object.keys(errors) as (keyof typeof form.errors)[]).forEach((key) => {
                form.errors[key] = errors[key][0];
            });
        } else {
            console.error(error);
            alert('Something went wrong!');
        }
    });
};

</script>

<template>

    <Head title="Edit Medical Re-imbursement" />

    <AppLayout :breadcrumbs="[{ title: 'Edit Medical Re-imbursement', href: `/medical/${props.medical.id}/edit` }]">
        <!-- Container grid: left form, right image manager -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-4">

            <!-- LEFT: Form Fields -->
            <div class="space-y-4">
                <form @submit.prevent="handleSubmit" class="space-y-4">

                    <!-- Employee Number -->
                    <div class="space-y-1">
                        <Label>Employee Number</Label>
                        <Input v-model="form.empnum" placeholder="Employee Number" />
                        <div class="text-sm text-red-600" v-if="form.errors.empnum">{{ form.errors.empnum }}</div>
                    </div>

                    <!-- Employee Name -->
                    <div class="space-y-1">
                        <Label>Employee Name</Label>
                        <Input v-model="form.empname" placeholder="Employee Name" />
                        <div class="text-sm text-red-600" v-if="form.errors.empname">{{ form.errors.empname }}</div>
                    </div>

                    <!-- Receipt Number -->
                    <div class="space-y-1">
                        <Label>Receipt Number</Label>
                        <Input v-model="form.receiptNumber" placeholder="Receipt Number or Invoice Number" />
                        <div class="text-sm text-red-600" v-if="form.errors.receiptNumber">{{ form.errors.receiptNumber
                        }}</div>
                    </div>

                    <!-- Amount -->
                    <div class="space-y-1">
                        <Label>Amount</Label>
                        <Input type="number" step="0.01" v-model.number="form.amount" placeholder="Amount in number" />
                        <div class="text-sm text-red-600" v-if="form.errors.amount">{{ form.errors.amount }}</div>
                    </div>

                    <!-- Transaction Date -->
                    <div class="space-y-1">
                        <Label>Transaction Date</Label>
                        <Popover>
                            <PopoverTrigger as-child>
                                <Button variant="outline" class="w-full justify-start text-left font-normal">
                                    <CalendarIcon class="mr-2 h-4 w-4" />
                                    {{ date
                                        ?
                                        `${date.year}-${String(date.month).padStart(2, '0')}-${String(date.day).padStart(2,
                                            '0')}`
                                        : form.transactionDate }}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0">
                                <Calendar v-model="date" :initial-focus="true" :default-placeholder="defaultPlaceholder"
                                    :min-value="minDate" :max-value="currentDate" layout="month-and-year" />
                            </PopoverContent>
                        </Popover>
                    </div>

                    <!-- Employee Notes -->
                    <div class="space-y-1">
                        <Label>Employee Notes</Label>
                        <Textarea v-model="form.employeeNotes" rows="4" class="w-full p-2 border rounded" />
                        <div class="text-sm text-red-600" v-if="form.errors.employeeNotes">{{ form.errors.employeeNotes
                        }}</div>
                    </div>

                    <!-- Form Buttons -->
                    <div class="flex space-x-2">
                        <Button type="submit" :disabled="form.processing"
                            class="flex items-center justify-center px-4 py-2 bg-blue-500 text-white rounded shadow">
                            <span v-if="form.processing" class="loader mr-2"></span>
                            <span>{{ form.processing ? 'Submitting...' : 'Update' }}</span>
                        </Button>

                        <Link :href="route('medical')"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded shadow text-gray-700">
                        Cancel
                        </Link>
                    </div>

                </form>
            </div>

            <!-- RIGHT: Image Manager -->
            <div class="space-y-4">
                <Label class="font-semibold">Medical Images</Label>
                <ImageManager :existingImages="medicalImages" @update="handleImageUpdate" />
            </div>

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
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}
</style>