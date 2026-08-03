<template>
  <AppLayout>
    <div class="space-y-6 max-w-5xl mx-auto pb-12">
      <!-- Page Header -->
      <div>
        <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-950 flex items-center gap-2">
          <span>SmartSplit Receipt Parser</span>
        </h2>
        <p class="text-xs sm:text-sm text-slate-600 mt-1 font-bold">Scan or upload receipt images to extract line items and split bills with pro-rata tax math.</p>
      </div>

      <!-- Neo-Brutalist Dropzone Card -->
      <div class="p-6 sm:p-8 rounded-3xl bg-amber-300 border-3 border-slate-950 shadow-[6px_6px_0px_#0f172a] space-y-6 text-slate-950">
        <form @submit.prevent="submitUpload" class="space-y-6">
          <div
            @dragover.prevent="isDragging = true"
            @dragenter.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
            :class="[
              isDragging ? 'bg-amber-400 scale-[1.01]' : 'bg-white',
              'border-3 border-dashed border-slate-950 rounded-2xl p-6 sm:p-10 text-center transition-all shadow-[4px_4px_0px_#0f172a]'
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

            <div class="w-16 h-16 rounded-2xl bg-slate-950 text-amber-300 flex items-center justify-center mx-auto mb-4 border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a]">
              <CameraIcon class="w-8 h-8 text-amber-300" />
            </div>

            <h3 class="text-xl font-black text-slate-950 mb-1">
              {{ previewUrl ? 'Image Selected (Replace below)' : 'Scan or Upload Receipt' }}
            </h3>
            <p class="text-xs text-slate-800 max-w-sm mx-auto font-bold mb-6">
              Powered by <span class="text-slate-950 font-black">Google Gemini AI</span>. Take a photo directly or choose from gallery.
            </p>

            <!-- Dual Action Selection Buttons for Mobile & Desktop -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 max-w-md mx-auto">
              <button
                type="button"
                @click="triggerCamera"
                class="w-full sm:w-auto px-5 py-3 rounded-2xl bg-slate-950 hover:bg-slate-900 text-white font-black text-xs border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a] flex items-center justify-center gap-2 transition-all active:translate-x-0.5 active:translate-y-0.5"
              >
                <CameraIcon class="w-4 h-4 text-amber-400" />
                <span>Take Photo (Camera)</span>
              </button>

              <button
                type="button"
                @click="triggerGallery"
                class="w-full sm:w-auto px-5 py-3 rounded-2xl bg-white hover:bg-slate-100 text-slate-950 border-2 border-slate-950 font-black text-xs shadow-[3px_3px_0px_#0f172a] flex items-center justify-center gap-2 transition-all active:translate-x-0.5 active:translate-y-0.5"
              >
                <UploadIcon class="w-4 h-4 text-slate-950" />
                <span>Upload from Gallery</span>
              </button>
            </div>

            <!-- Preview Image if selected -->
            <div v-if="previewUrl" class="mt-6 max-w-xs mx-auto overflow-hidden rounded-2xl border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a]">
              <img :src="previewUrl" alt="Receipt preview" class="w-full max-h-48 object-cover" />
            </div>
          </div>

          <!-- Submit Action Button -->
          <div class="flex items-center justify-center">
            <button
              type="submit"
              :disabled="!selectedFile || isProcessing"
              class="w-full sm:w-auto px-8 py-3.5 rounded-2xl bg-slate-950 text-amber-300 font-black text-sm border-2 border-slate-950 shadow-[4px_4px_0px_#0f172a] disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center space-x-2 active:translate-x-0.5 active:translate-y-0.5 transition-all"
            >
              <SparklesIcon class="w-4 h-4 text-amber-400 animate-spin-slow" />
              <span>{{ isProcessing ? 'Google Gemini AI Vision Parsing...' : 'Process Receipt with SmartSplit' }}</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Scanned Receipts History -->
      <div class="p-6 rounded-3xl bg-white border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] space-y-4">
        <h3 class="text-lg font-black text-slate-950 flex items-center gap-2">
          <ReceiptIcon class="w-5 h-5 text-slate-950" />
          <span>Scanned Receipts History</span>
        </h3>

        <div v-if="receipts.length === 0" class="text-center py-8 text-slate-500 text-sm font-bold">
          No receipts scanned yet. Use the camera tool above to scan your first receipt.
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="rcpt in receipts"
            :key="rcpt.id"
            class="p-5 rounded-3xl bg-slate-50 border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] flex flex-col justify-between space-y-3 hover:-translate-y-0.5 transition-all"
          >
            <div class="flex items-start justify-between">
              <div>
                <h4 class="font-black text-base text-slate-950 truncate max-w-[180px]">
                  {{ rcpt.merchant_name || 'Receipt #' + rcpt.id }}
                </h4>
                <span class="text-xs text-slate-600 font-bold block mt-0.5">{{ formatDate(rcpt.created_at) }}</span>
              </div>
              <span
                :class="[
                  rcpt.status === 'claimed' ? 'bg-emerald-400 text-slate-950 border-slate-950' : 'bg-amber-300 text-slate-950 border-slate-950',
                  'px-2.5 py-0.5 rounded-full text-[9px] font-black border uppercase tracking-wider'
                ]"
              >
                {{ rcpt.status }}
              </span>
            </div>

            <div class="space-y-1 pt-1 border-t-2 border-slate-950/10">
              <div class="flex justify-between text-xs text-slate-600 font-bold">
                <span>Total Amount:</span>
                <span class="font-black font-mono text-slate-950">{{ formatCurrency(rcpt.total_amount) }}</span>
              </div>
              <div class="flex justify-between text-xs text-slate-600 font-bold">
                <span>Items extracted:</span>
                <span class="font-black text-slate-950">{{ rcpt.items ? rcpt.items.length : 0 }} items</span>
              </div>
            </div>

            <div class="flex items-center space-x-2 pt-1">
              <Link
                :href="`/receipts/${rcpt.id}`"
                class="flex-1 py-2 rounded-2xl bg-slate-950 text-amber-300 font-black text-xs text-center border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a] hover:bg-slate-900 transition-all block"
              >
                Split Bill & Claim →
              </Link>
              <button
                @click="confirmDeleteReceipt(rcpt)"
                class="p-2 text-white bg-rose-500 rounded-2xl border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a] hover:bg-rose-600 transition-colors shrink-0"
                title="Delete Receipt"
              >
                <Trash2Icon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatCurrency, formatDate } from '@/Utils/formatters';
