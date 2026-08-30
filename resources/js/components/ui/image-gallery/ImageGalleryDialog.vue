<script setup lang="ts">
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';
import type { EventGalleryImage } from '@/composables/fetchData';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    open: boolean;
    images: EventGalleryImage[];
    startIndex?: number;
}>();

const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const currentIndex = ref(props.startIndex ?? 0);

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) currentIndex.value = props.startIndex ?? 0;
    },
);

const next = () => {
    currentIndex.value = (currentIndex.value + 1) % props.images.length;
};
const prev = () => {
    currentIndex.value = (currentIndex.value - 1 + props.images.length) % props.images.length;
};

const close = (value: boolean) => emit('update:open', value);
</script>

<template>
    <Dialog :open="open" @update:open="close">
        <DialogContent class="max-w-3xl">
            <DialogTitle class="sr-only">Event Image Gallery</DialogTitle>

            <div v-if="images.length" class="flex flex-col gap-4">
                <!-- Main image -->
                <div class="relative flex items-center justify-center rounded-lg bg-black/5">
                    <button
                        v-if="images.length > 1"
                        class="absolute left-2 z-10 rounded-full bg-black/50 p-2 text-white hover:bg-black/70"
                        @click="prev"
                    >
                        <ChevronLeft class="h-5 w-5" />
                    </button>

                    <img :src="images[currentIndex].url" class="max-h-[60vh] w-full rounded-lg object-contain" />

                    <button
                        v-if="images.length > 1"
                        class="absolute right-2 z-10 rounded-full bg-black/50 p-2 text-white hover:bg-black/70"
                        @click="next"
                    >
                        <ChevronRight class="h-5 w-5" />
                    </button>
                </div>

                <!-- Thumbnail strip -->
                <div v-if="images.length > 1" class="flex gap-2 overflow-x-auto">
                    <img
                        v-for="(img, idx) in images"
                        :key="img.id"
                        :src="img.url"
                        class="h-16 w-16 flex-shrink-0 cursor-pointer rounded-md object-cover ring-2 transition"
                        :class="idx === currentIndex ? 'ring-primary' : 'ring-transparent opacity-70 hover:opacity-100'"
                        @click="currentIndex = idx"
                    />
                </div>

                <p class="text-center text-xs text-muted-foreground">{{ currentIndex + 1 }} / {{ images.length }}</p>
            </div>
        </DialogContent>
    </Dialog>
</template>