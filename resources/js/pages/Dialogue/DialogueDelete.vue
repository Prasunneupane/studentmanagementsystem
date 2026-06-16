<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';

// Props that can be passed from parent
const props = defineProps<{
    modelValue: boolean;
    title: string;
    description?: string;
    itemName?: string;
    itemId?: number | string;
}>();

// Emit events to parent
const emit = defineEmits(['update:modelValue', 'confirm']);

// Local ref for controlling open state
const isOpen = ref(props.modelValue);

// Watch v-model from parent
watch(
    () => props.modelValue,
    (val) => (isOpen.value = val),
);

// Emit update to parent when closed
const close = () => {
    isOpen.value = false;
    emit('update:modelValue', false);
};

// When confirm button clicked
const confirm = () => {
    emit('confirm'); // parent will handle delete action
    close();
};
</script>

<template>
    <AlertDialog v-model:open="isOpen">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>{{ props.title }}</AlertDialogTitle>
                <AlertDialogDescription>
                    <div v-if="props.description">{{ props.description }}</div>
                    <div v-if="props.itemName || props.itemId" class="mt-3 rounded-md border border-red-200 bg-red-50 p-3 text-sm">
                        <strong>{{ props.itemName }}</strong
                        ><br />
                        <span v-if="props.itemId">ID: #{{ props.itemId }}</span>
                    </div>
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel @click="close">Cancel</AlertDialogCancel>
                <AlertDialogAction @click="confirm" class="bg-red-600 hover:bg-red-700"> <Trash2 class="mr-2 h-4 w-4" /> Delete </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
