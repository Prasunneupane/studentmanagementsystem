<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Toaster } from '@/components/ui/sonner';
import { Textarea } from '@/components/ui/textarea';
import { usePermission } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye, Loader2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import 'vue-sonner/style.css';

const { toast } = useToast();

const { can } = usePermission();
// Props
const props = defineProps({
    role: {
        type: Object,
        default: null,
    },
});

const isEdit = computed(() => !!props.role);

// Initialize form
const form = useForm({
    name: props.role?.name || '',
    is_active: props.role?.is_active?.toString() || '1',
    description: props.role?.description || '',
});
console.log(form, 'formdata');

// When DatePicker changes → update form.dob (string: YYYY-MM-DD)

const errors = ref<Record<string, string>>({});
// Submit
const handleSubmit = () => {
    errors.value = {};

    const payload = {
        onSuccess: () => {
            toast.success(isEdit.value ? 'Role updated successfully.' : 'Role added successfully.');

            if (!isEdit.value) {
                form.reset();
                ((form.name = ''), (form.description = ''), (form.is_active = '1'));
            }
        },

        onError: () => {
            const errorMessages = Object.values(form.errors);
            console.log(errorMessages, 'errormessage');

            const msg = errorMessages.length > 0 ? errorMessages[0] : 'Something went wrong.';
            toast.error(msg);
        },
    };

    if (isEdit.value) {
        form.put(route('roles.update', props.role.id), payload);
    } else {
        form.post(route('roles.store'), payload);
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Role' : 'Add Role'" />
    <!-- Hero No 1 -->
    <AppLayout
        :breadcrumbs="[
            { title: 'roles', href: '/roles' },
            { title: isEdit ? 'Edit Role' : 'Add Role', href: '' },
        ]"
    >
        <Toaster position="top-right" />

        <div class="container mx-auto max-w-7xl p-6">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle>{{ isEdit ? 'Edit Role' : 'Add Role' }}</CardTitle>
                        <CardDescription>
                            {{ isEdit ? 'Update Role details.' : 'Add a new role to the system.' }}
                        </CardDescription>
                    </div>
                    <Button v-if="can('roles.viewRole')" as-child>
                        <Link :href="route('roles.index')"> <Eye class="mr-2 h-4 w-4" /> View Roles </Link>
                    </Button>
                </CardHeader>

                <CardContent>
                    <form @submit.prevent="handleSubmit" class="space-y-8">
                        <!-- Row 1 -->
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label>Role Name <span class="text-red-500">*</span></Label>
                                <Input v-model="form.name" placeholder="SuperAdmin, Admin, Account..." />
                                <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label>Description <span class="text-red-500">*</span></Label>
                                <Textarea v-model="form.description" placeholder="Role description..." />
                                <p v-if="form.errors.description" class="text-sm text-red-600">{{ form.errors.description }}</p>
                            </div>

                            <div class="space-y-3">
                                <Label>Is Active <span class="text-red-500">*</span></Label>
                                <RadioGroup v-model="form.is_active" class="flex flex-row gap-8">
                                    <div class="flex items-center space-x-2">
                                        <RadioGroupItem value="1" id="active" />
                                        <Label for="active" class="cursor-pointer font-normal">Active</Label>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <RadioGroupItem value="0" id="inactive" />
                                        <Label for="inactive" class="cursor-pointer font-normal">Inactive</Label>
                                    </div>
                                </RadioGroup>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="flex justify-end gap-4 border-t pt-6" v-if="can('roles.canCreate') || can('roles.canEdit')">
                            <Button type="submit" :disabled="form.processing" class="cursor-pointer">
                                <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                {{ isEdit ? 'Update Role' : 'Add Role' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
