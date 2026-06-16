<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Toaster } from '@/components/ui/sonner';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { AlertCircle, Loader2, Save, UserCheck, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import 'vue-sonner/style.css';
import CustomSelect from '../CustomSelect.vue';
// import { Alert, AlertDescription } from '@/components/ui/alert'
import axios from 'axios';

const { toast } = useToast();

interface Option {
    value: string;
    label: string;
}

interface Props {
    classes: Option[];
    teachers: Option[];
    academicYears: Option[];
    currentAcademicYear: Option | null;
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'Class Teachers', href: '/class-teachers' },
    { title: 'Assign Teacher', href: '/class-teachers/create' },
];

const sections = ref<Option[]>([]);
const loadingSections = ref(false);

const form = useForm({
    class_id: '' as string,
    section_id: '' as string,
    teacher_id: '' as string,
    academic_year_id: props.currentAcademicYear?.value || ('' as string),
    is_class_teacher: false,
    is_active: true,
});

// Fetch sections when class changes
watch(
    () => form.class_id,
    async (newClass) => {
        form.section_id = '';
        sections.value = [];

        if (!newClass) return;

        loadingSections.value = true;
        try {
            const response = await axios.get('/class-teacher/sections-by-class', {
                params: { class_id: newClass },
            });
            sections.value = response.data;
        } catch (error) {
            console.error('Failed to fetch sections:', error);
            toast.error('Failed to load sections');
        } finally {
            loadingSections.value = false;
        }
    },
);

// Validation
const errors = computed(() => {
    const errs: Record<string, string> = {};
    if (!form.class_id) errs.class_id = 'Class is required';
    if (!form.section_id) errs.section_id = 'Section is required';
    if (!form.teacher_id) errs.teacher_id = 'Teacher is required';
    if (!form.academic_year_id) errs.academic_year_id = 'Academic year is required';
    return errs;
});

const canSubmit = computed(() => {
    return Object.keys(errors.value).length === 0 && !form.processing;
});

const extractValue = (option: any) => {
    if (!option) return null;
    return typeof option === 'object' && 'value' in option ? option.value : option;
};

const handleSubmit = () => {
    if (!canSubmit.value) {
        toast.error('Please fill all required fields correctly');
        return;
    }
    console.log('RAW FORM:', form);
    console.log('CLASS TEACHER:', form.is_class_teacher, typeof form.is_class_teacher);
    console.log('ACTIVE:', form.is_active, typeof form.is_active);

    form.transform((data) => {
        console.log('TRANSFORM DATA:', data);
        return {
            ...data,
            is_class_teacher: !!data.is_class_teacher,
            is_active: !!data.is_active,
        };
    }).post('/class-teacher/', {
        onSuccess: () => {
            toast.success('Teacher assigned successfully');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            toast.error(firstError as string);
        },
    });
};

const handleCancel = () => {
    router.visit('/class-teacher');
};
</script>

<template>
    <Head title="Assign Teacher to Class" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />

        <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
            <Card class="w-full rounded-2xl shadow-lg">
                <CardHeader class="border-b">
                    <CardTitle class="text-2xl font-bold">Assign Teacher to Class-Section</CardTitle>
                    <p class="mt-1 text-sm text-muted-foreground">Configure teacher assignment for class management</p>
                </CardHeader>

                <CardContent class="pt-6">
                    <form @submit.prevent="handleSubmit" class="space-y-6">
                        <!-- Academic Year -->
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="academic_year_id"> Academic Year <span class="text-red-500">*</span> </Label>
                                <CustomSelect
                                    id="academic_year_id"
                                    v-model="form.academic_year_id"
                                    :options="academicYears"
                                    placeholder="Select Academic Year"
                                    :class="{ 'border-red-500': errors.academic_year_id }"
                                />
                                <p v-if="errors.academic_year_id" class="text-sm text-red-600">
                                    {{ errors.academic_year_id }}
                                </p>
                            </div>
                        </div>

                        <!-- Class and Section -->
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="class_id"> Class <span class="text-red-500">*</span> </Label>
                                <CustomSelect
                                    id="class_id"
                                    v-model="form.class_id"
                                    :options="classes"
                                    placeholder="Select Class"
                                    :class="{ 'border-red-500': errors.class_id }"
                                />
                                <p v-if="errors.class_id" class="text-sm text-red-600">
                                    {{ errors.class_id }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="section_id"> Section <span class="text-red-500">*</span> </Label>
                                <CustomSelect
                                    id="section_id"
                                    v-model="form.section_id"
                                    :options="sections"
                                    :disabled="!form.class_id || loadingSections"
                                    :placeholder="loadingSections ? 'Loading...' : 'Select Section'"
                                    :class="{ 'border-red-500': errors.section_id }"
                                />
                                <p v-if="errors.section_id" class="text-sm text-red-600">
                                    {{ errors.section_id }}
                                </p>
                            </div>
                        </div>

                        <!-- Teacher -->
                        <div class="space-y-2">
                            <Label for="teacher_id"> Teacher <span class="text-red-500">*</span> </Label>
                            <CustomSelect
                                id="teacher_id"
                                v-model="form.teacher_id"
                                :options="teachers"
                                placeholder="Select Teacher"
                                :class="{ 'border-red-500': errors.teacher_id }"
                            />
                            <p v-if="errors.teacher_id" class="text-sm text-red-600">
                                {{ errors.teacher_id }}
                            </p>
                        </div>

                        <!-- Configuration -->
                        <div class="space-y-4 rounded-lg bg-muted/50 p-4">
                            <h3 class="text-sm font-semibold">Assignment Configuration</h3>

                            <div class="space-y-3">
                                <div class="flex items-start space-x-3">
                                    <Checkbox id="is_class_teacher" v-model="form.is_class_teacher" />
                                    <div class="grid gap-1.5 leading-none">
                                        <Label for="is_class_teacher" class="flex cursor-pointer items-center gap-2 font-medium">
                                            <UserCheck class="h-4 w-4" />
                                            Class Teacher
                                        </Label>
                                        <p class="text-sm text-muted-foreground">Mark this teacher as the primary class teacher for this section</p>
                                    </div>
                                </div>

                                <Alert v-if="form.is_class_teacher" variant="default" class="border-blue-200 bg-blue-50">
                                    <AlertCircle class="h-4 w-4 text-blue-600" />
                                    <AlertDescription class="text-blue-800">
                                        Marking as class teacher will automatically unset any existing class teacher for this section.
                                    </AlertDescription>
                                </Alert>

                                <div class="flex items-start space-x-3">
                                    <Checkbox id="is_active" v-model="form.is_active" />
                                    <div class="gap-.5 grid leading-none">
                                        <Label for="is_active" class="cursor-pointer font-medium"> Active Status </Label>
                                        <p class="text-sm text-muted-foreground">Set whether this assignment is currently active</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end gap-3 border-t pt-4">
                            <Button type="button" variant="outline" @click="handleCancel" :disabled="form.processing">
                                <X class="mr-2 h-4 w-4" />
                                Cancel
                            </Button>
                            <Button type="submit" :disabled="!canSubmit">
                                <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                <Save v-else class="mr-2 h-4 w-4" />
                                {{ form.processing ? 'Saving...' : 'Assign Teacher' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
.border-red-500 {
    border-color: rgb(239 68 68) !important;
}
</style>
