<script setup lang="ts">
/**
 * ImageDropzone.vue
 * -------------------------------------------------------------
 * Generic drag-and-drop image upload component (dropzone.js style).
 *
 * - Works for single OR multiple images (controlled by `multiple` prop)
 * - Previews render INSIDE the dropzone box as small thumbnails with an X
 * - Also supports showing already-uploaded images (edit forms) via
 *   `existingImages`, which can be removed independently (emits
 *   `remove-existing` so the parent can call an API / mark for deletion)
 *
 * Usage (create form, single image):
 *   <ImageDropzone v-model="form.image" :multiple="false" label="Main image" />
 *
 * Usage (create form, multiple images):
 *   <ImageDropzone v-model="form.images" :multiple="true" :max-files="10" />
 *
 * Usage (edit form, existing + new images):
 *   <ImageDropzone
 *     v-model="form.newImages"
 *     :multiple="true"
 *     :existing-images="existingImages"
 *     @remove-existing="onRemoveExisting"
 *   />
 */
import { ref, computed, watch, onBeforeUnmount } from 'vue'
import { UploadCloud, X, Plus } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import 'vue-sonner/style.css';
import { useToast } from '@/composables/useToast';
import { Toaster } from '@/components/ui/sonner';
const { toast } = useToast();
interface ExistingImage {
  id: string | number
  url: string
}

const props = withDefaults(
  defineProps<{
    /** v-model — the raw File objects the user just picked/dropped */
    modelValue: File[] | File | null
    /** allow more than one image */
    multiple?: boolean
    /** cap on total images (existing + new). undefined = no cap */
    maxFiles?: number
    /** max size per file, in MB */
    maxSizeMb?: number
    /** input accept attribute */
    accept?: string
    /** already-uploaded images, e.g. when editing an event */
    existingImages?: ExistingImage[]
    label?: string
    description?: string
    disabled?: boolean
  }>(),
  {
    multiple: true,
    maxFiles: undefined,
    maxSizeMb: 5,
    accept: 'image/*',
    existingImages: () => [],
    label: '',
    description: '',
    disabled: false,
  }
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: File[] | File | null): void
  (e: 'remove-existing', id: string | number): void
  (e: 'error', message: string): void
}>()

const isDragging = ref(false)
const inputRef = ref<HTMLInputElement | null>(null)

/** Always work internally with an array, regardless of single/multiple mode */
const files = ref<File[]>(
  Array.isArray(props.modelValue) ? props.modelValue : props.modelValue ? [props.modelValue] : []
)

// keep internal array in sync if parent resets modelValue externally (e.g. form.reset())
watch(
  () => props.modelValue,
  (val) => {
    const next = Array.isArray(val) ? val : val ? [val] : []
    if (next.length === 0 && files.value.length !== 0) {
      files.value = []
    }
  }
)

interface Preview {
  url: string
  file: File
}

const previews = ref<Preview[]>([])

function rebuildPreviews() {
  previews.value.forEach((p) => URL.revokeObjectURL(p.url))
  previews.value = files.value.map((file) => ({ file, url: URL.createObjectURL(file) }))
}

onBeforeUnmount(() => {
  previews.value.forEach((p) => URL.revokeObjectURL(p.url))
})

const totalCount = computed(() => props.existingImages.length + files.value.length)

const canAddMore = computed(() => {
  if (totalCount.value === 0) return false // handled by the big empty-state prompt instead
  if (!props.multiple) return false // single mode: clicking the box itself replaces the image
  if (!props.maxFiles) return true
  return totalCount.value < props.maxFiles
})

function emitUpdate() {
  emit('update:modelValue', props.multiple ? files.value : files.value[0] ?? null)
}

