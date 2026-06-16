<template>
    <div class="rounded-lg bg-white p-6 shadow-sm transition-shadow hover:shadow-md">
        <div class="mb-4 flex items-start justify-between">
            <Badge :variant="getTypeVariant(exam.exam_type)">
                {{ getExamTypeLabel(exam.exam_type) }}
            </Badge>
            <Badge :variant="getStatusVariant(exam.status)">
                {{ getStatusLabel(exam.status) }}
            </Badge>
        </div>

        <h3 class="mb-2 text-lg font-semibold text-gray-900">{{ exam.name }}</h3>

        <div class="space-y-2 text-sm text-gray-600">
            <div class="flex items-center gap-2">
                <Calendar class="h-4 w-4" />
                <span>{{ exam.start_date }} - {{ exam.end_date }}</span>
            </div>
            <div class="flex items-center gap-2">
                <Weight class="h-4 w-4" />
                <span>Weightage: {{ exam.weightage }}%</span>
            </div>
            <div class="flex items-center gap-2">
                <GraduationCap class="h-4 w-4" />
                <span>{{ exam.academic_year }}</span>
            </div>
            <div class="flex items-center gap-2">
                <Globe class="h-4 w-4" />
                <span :class="exam.is_published ? 'text-green-600' : 'text-gray-500'">
                    {{ exam.is_published ? 'Published' : 'Not Published' }}
                </span>
            </div>
        </div>

        <div class="mt-6">
            <Button variant="outline" size="sm" @click="$emit('view', exam)" class="w-full">
                <Eye class="mr-2 h-4 w-4" />
                View Details
            </Button>
        </div>
    </div>
</template>

<script setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar, Eye, Globe, GraduationCap, Weight } from 'lucide-vue-next';

defineProps({
    exam: {
        type: Object,
        required: true,
    },
});

defineEmits(['view']);

const getExamTypeLabel = (type) => {
    const labels = {
        unit_test: 'Unit Test',
        midterm: 'Midterm',
        final: 'Final',
        semester: 'Semester',
        annual: 'Annual',
    };
    return labels[type] || type;
};

const getTypeVariant = (type) => {
    const variants = {
        unit_test: 'secondary',
        midterm: 'default',
        final: 'destructive',
        semester: 'outline',
        annual: 'default',
    };
    return variants[type] || 'secondary';
};

const getStatusLabel = (status) => {
    const labels = {
        draft: 'Draft',
        upcoming: 'Upcoming',
        ongoing: 'Ongoing',
        completed: 'Completed',
    };
    return labels[status] || status;
};

const getStatusVariant = (status) => {
    const variants = {
        draft: 'secondary',
        upcoming: 'outline',
        ongoing: 'default',
        completed: 'secondary',
    };
    return variants[status] || 'secondary';
};
</script>
