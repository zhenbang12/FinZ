<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div>
        <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
          SmartSplit OCR Receipt Parser
        </h2>
        <p class="text-xs sm:text-sm text-slate-500 mt-1 font-normal">Scan or upload receipt images to extract line items and split bills with pro-rata tax math.</p>
      </div>

      <!-- Dropzone Card -->
      <div class="minimal-card p-6 sm:p-8 space-y-6">
        <form @submit.prevent="submitUpload" class="space-y-6">
          <div
            @click="triggerFileInput"
            class="border-2 border-dashed border-slate-300 hover:border-slate-800 bg-slate-50 hover:bg-slate-100/80 rounded-2xl p-8 sm:p-12 text-center cursor-pointer transition-all group"
          >
            <!-- Native Mobile Camera Input -->
            <input
              ref="fileInputRef"
              type="file"
              accept="image/*"
              capture="environment"
              class="hidden"
              @change="handleFileSelected"
            />

            <div class="w-16 h-16 rounded-full bg-slate-900 text-white flex items-center justify-center mx-auto mb-4 group-hover:scale-105 transition-transform">
              <CameraIcon class="w-8 h-8 text-white" />
            </div>

            <h3 class="text-lg font-bold text-slate-900 mb-1">
              {{ previewUrl ? 'Image Selected (Click to change)' : 'Take Photo or Upload Receipt' }}
            </h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto font-medium">
              Powered strictly by <span class="text-slate-900 font-bold">Google Gemini AI Vision</span>. Upload any receipt photo for line-item extraction!
            </p>

            <!-- Preview Image if selected -->
            <div v-if="previewUrl" class="mt-4 max-w-xs mx-auto overflow-hidden rounded-xl border border-slate-300 shadow-md">
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
  Sparkles as SparklesIcon,
  Receipt as ReceiptIcon,
} from 'lucide-vue-next';

const props = defineProps({
  receipts: { type: Array, default: () => [] },
});

const fileInputRef = ref(null);
const selectedFile = ref(null);
const previewUrl = ref(null);
const isProcessing = ref(false);

const form = useForm({
  image: null,
});

const triggerFileInput = () => {
  fileInputRef.value.click();
};

const handleFileSelected = (e) => {
  const file = e.target.files[0];
  if (file) {
    selectedFile.value = file;
    form.image = file;
    previewUrl.value = URL.createObjectURL(file);
  }
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
