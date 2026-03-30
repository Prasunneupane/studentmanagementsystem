<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Loader2, Plus, Edit, Trash2, Mail, Phone, User } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';

import { useToast } from '@/composables/useToast';

interface Guardian {
  id: number;
  student_id: number;
  name: string;
  relation: string;
  phone: string;
  email?: string;
  occupation?: string;
  address?: string;
  is_primary_contact: boolean;
}

interface Props {
  studentId: number;
  studentName: string;
}

const props = defineProps<Props>();
const { toast } = useToast();

// State
const guardians = ref<Guardian[]>([]);
const loading = ref(false);
const isFormModalOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const selectedGuardian = ref<Guardian | null>(null);
const guardianToDelete = ref<Guardian | null>(null);

// Fetch guardians
const fetchGuardians = async () => {
  loading.value = true;
  try {
    const response = await fetch(`/api/students/${props.studentId}/guardians`);
    if (!response.ok) throw new Error('Failed to fetch guardians');
    const data = await response.json();
    guardians.value = data;
  } catch (error) {
    console.error('Error fetching guardians:', error);
    toast.error('Failed to load guardians');
  } finally {
    loading.value = false;
  }
};

// Open form for adding new guardian
const handleAddGuardian = () => {
  selectedGuardian.value = null;
  isFormModalOpen.value = true;
};

// Open form for editing guardian
const handleEditGuardian = (guardian: Guardian) => {
  selectedGuardian.value = guardian;
  isFormModalOpen.value = true;
};

// Open delete confirmation
const handleDeleteGuardian = (guardian: Guardian) => {
  guardianToDelete.value = guardian;
  isDeleteDialogOpen.value = true;
};

// Handle successful save (add or update)
const handleGuardianSaved = (guardian: Guardian) => {
  if (selectedGuardian.value) {
    // Update existing guardian
    const index = guardians.value.findIndex(g => g.id === guardian.id);
    if (index !== -1) {
      guardians.value[index] = guardian;
    }
    toast.success('Guardian updated successfully');
  } else {
    // Add new guardian
    guardians.value.push(guardian);
    toast.success('Guardian added successfully');
  }
  isFormModalOpen.value = false;
};

// Handle successful delete
const handleGuardianDeleted = (guardianId: number) => {
  guardians.value = guardians.value.filter(g => g.id !== guardianId);
  isDeleteDialogOpen.value = false;
  guardianToDelete.value = null;
  toast.success('Guardian deleted successfully');
};

// Load guardians on mount
onMounted(() => {
  fetchGuardians();
});
</script>

<template>
  <div class="space-y-4">
    <!-- Header with Add Button -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-semibold text-gray-900">Guardian Information</h3>
        <p class="text-sm text-gray-600 mt-1">Manage guardians for {{ studentName }}</p>
      </div>
      <Button @click="handleAddGuardian" size="sm" class="gap-2">
        <Plus class="h-4 w-4" />
        Add Guardian
      </Button>
    </div>

    <!-- Guardians Table -->
    <Card>
      <CardContent class="p-0">
        <div class="overflow-x-auto">
          <Table>
            <!-- Fixed Header -->
            <TableHeader>
              <TableRow>
                <TableHead class="w-[200px]">Name</TableHead>
                <TableHead class="w-[120px]">Relation</TableHead>
                <TableHead class="w-[150px]">Phone</TableHead>
                <TableHead class="w-[200px]">Email</TableHead>
                <TableHead class="w-[150px]">Occupation</TableHead>
                <TableHead class="w-[100px] text-center">Primary</TableHead>
                <TableHead class="w-[120px] text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>

            <!-- Table Body -->
            <TableBody>
              <!-- Loading State -->
              <TableRow v-if="loading">
                <TableCell colspan="7" class="h-32 text-center">
                  <div class="flex flex-col items-center justify-center gap-2">
                    <Loader2 class="h-8 w-8 animate-spin text-gray-400" />
                    <p class="text-sm text-gray-600">Loading guardians...</p>
                  </div>
                </TableCell>
              </TableRow>

              <!-- No Data State -->
              <TableRow v-else-if="guardians.length === 0">
                <TableCell colspan="7" class="h-32 text-center">
                  <div class="flex flex-col items-center justify-center gap-2">
                    <User class="h-12 w-12 text-gray-300" />
                    <p class="text-sm font-medium text-gray-900">No guardians available</p>
                    <p class="text-xs text-gray-600">Add a guardian to get started</p>
                  </div>
                </TableCell>
              </TableRow>

              <!-- Data Rows -->
              <TableRow v-else v-for="guardian in guardians" :key="guardian.id" class="hover:bg-gray-50">
                <TableCell class="font-medium">
                  <div class="flex items-center gap-2">
                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                      <User class="h-4 w-4 text-blue-600" />
                    </div>
                    {{ guardian.name }}
                  </div>
                </TableCell>
                <TableCell>
                  <Badge variant="outline" class="capitalize">
                    {{ guardian.relation }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <div class="flex items-center gap-1 text-sm">
                    <Phone class="h-3 w-3 text-gray-400" />
                    {{ guardian.phone }}
                  </div>
                </TableCell>
                <TableCell>
                  <div v-if="guardian.email" class="flex items-center gap-1 text-sm lowercase">
                    <Mail class="h-3 w-3 text-gray-400" />
                    {{ guardian.email }}
                  </div>
                  <span v-else class="text-gray-400 text-sm">—</span>
                </TableCell>
                <TableCell>
                  <span class="text-sm">{{ guardian.occupation || '—' }}</span>
                </TableCell>
                <TableCell class="text-center">
                  <Badge v-if="guardian.is_primary_contact" variant="default" class="text-xs">
                    Primary
                  </Badge>
                  <span v-else class="text-gray-400 text-sm">—</span>
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex items-center justify-end gap-1">
                    <Button
                      variant="ghost"
                      size="sm"
                      class="h-8 w-8 p-0"
                      @click="handleEditGuardian(guardian)"
                      title="Edit Guardian"
                    >
                      <Edit class="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="sm"
                      class="h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50"
                      @click="handleDeleteGuardian(guardian)"
                      title="Delete Guardian"
                    >
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </CardContent>
    </Card>

    <!-- Guardian Form Modal -->
    <GuardianFormModal
      v-model:open="isFormModalOpen"
      :student-id="studentId"
      :guardian="selectedGuardian"
      @saved="handleGuardianSaved"
    />

    <!-- Delete Confirmation Dialog -->
    <GuardianDeleteDialog
      v-model:open="isDeleteDialogOpen"
      :guardian="guardianToDelete"
      @deleted="handleGuardianDeleted"
    />
  </div>
</template>