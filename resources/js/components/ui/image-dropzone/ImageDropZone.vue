<script setup lang="ts">
/**
 * ImageDropzone.vue
 * -------------------------------------------------------------
 * Generic drag-and-drop image upload component (dropzone.js style).
 *
 * - Works for single OR multiple images (controlled by `multiple` prop)
 * - Previews render INSIDE the dropzone box as small thumbnails with an X
 * - Also supports showing already-uploaded images (edit forms) via
 *   `existingImages`, which are kept separate from pending File objects.
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
 *     :remove-existing-url="(id) => route('events.gallery.destroy', id)"
 *   />
 */
import { ref, computed, watch, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
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

type ModelValue = File[] | File | ExistingImage[] | ExistingImage | string[] | string | null

const props = withDefaults(
  defineProps<{
    /** v-model — raw File objects, URL strings, or existing image objects */
    modelValue: ModelValue
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
    /** URL to call when a newly selected file is removed */
    removeUrl?: string
    /** Build the URL used to remove an already-uploaded image */
    removeExistingUrl?: (id: string | number) => string
    /** HTTP method used for the remove request */
    removeMethod?: 'DELETE' | 'POST' | 'PUT' | 'PATCH'
    /** Optional custom delete handler; receives the item being removed */
    onRemove?: (payload: { index: number; item: File | ExistingImage; url?: string }) => Promise<unknown> | unknown
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
    removeUrl: undefined,
    removeExistingUrl: undefined,
    removeMethod: 'DELETE',
    onRemove: undefined,
    label: '',
    description: '',
    disabled: false,
  }
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: ModelValue): void
  (e: 'remove-existing', id: string | number): void
  (e: 'error', message: string): void
}>()

const isDragging = ref(false)
const inputRef = ref<HTMLInputElement | null>(null)

function normalizeExistingImages(value: ModelValue): ExistingImage[] {
  const result: ExistingImage[] = []

  if (Array.isArray(value)) {
    for (const item of value) {
      if (typeof item === 'string' && item.trim()) {
        result.push({ id: item, url: item })
        continue
      }

      if (item && typeof item === 'object' && 'url' in item && typeof item.url === 'string' && item.url.trim()) {
        result.push({
          id: 'id' in item && item.id != null ? String(item.id) : item.url,
          url: item.url,
        })
      }
    }

    return result
  }

  if (typeof value === 'string' && value.trim()) {
    return [{ id: value, url: value }]
  }

  if (value && typeof value === 'object' && 'url' in value && typeof value.url === 'string' && value.url.trim()) {
    return [{ id: 'id' in value && value.id != null ? String(value.id) : value.url, url: value.url }]
  }

  return result
}

/** Always work internally with an array, regardless of single/multiple mode */
const files = ref<File[]>([])

interface Preview {
  url: string
  file: File
}

const previews = ref<Preview[]>([])

function rebuildPreviews() {
  previews.value.forEach((p) => URL.revokeObjectURL(p.url))
  previews.value = files.value.map((file) => ({ file, url: URL.createObjectURL(file) }))
}

watch(
  () => props.modelValue,
  (val) => {
    const nextFiles = Array.isArray(val)
      ? val.filter((item): item is File => item instanceof File)
      : val instanceof File
        ? [val]
        : []

    // keep the file list synced with the parent payload, but keep URL previews separate
    files.value = nextFiles
    rebuildPreviews()
  },
  { immediate: true }
)

onBeforeUnmount(() => {
  previews.value.forEach((p) => URL.revokeObjectURL(p.url))
})

const modelExistingImages = computed(() => normalizeExistingImages(props.modelValue))
const totalExistingImages = computed(() => {
  const map = new Map<string, ExistingImage>()

  for (const image of [...props.existingImages, ...modelExistingImages.value]) {
    map.set(String(image.id), image)
  }

  return [...map.values()]
})

const removedExistingIds = ref<Set<string>>(new Set())
const visibleExistingImages = computed(() =>
  totalExistingImages.value.filter((image) => !removedExistingIds.value.has(String(image.id)))
)

const totalCount = computed(() => visibleExistingImages.value.length + files.value.length)

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

async function removeFile(index: number) {
  const item = files.value[index]
  if (!item) return

  files.value.splice(index, 1)
  rebuildPreviews()
  emitUpdate()
}

async function removeExistingImage(image: ExistingImage) {
  if (props.disabled) return

  try {
    if (props.removeExistingUrl) {
      await new Promise<void>((resolve, reject) => {
        router.delete(props.removeExistingUrl!(image.id), {
          preserveScroll: true,
          onSuccess: () => resolve(),
          onError: (errors) => {
            console.error(errors)
            reject(new Error('Delete request failed'))
          },
        })
      })
    } else {
      emit('remove-existing', image.id)
    }

    removedExistingIds.value.add(String(image.id))
  } catch (error) {
    console.error('Failed to delete existing image:', error)
    emit('error', 'Failed to delete image. Please try again.')
  }
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
          v-for="img in visibleExistingImages"
          :key="`existing-${img.id}`"
          class="group relative h-16 w-16 shrink-0 overflow-visible rounded-md border"
        >
          <img :src="img.url" class="h-full w-full object-cover" alt="Uploaded image" />
          <button
            v-if="multiple"
            type="button"
            class="absolute -right-2 -top-2 z-10 flex h-5 w-5 items-center justify-center rounded-full bg-destructive text-destructive-foreground shadow-md ring-2 ring-background"
            @click.stop="removeExistingImage(img)"
          >
            <X class="h-3 w-3" />
          </button>
        </div>

        <!-- newly dropped/selected files, not yet uploaded -->
        <div
          v-for="(preview, index) in previews"
          :key="preview.url"
          class="group relative h-16 w-16 shrink-0 overflow-visible rounded-md border"
        >
          <img :src="preview.url" class="h-full w-full object-cover" :alt="preview.file.name" />
          <button
            v-if="multiple"
            type="button"
            class="absolute -right-2 -top-2 z-10 flex h-5 w-5 items-center justify-center rounded-full bg-destructive text-destructive-foreground shadow-md ring-2 ring-background"
            @click.stop="removeFile(index)"
          >
            <X class="h-3 w-3" />
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