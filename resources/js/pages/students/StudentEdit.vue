<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DatePicker from '@/components/ui/datepicker/DatePicker.vue';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Toaster } from '@/components/ui/sonner';
import { useAcademic } from '@/composables/useAcademic';
import { useLocation } from '@/composables/useLocation';
import { usePermission } from '@/composables/usePermissions';
import { useStudents } from '@/composables/useStudents';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Guardian, Student } from '@/services/studentService';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Loader2, Pencil, Plus, Save, Trash2 } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import 'vue-sonner/style.css';
import CustomSelect from '../CustomSelect.vue';

interface Option {
    value: string;
    label: string;
}

const props = defineProps<{
    student: Student;
    classes: Option[];
    states: Option[];
}>();

const { toast } = useToast();
const { can } = usePermission();
const { updateStudent, getGuardians, createGuardian, updateGuardian, deleteGuardian } = useStudents();
const { states, districts, municipalities, isDistrictLoading, isMunicipalityLoading, fetchDistricts, fetchMunicipalities } = useLocation();
const { classes, sections, isSectionLoading, fetchSections } = useAcademic();

const breadcrumbs = [
    { title: 'View Students', href: '/students' },
    { title: 'Edit Student', href: `/students/${props.student.id}/edit` },
];

const form = ref({
    fName: props.student.first_name || '',
    mName: props.student.middle_name || '',
    lName: props.student.last_name || '',
    email: props.student.email || '',
    phone: props.student.phone || '',
    age: String(props.student.age || ''),
    dateOfBirth: props.student.date_of_birth || '',
    classId: null as Option | null,
    sectionId: null as Option | null,
    joinedDate: props.student.joined_date || '',
    contactNumber: props.student.contact_number || '',
    address: props.student.address || '',
    stateId: null as Option | null,
    districtId: null as Option | null,
    municipalityId: null as Option | null,
    photo: null as File | null,
});

const errors = ref<Record<string, string>>({});
const saving = ref(false);
const initializing = ref(true);
const guardians = ref<Guardian[]>([]);
const guardiansLoading = ref(true);
const guardianDialogOpen = ref(false);
const guardianSaving = ref(false);
const guardianErrors = ref<Record<string, string>>({});
const guardianForm = ref<Guardian>({ id: 0, name: '', relation: '', phone: '', email: '', occupation: '', address: '', is_primary_contact: false });

const dateValue = (value: string) => {
    if (!value) return null;
    const [year, month, day] = value.split('-').map(Number);
    return new Date(year, month - 1, day);
};

const dateOfBirthValue = computed(() => dateValue(form.value.dateOfBirth));
const joinedDateValue = computed(() => dateValue(form.value.joinedDate));
const photoPreview = computed(() => (form.value.photo ? URL.createObjectURL(form.value.photo) : props.student.photo_url));

const formatDate = (date: Date | null | undefined) => (date ? date.toISOString().split('T')[0] : '');

const optionFor = (options: Option[], value?: number) => (value ? options.find((option) => String(option.value) === String(value)) || null : null);

onMounted(async () => {
    form.value.stateId = optionFor(states.value, props.student.state_id);
    form.value.classId = optionFor(classes.value, props.student.class_id);

    try {
        const stateId = String(props.student.state_id || '');
        const districtId = String(props.student.district_id || '');
        const classId = String(props.student.class_id || '');

        if (stateId) await fetchDistricts(stateId);
        if (districtId) await fetchMunicipalities(districtId);
        if (classId) await fetchSections(classId);

        form.value.districtId = optionFor(districts.value, props.student.district_id);
        form.value.municipalityId = optionFor(municipalities.value, props.student.municipality_id);
        form.value.sectionId = optionFor(sections.value, props.student.section_id);
        guardians.value = await getGuardians(props.student.id);
    } catch {
        toast.error('Failed to load student details');
    } finally {
        initializing.value = false;
        guardiansLoading.value = false;
    }
});

watch(
    () => form.value.stateId,
    async (value, previous) => {
        if (initializing.value || value?.value === previous?.value) return;
        form.value.districtId = null;
        form.value.municipalityId = null;
        if (value?.value) await fetchDistricts(String(value.value));
    },
);

