<script setup lang="ts">
import { Inbox } from 'lucide-vue-next';
import { ref, defineProps, defineEmits, watch, onBeforeUnmount } from 'vue';
import Label from '../ui/label/Label.vue';
import Input from '../ui/input/Input.vue';
import Button from '../ui/button/Button.vue';

interface MedicalImage {
    id: number;
    image_path: string;
}

const props = defineProps<{
    existingImages: MedicalImage[];
}>();

const emits = defineEmits<{
    (e: 'update', payload: {
        newImages: File[];
        deletedImages: number[];
        replacedImages: { id: number, file: File }[];
    }): void;
}>();

// State
const medicalImages = ref<MedicalImage[]>([...props.existingImages]);
const newImages = ref<File[]>([]);
const newImagePreviews = ref<string[]>([]);
const deletedImages = ref<number[]>([]);
const replacedImages = ref<{ id: number, file: File }[]>([]);

// Modal state
const showModal = ref(false);
const modalImageSrc = ref('');

// Add new images
const onNewImages = (e: Event) => {
    const files = (e.target as HTMLInputElement).files;
    if (!files) return;

    Array.from(files).forEach((file) => {
        newImages.value.push(file);
        newImagePreviews.value.push(window.URL.createObjectURL(file));
    });

    emitUpdate();
};

// Delete existing image
const deleteImage = (id: number) => {
    deletedImages.value.push(id);
    const index = medicalImages.value.findIndex(img => img.id === id);
    if (index !== -1) medicalImages.value.splice(index, 1);
    emitUpdate();
};

// Replace existing image
const replaceImage = (e: Event, id: number) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;

    const existIndex = replacedImages.value.findIndex(r => r.id === id);
    if (existIndex !== -1) replacedImages.value[existIndex].file = file;
    else replacedImages.value.push({ id, file });

    emitUpdate();
};

// Emit changes to parent
const emitUpdate = () => {
    emits('update', {
        newImages: newImages.value,
        deletedImages: deletedImages.value,
        replacedImages: replacedImages.value
    });
};

// Open modal with image src
const openModal = (src: string) => {
    modalImageSrc.value = src;
    showModal.value = true;
};

// Close modal
const closeModal = () => {
    showModal.value = false;
    modalImageSrc.value = '';
};

watch(newImages, (files, prev) => {
    prev?.forEach(() => {
        const url = newImagePreviews.value.shift();
        if (url) window.URL.revokeObjectURL(url);
    });
});

onBeforeUnmount(() => {
    newImagePreviews.value.forEach((url) => window.URL.revokeObjectURL(url));
});


</script>

<template>
    <div class="space-y-6">

        <!-- Existing Images Section -->
        <section v-if="medicalImages.length">
            <Label class="font-semibold mb-2 block">Existing Images</Label>
            <div class="flex flex-wrap gap-4">
                <div v-for="img in medicalImages" :key="img.id"
                    class="relative w-32 h-32 rounded overflow-hidden border cursor-pointer"
                    @click="openModal(`/storage/${img.image_path}`)">
                    <img :src="`/storage/${img.image_path}`" class="w-full h-full object-cover" />

                    <!-- Delete Button -->
                    <Button @click.stop.prevent="deleteImage(img.id)"
                        class="absolute top-1 right-1 bg-red-500 text-white rounded p-1 text-xs shadow">
                        ✕
                    </Button>

                    <!-- Replace File Input -->
                    <input type="file" accept="image/*"
                        class="absolute bottom-1 left-1 w-6 h-6 opacity-0 cursor-pointer"
                        @change="e => replaceImage(e, img.id)" />
                    <div class="absolute bottom-1 left-1 text-xs bg-white bg-opacity-70 px-1 rounded">Replace</div>
                </div>
            </div>
        </section>

        <!-- Add New Images Section -->
        <section>
            <Label class="font-semibold mb-2 block">Add New Images</Label>
            <input type="file" multiple accept="image/*" @change="onNewImages"
                class="block w-full border rounded p-2" />

            <!-- Preview New Images -->
            <div class="flex gap-3 mt-3 flex-wrap">
                <div v-for="(src, i) in newImagePreviews" :key="i"
                    class="w-24 h-24 rounded overflow-hidden border cursor-pointer" @click="openModal(src)">
                    <img :src="src" class="w-full h-full object-cover" />
                </div>
            </div>
        </section>

        <!-- Modal for full image view -->
        <transition name="fade">
            <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50"
                @click.self="closeModal">

                <!-- ShadCN Card wrapper -->
                <div
                    class="relative bg-white rounded-xl shadow-xl overflow-hidden max-w-3xl w-full max-h-[90vh] p-4 transition-transform transform scale-95 animate-fade-in">

                    <!-- Close Button -->
                    <button @click="closeModal"
                        class="absolute top-2 right-2 text-gray-700 text-2xl font-bold bg-white rounded-full w-8 h-8 flex items-center justify-center shadow hover:bg-gray-100">
                        &times;
                    </button>

                    <!-- Modal Image -->
                    <img :src="modalImageSrc" class="w-full h-auto rounded-lg object-contain" />

                </div>
            </div>
        </transition>

    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

@keyframes fade-in {
    from {
        transform: scale(0.95);
        opacity: 0;
    }

    to {
        transform: scale(1);
        opacity: 1;
    }
}

.animate-fade-in {
    animation: fade-in 0.25s ease forwards;
}
</style>