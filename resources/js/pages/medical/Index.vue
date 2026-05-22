<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import Alert from '@/components/ui/alert/Alert.vue';
import { ref, watch, onMounted } from 'vue';
import { Info } from 'lucide-vue-next';

import type { Medical } from '@/components/medical/columns'
import { columns } from '@/components/medical/columns'
import DataTable from '@/components/medical/data-table.vue'
import { route } from 'ziggy-js';

const data = ref<Medical[]>([])
const page = usePage();
const flashMessage = ref<string | null>(null);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Medical Re-imbursement',
        href: route('medical'),
    },
];

onMounted(() => {
    data.value = page.props.medical as Medical[]
})

watch(
    () => (page.props.flash as { message?: string })?.message,
    (newMessage) => {
        flashMessage.value = newMessage ?? null;

        if (newMessage) {
            // Auto-dismiss after 5 seconds (5000 ms)
            setTimeout(() => {
                flashMessage.value = null;
            }, 5000);
        }
    },
    { immediate: true }
);


</script>

<template>

    <Head title="Medical Re-imbursement" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="m-4 space-y-4">
            <div class="container py-5 mx-auto">
                <DataTable :columns="columns" :data="data" />
            </div>
        </div>
    </AppLayout>
</template>