import {
  Camera as CameraIcon,
  Upload as UploadIcon,
  Sparkles as SparklesIcon,
  Receipt as ReceiptIcon,
  Trash2 as Trash2Icon,
} from 'lucide-vue-next';

const props = defineProps({
  receipts: { type: Array, default: () => [] },
});

const confirmDeleteReceipt = (receipt) => {
  if (confirm(`Delete receipt from "${receipt.merchant_name || 'Receipt #' + receipt.id}"?`)) {
    router.delete(`/receipts/${receipt.id}`);
  }
};

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

const compressImage = (file, maxWidth = 1200, quality = 0.75) => {
  return new Promise((resolve) => {
    if (!file || !file.type.startsWith('image/') || file.size < 150 * 1024) {
      resolve(file);
      return;
    }

    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = (event) => {
      const img = new Image();
      img.src = event.target.result;
      img.onload = () => {
        let width = img.width;
        let height = img.height;

        if (width > maxWidth) {
          height = Math.round((height * maxWidth) / width);
          width = maxWidth;
        }

        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;

        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, width, height);

        canvas.toBlob(
          (blob) => {
            if (!blob) {
              resolve(file);
              return;
            }
            const compressedFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".webp", {
              type: 'image/webp',
              lastModified: Date.now(),
            });
            resolve(compressedFile);
          },
          'image/webp',
          quality
        );
      };
      img.onerror = () => resolve(file);
    };
    reader.onerror = () => resolve(file);
  });
};

const setFile = async (file) => {
  if (file && file.type.startsWith('image/')) {
    const compressed = await compressImage(file);
    selectedFile.value = compressed;
    form.image = compressed;
    previewUrl.value = URL.createObjectURL(compressed);
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