watch(
    () => form.value.districtId,
    async (value, previous) => {
        if (initializing.value || value?.value === previous?.value) return;
        form.value.municipalityId = null;
        if (value?.value) await fetchMunicipalities(String(value.value));
    },
);

watch(
    () => form.value.classId,
    async (value, previous) => {
        if (initializing.value || value?.value === previous?.value) return;
        form.value.sectionId = null;
        if (value?.value) await fetchSections(String(value.value));
    },
);

const setPhone = (event: Event) => {
    form.value.phone = (event.target as HTMLInputElement).value.replace(/\D/g, '').slice(0, 10);
};

const validate = () => {
    errors.value = {};
    if (!form.value.fName.trim()) errors.value.fName = 'First name is required';
    if (!form.value.lName.trim()) errors.value.lName = 'Last name is required';
    if (!/^\d{10}$/.test(form.value.phone)) errors.value.phone = 'Phone must be 10 digits';
    if (!form.value.age) errors.value.age = 'Age is required';
    if (!form.value.dateOfBirth) errors.value.dateOfBirth = 'Date of birth is required';
    if (!form.value.classId) errors.value.classId = 'Class is required';
    if (!form.value.joinedDate) errors.value.joinedDate = 'Joined date is required';
    if (!form.value.stateId) errors.value.stateId = 'State is required';
    return !Object.keys(errors.value).length;
};

const submit = async () => {
    if (!validate()) {
        toast.error('Please complete the required fields');
        return;
    }
    saving.value = true;
    const data = new FormData();
    data.append('first_name', form.value.fName);
    data.append('middle_name', form.value.mName);
    data.append('last_name', form.value.lName);
    data.append('email', form.value.email);
    data.append('phone', form.value.phone);
    data.append('age', form.value.age);
    data.append('date_of_birth', form.value.dateOfBirth);
    data.append('class_id', form.value.classId?.value || '');
    data.append('section_id', form.value.sectionId?.value || '');
    data.append('joined_date', form.value.joinedDate);
    data.append('contact_number', form.value.contactNumber);
    data.append('address', form.value.address);
    data.append('state_id', form.value.stateId?.value || '');
    data.append('district_id', form.value.districtId?.value || '');
    data.append('municipality_id', form.value.municipalityId?.value || '');
    if (form.value.photo) data.append('photo', form.value.photo);

    try {
        const result = await updateStudent(props.student.id, data);
        if (result.success) {
            toast.success('Student updated successfully');
            router.visit('/students');
        } else toast.error('Failed to update student');
    } catch {
        toast.error('Failed to update student');
    } finally {
        saving.value = false;
    }
};

const choosePhoto = (event: Event) => {
    form.value.photo = (event.target as HTMLInputElement).files?.[0] || null;
};

const openGuardian = (guardian?: Guardian) => {
    guardianErrors.value = {};
    guardianForm.value = guardian
        ? { ...guardian, is_primary_contact: Boolean(guardian.is_primary_contact) }
        : { id: 0, name: '', relation: '', phone: '', email: '', occupation: '', address: '', is_primary_contact: false };
    guardianDialogOpen.value = true;
};

const saveGuardian = async () => {
    guardianErrors.value = guardianForm.value.name.trim() ? {} : { name: 'Name is required' };
    if (Object.keys(guardianErrors.value).length) return;
    guardianSaving.value = true;
    try {
        const result = guardianForm.value.id
            ? await updateGuardian(guardianForm.value.id, guardianForm.value)
            : await createGuardian(props.student.id, guardianForm.value);
        if (guardianForm.value.id) {
            guardians.value = guardians.value.map((guardian) => (guardian.id === result.id ? result : guardian));
        } else guardians.value.push(result);
        guardianDialogOpen.value = false;
        toast.success('Guardian saved successfully');
    } catch {
        toast.error('Failed to save guardian');
    } finally {
        guardianSaving.value = false;
    }
};

const removeGuardian = async (guardian: Guardian) => {
    if (!window.confirm(`Delete ${guardian.name}?`)) return;
    try {
        if (await deleteGuardian(guardian.id)) {
            guardians.value = guardians.value.filter((item) => item.id !== guardian.id);
            toast.success('Guardian deleted');
        }
    } catch {
        toast.error('Failed to delete guardian');
    }
};
</script>

