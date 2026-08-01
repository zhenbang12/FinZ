<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div>
        <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
          SmartSplit
        </h2>
        <p class="text-xs sm:text-sm text-slate-500 mt-1 font-normal">Scan or upload receipt images to extract line items and split bills with pro-rata tax math.</p>
      </div>

      <!-- Dropzone Card -->
      <div class="minimal-card p-6 sm:p-8 space-y-6">
        <form @submit.prevent="submitUpload" class="space-y-6">
          <div
            @dragover.prevent="isDragging = true"
            @dragenter.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
            :class="[
              isDragging ? 'border-indigo-600 bg-indigo-50/60 ring-2 ring-indigo-500/20' : 'border-slate-300 bg-slate-50',
              'border-2 border-dashed rounded-2xl p-6 sm:p-10 text-center transition-all'
            ]"
          >
            <!-- Camera Direct Input (forces mobile camera) -->
            <input
              ref="cameraInputRef"
              type="file"
              accept="image/*"
              capture="environment"
              class="hidden"
              @change="handleFileSelected"
            />

            <!-- Gallery/File Input (opens photo library/files) -->
            <input
              ref="galleryInputRef"
              type="file"
              accept="image/*"
              class="hidden"
              @change="handleFileSelected"
            />

            <div class="w-16 h-16 rounded-full bg-slate-900 text-white flex items-center justify-center mx-auto mb-4">
              <CameraIcon class="w-8 h-8 text-white" />
            </div>

            <h3 class="text-lg font-bold text-slate-900 mb-1">
              {{ previewUrl ? 'Image Selected (Replace below)' : 'Scan or Upload Receipt' }}
            </h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto font-medium mb-6">
              Powered by <span class="text-slate-900 font-bold">Google Gemini</span>. Take a photo directly or choose from gallery.
            </p>

            <!-- Dual Action Selection Buttons for Mobile & Desktop -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 max-w-md mx-auto">
              <button
                type="button"
                @click="triggerCamera"
                class="w-full sm:w-auto px-5 py-2.5 rounded-full bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-sm flex items-center justify-center gap-2 transition-all active:scale-95"
              >
                <CameraIcon class="w-4 h-4 text-white" />
                <span>Take Photo (Camera)</span>
              </button>

              <button
                type="button"
                @click="triggerGallery"
                class="w-full sm:w-auto px-5 py-2.5 rounded-full bg-white hover:bg-slate-100 text-slate-900 border border-slate-300 font-bold text-xs shadow-sm flex items-center justify-center gap-2 transition-all active:scale-95"
              >
                <UploadIcon class="w-4 h-4 text-slate-700" />
                <span>Upload from Gallery</span>
              </button>
            </div>

            <!-- Preview Image if selected -->
            <div v-if="previewUrl" class="mt-6 max-w-xs mx-auto overflow-hidden rounded-xl border border-slate-300 shadow-md">
              <img :src="previewUrl" alt="Receipt preview" class="w-full max-h-48 object-cover" />
            </div>
          </div>

          <!-- Submit Action Button -->
          <div class="flex items-center justify-center">
            <button
              type="submit"
              :disabled="!selectedFile || isProcessing"
              class="minimal-btn-primary w-full sm:w-auto px-8 py-3.5 text-sm shadow-md disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center space-x-2"
            >
              <SparklesIcon class="w-4 h-4 text-white animate-spin-slow" />
              <span>{{ isProcessing ? 'Google Gemini AI Vision Parsing...' : 'Process Receipt with SmartSplit' }}</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Scanned Receipts History -->
      <div class="minimal-card p-6 space-y-4">
        <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
          <ReceiptIcon class="w-5 h-5 text-slate-700" />
          <span>Scanned Receipts History</span>
        </h3>

        <div v-if="receipts.length === 0" class="text-center py-8 text-slate-400 text-sm font-medium">
          No receipts scanned yet. Use the camera tool above to scan your first receipt.
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="rcpt in receipts"
            :key="rcpt.id"
            class="minimal-card minimal-card-hover p-5 flex flex-col justify-between space-y-3"
          >
            <div class="flex items-start justify-between">
              <div>
                <h4 class="font-bold text-base text-slate-900 truncate max-w-[180px]">
                  {{ rcpt.merchant_name || 'Receipt #' + rcpt.id }}
                </h4>
                <span class="text-xs text-slate-400 block mt-0.5">{{ formatDate(rcpt.created_at) }}</span>
              </div>
              <span
                :class="[
                  rcpt.status === 'claimed' ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-slate-100 text-slate-800 border-slate-200',
                  'px-2.5 py-0.5 rounded-full text-[9px] font-bold border uppercase tracking-wider'
                ]"
              >
                {{ rcpt.status }}
              </span>
            </div>

            <div class="space-y-1">
              <div class="flex justify-between text-xs text-slate-500 font-medium">
                <span>Total Amount:</span>
                <span class="font-bold text-slate-900">{{ formatCurrency(rcpt.total_amount) }}</span>
              </div>
              <div class="flex justify-between text-xs text-slate-500 font-medium">
                <span>Items extracted:</span>
                <span class="font-semibold text-slate-700">{{ rcpt.items ? rcpt.items.length : 0 }} items</span>
              </div>
            </div>

            <Link
              :href="`/receipts/${rcpt.id}`"
              class="minimal-btn-secondary w-full py-2 font-bold text-xs text-center block transition-all"
            >
              Split Bill & Claim Items →
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatCurrency, formatDate } from '@/Utils/formatters';
import {
  Camera as CameraIcon,
  Upload as UploadIcon,
  Sparkles as SparklesIcon,
  Receipt as ReceiptIcon,
} from 'lucide-vue-next';

const props = defineProps({
  receipts: { type: Array, default: () => [] },
});

const cameraInputRef = ref(null);
const galleryInputRef = ref(null);
const selectedFile = ref(null);
const previewUrl = ref(null);
const isProcessing = ref(false);
const isDragging = ref(false);

const form = useForm({
  image: null,
});

const triggerCamera = () => {
  if (cameraInputRef.value) {
    cameraInputRef.value.click();
  }
};

const triggerGallery = () => {
  if (galleryInputRef.value) {
    galleryInputRef.value.click();
  }
};

const setFile = (file) => {
  if (file && file.type.startsWith('image/')) {
    selectedFile.value = file;
    form.image = file;
    previewUrl.value = URL.createObjectURL(file);
  }
};

const handleFileSelected = (e) => {
  const file = e.target.files[0];
  setFile(file);
};

const handleDrop = (e) => {
  isDragging.value = false;
  const file = e.dataTransfer.files[0];
  setFile(file);
};

const submitUpload = () => {
  if (!form.image) return;
  isProcessing.value = true;
  form.post('/receipts/upload', {
    onFinish: () => {
      isProcessing.value = false;
    },
  });
};
</script>