function addFiles(fileList: FileList | File[]) {
  if (props.disabled) return

  const incoming = Array.from(fileList).filter((f) => f.type.startsWith('image/'))

  for (const file of incoming) {
    if (props.maxSizeMb && file.size > props.maxSizeMb * 1024 * 1024) {
      // toast.error(`"${file.name}" is larger than ${props.maxSizeMb}MB`)
      emit('error', `"${file.name}" is larger than ${props.maxSizeMb}MB`)
      continue
    }

    if (!props.multiple) {
      files.value = [file]
      rebuildPreviews()
      emitUpdate()
      return
    }

    if (props.maxFiles && totalCount.value >= props.maxFiles) {
      emit('error', `You can only upload up to ${props.maxFiles} images`)
      break
    }

    files.value.push(file)
  }

  if (props.multiple) {
    rebuildPreviews()
    emitUpdate()
  }
}

function removeFile(index: number) {
  files.value.splice(index, 1)
  rebuildPreviews()
  emitUpdate()
}

function onDrop(e: DragEvent) {
  isDragging.value = false
  if (props.disabled) return
  if (e.dataTransfer?.files?.length) addFiles(e.dataTransfer.files)
}

function onInputChange(e: Event) {
  const target = e.target as HTMLInputElement
  if (target.files?.length) addFiles(target.files)
  target.value = '' // allow re-selecting the same file
}

function openFileDialog() {
  if (!props.disabled) inputRef.value?.click()
}
</script>

<template>
  <Toaster />
  <div class="space-y-2">
    <label v-if="label" class="text-sm font-medium leading-none">{{ label }}</label>

    <div
      :class="
        cn(
          'rounded-lg border-2 border-dashed transition-colors cursor-pointer',
          isDragging ? 'border-primary bg-primary/5' : 'border-muted-foreground/25 hover:border-muted-foreground/50',
          disabled && 'pointer-events-none opacity-50'
        )
      "
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="onDrop"
      @click="openFileDialog"
    >
      <!-- Empty state: big centered prompt -->
      <div v-if="totalCount === 0" class="flex flex-col items-center justify-center gap-2 p-6 text-center">
        <UploadCloud class="h-8 w-8 text-muted-foreground" />
        <p class="text-sm text-muted-foreground">
          <span class="font-medium text-foreground">Click to upload</span> or drag and drop
        </p>
        <p v-if="description" class="text-xs text-muted-foreground">{{ description }}</p>
        <p class="text-xs text-muted-foreground">
          PNG, JPG up to {{ maxSizeMb }}MB {{ maxFiles ? `· max ${maxFiles} images` : '' }}
        </p>
      </div>

      <!-- Has images: small thumbnails INSIDE the dropzone box -->
      <div v-else class="flex flex-wrap gap-2 p-3">
        <!-- already-uploaded images (edit mode) -->
        <div
          v-for="img in existingImages"
          :key="`existing-${img.id}`"
          class="group relative h-16 w-16 shrink-0 overflow-hidden rounded-md border"
        >
          <img :src="img.url" class="h-full w-full object-cover" alt="Uploaded image" />
          <button
            type="button"
            class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-destructive text-destructive-foreground shadow"
            @click.stop="emit('remove-existing', img.id)"
          >
            <X class="h-2.5 w-2.5" />
          </button>
        </div>

        <!-- newly dropped/selected files, not yet uploaded -->
        <div
          v-for="(preview, index) in previews"
          :key="preview.url"
          class="group relative h-16 w-16 shrink-0 overflow-hidden rounded-md border"
        >
          <img :src="preview.url" class="h-full w-full object-cover" :alt="preview.file.name" />
          <button
            type="button"
            class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-destructive text-destructive-foreground shadow"
            @click.stop="removeFile(index)"
          >
            <X class="h-2.5 w-2.5" />
          </button>
        </div>

        <!-- compact "add more" tile, same size as thumbnails, only for multiple mode -->
        <div
          v-if="canAddMore"
          class="flex h-16 w-16 shrink-0 flex-col items-center justify-center gap-0.5 rounded-md border-2 border-dashed border-muted-foreground/25 text-muted-foreground hover:border-primary hover:text-primary"
          @click.stop="openFileDialog"
        >
          <Plus class="h-4 w-4" />
          <span class="text-[9px] leading-none">Add</span>
        </div>
      </div>

      <input
        ref="inputRef"
        type="file"
        :accept="accept"
        :multiple="multiple"
        class="hidden"
        @change="onInputChange"
      />
    </div>
  </div>
</template>