<template>
    <Head :title="`Edit ${student.first_name} ${student.last_name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />
        <div class="w-full space-y-5 bg-slate-50 p-4 sm:p-6">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <p class="text-sm font-medium text-blue-600">Student management</p>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-950">Edit student profile</h1>
                    <p class="mt-1 text-sm text-slate-500">Update academic, contact, and guardian information in one place.</p>
                </div>
                <Button variant="outline" @click="router.visit('/students')"><ArrowLeft class="mr-2 h-4 w-4" />Back to students</Button>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                    <Card class="overflow-hidden rounded-2xl border-0 shadow-sm">
                        <CardHeader class="border-b bg-gradient-to-r from-slate-950 to-blue-950 text-white">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                                <Avatar class="h-20 w-20 border-2 border-white/70">
                                    <AvatarImage :src="photoPreview ?? '/images/default-avatar.png'" />
                                    <AvatarFallback class="bg-blue-600 text-xl">{{ form.fName[0] }}{{ form.lName[0] }}</AvatarFallback>
                                </Avatar>
                                <div>
                                    <CardTitle class="text-2xl">{{ form.fName }} {{ form.mName }} {{ form.lName }}</CardTitle>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <Badge variant="secondary">ID #{{ student.id }}</Badge>
                                        <Badge variant="outline" class="border-white/40 text-white">{{ student.class_name || 'No class' }}</Badge>
                                    </div>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-6 p-5 sm:p-7">
                            <div>
                                <h2 class="mb-4 text-lg font-semibold text-slate-900">Personal information</h2>
                                <div class="grid gap-4 md:grid-cols-3">
                                    <div><Label>First name *</Label><Input v-model="form.fName" :class="{ 'border-red-500': errors.fName }" /><p v-if="errors.fName" class="text-sm text-red-600">{{ errors.fName }}</p></div>
                                    <div><Label>Middle name</Label><Input v-model="form.mName" /></div>
                                    <div><Label>Last name *</Label><Input v-model="form.lName" :class="{ 'border-red-500': errors.lName }" /><p v-if="errors.lName" class="text-sm text-red-600">{{ errors.lName }}</p></div>
                                    <div><Label>Email</Label><Input v-model="form.email" type="email" /></div>
                                    <div><Label>Phone *</Label><Input v-model="form.phone" maxlength="10" @input="setPhone" :class="{ 'border-red-500': errors.phone }" /><p v-if="errors.phone" class="text-sm text-red-600">{{ errors.phone }}</p></div>
                                    <div><Label>Contact number</Label><Input v-model="form.contactNumber" /></div>
                                    <div><Label>Age *</Label><Input v-model="form.age" type="number" min="1" max="100" /></div>
                                    <div><Label>Date of birth *</Label><DatePicker :model-value="dateOfBirthValue" month-year-selector @update:model-value="form.dateOfBirth = formatDate($event)" /></div>
                                    <div><Label>Joined date *</Label><DatePicker :model-value="joinedDateValue" month-year-selector @update:model-value="form.joinedDate = formatDate($event)" /></div>
                                </div>
                            </div>

                            <div class="border-t pt-6">
                                <h2 class="mb-4 text-lg font-semibold text-slate-900">Academic and address</h2>
                                <div class="grid gap-4 md:grid-cols-3">
                                    <div><Label>Class *</Label><CustomSelect v-model="form.classId" :options="classes" placeholder="Select class" :loading="classes.length === 0" /><p v-if="errors.classId" class="text-sm text-red-600">{{ errors.classId }}</p></div>
                                    <div><Label>Section</Label><CustomSelect v-model="form.sectionId" :options="sections" placeholder="Select section" :loading="isSectionLoading" /></div>
                                    <div><Label>Address</Label><Input v-model="form.address" /></div>
                                    <div><Label>State *</Label><CustomSelect v-model="form.stateId" :options="states" placeholder="Select state" :loading="states.length === 0" /><p v-if="errors.stateId" class="text-sm text-red-600">{{ errors.stateId }}</p></div>
                                    <div><Label>District</Label><CustomSelect v-model="form.districtId" :options="districts" :disabled="!form.stateId" placeholder="Select district" :loading="isDistrictLoading" /></div>
                                    <div><Label>Municipality</Label><CustomSelect v-model="form.municipalityId" :options="municipalities" :disabled="!form.districtId" placeholder="Select municipality" :loading="isMunicipalityLoading" /></div>
                                    <div class="md:col-span-2"><Label>Profile photo</Label><Input type="file" accept="image/*" @change="choosePhoto" /></div>
                                </div>
                            </div>
                            <div class="flex justify-end gap-3 border-t pt-5"><Button type="button" variant="outline" @click="router.visit('/students')">Cancel</Button><Button type="submit" :disabled="saving"><Loader2 v-if="saving" class="mr-2 h-4 w-4 animate-spin" /><Save v-else class="mr-2 h-4 w-4" />{{ saving ? 'Saving...' : 'Save changes' }}</Button></div>
                        </CardContent>
                    </Card>
            </form>

                <Card class="rounded-2xl border-0 shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between border-b"><div><CardTitle>Guardians</CardTitle><p class="mt-1 text-sm text-slate-500">Manage the people connected to this student.</p></div><Button size="sm" @click="openGuardian()"><Plus class="mr-2 h-4 w-4" />Add guardian</Button></CardHeader>
                    <CardContent class="p-0">
                        <div v-if="guardiansLoading" class="flex justify-center p-10"><Loader2 class="h-7 w-7 animate-spin text-blue-600" /></div>
                        <div v-else-if="!guardians.length" class="p-10 text-center text-sm text-slate-500">No guardians added yet.</div>
                        <div v-else class="divide-y">
                            <div v-for="guardian in guardians" :key="guardian.id" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div><div class="flex items-center gap-2 font-medium text-slate-900">{{ guardian.name }}<Badge v-if="guardian.is_primary_contact" class="bg-emerald-100 text-emerald-700">Primary</Badge></div><p class="text-sm text-slate-500">{{ guardian.relation || 'Guardian' }} <span v-if="guardian.phone">· {{ guardian.phone }}</span><span v-if="guardian.email">· {{ guardian.email }}</span></p></div>
                                <div class="flex gap-1"><Button v-if="can('guardians.canEdit')" variant="ghost" size="icon" title="Edit guardian" @click="openGuardian(guardian)"><Pencil class="h-4 w-4" /></Button><Button v-if="can('guardians.canDelete')" variant="ghost" size="icon" class="text-red-600" title="Delete guardian" @click="removeGuardian(guardian)"><Trash2 class="h-4 w-4" /></Button></div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
        </div>
    </AppLayout>

    <Dialog v-model:open="guardianDialogOpen">
        <DialogContent class="sm:max-w-xl"><DialogHeader><DialogTitle>{{ guardianForm.id ? 'Edit guardian' : 'Add guardian' }}</DialogTitle></DialogHeader><form class="space-y-4" @submit.prevent="saveGuardian"><div><Label>Name *</Label><Input v-model="guardianForm.name" /><p v-if="guardianErrors.name" class="text-sm text-red-600">{{ guardianErrors.name }}</p></div><div class="grid gap-4 sm:grid-cols-2"><div><Label>Relation</Label><Input v-model="guardianForm.relation" placeholder="Father, mother..." /></div><div><Label>Phone</Label><Input v-model="guardianForm.phone" /></div><div><Label>Email</Label><Input v-model="guardianForm.email" type="email" /></div><div><Label>Occupation</Label><Input v-model="guardianForm.occupation" /></div></div><div><Label>Address</Label><Input v-model="guardianForm.address" /></div><label class="flex items-center gap-2 text-sm"><input v-model="guardianForm.is_primary_contact" type="checkbox" class="h-4 w-4" />Primary contact</label><DialogFooter><Button type="button" variant="outline" @click="guardianDialogOpen = false">Cancel</Button><Button type="submit" :disabled="guardianSaving"><Loader2 v-if="guardianSaving" class="mr-2 h-4 w-4 animate-spin" />Save guardian</Button></DialogFooter></form></DialogContent>
    </Dialog>
</template>
