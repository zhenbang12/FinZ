<template>
  <AppLayout>
    <div class="space-y-6 max-w-5xl mx-auto pb-12">
      <!-- Top Navigation Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <Link href="/receipts" class="text-xs font-black text-slate-950 hover:underline flex items-center gap-1 uppercase">
            ← Back to Receipts
          </Link>
          <span class="text-slate-400 font-bold">|</span>
          <button
            @click="confirmDeleteReceipt"
            class="text-xs font-black text-rose-600 hover:underline flex items-center gap-1 transition-colors uppercase"
          >
            <Trash2Icon class="w-3.5 h-3.5" />
            <span>Delete Receipt</span>
          </button>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-black bg-amber-400 text-slate-950 border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a] flex items-center gap-1.5 uppercase font-mono">
          <SparklesIcon class="w-3.5 h-3.5 text-slate-950" />
          {{ receipt.raw_ocr_data?.ocr_engine || 'Google Gemini AI Vision' }}
        </span>
      </div>

      <!-- Live Group Session Share Banner -->
      <div class="p-5 sm:p-6 rounded-3xl bg-amber-300 border-3 border-slate-950 shadow-[6px_6px_0px_#0f172a] flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 text-slate-950">
        <div class="space-y-1">
          <div class="flex items-center space-x-2">
            <UsersIcon class="w-5 h-5 text-slate-950" />
            <h3 class="font-black text-base sm:text-lg text-slate-950">Live Group Session Splitting</h3>
          </div>
          <p class="text-xs text-slate-900 font-bold">
            Generate a guest link so anyone can join without an account, tick what they ate, and pay their share!
          </p>
        </div>

        <div v-if="!shareUrl">
          <button
            @click="createSession"
            class="px-5 py-2.5 rounded-2xl bg-slate-950 text-amber-300 font-black text-xs border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a] flex items-center gap-2 hover:bg-slate-900 transition-all active:translate-x-0.5 active:translate-y-0.5"
          >
            <Share2Icon class="w-4 h-4 text-amber-400" />
            <span>Create Live Group Session</span>
          </button>
        </div>

        <div v-else class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
          <input
            type="text"
            readonly
            :value="shareUrl"
            class="px-3.5 py-2 rounded-2xl bg-white border-2 border-slate-950 text-slate-950 text-xs font-mono font-bold w-full sm:w-64 focus:outline-none"
          />
          <button
            @click="copyShareUrl"
            class="px-4 py-2 rounded-2xl bg-slate-950 text-amber-300 font-black text-xs border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a] flex items-center justify-center gap-1.5 shrink-0"
          >
            <CopyIcon class="w-3.5 h-3.5 text-amber-400" />
            <span>{{ copied ? 'Copied Link!' : 'Copy Link' }}</span>
          </button>
          <button
            @click="showQrModal = true"
            class="px-4 py-2 rounded-2xl bg-white text-slate-950 border-2 border-slate-950 font-black text-xs shadow-[2px_2px_0px_#0f172a] flex items-center justify-center gap-1.5 shrink-0"
          >
            <QrCodeIcon class="w-3.5 h-3.5 text-slate-950" />
            <span>QR Code</span>
          </button>
        </div>
      </div>

      <!-- Receipt Summary Header -->
      <div class="p-6 rounded-3xl bg-white border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center space-x-3.5">
          <button
            v-if="receipt.image_path"
            type="button"
            @click="showOriginalReceiptModal = true"
            class="shrink-0 group focus:outline-none"
            title="Click to view original scanned receipt image"
          >
            <img
              :src="receipt.image_path"
              class="w-16 h-16 rounded-2xl object-cover border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a] group-hover:scale-105 transition-all"
            />
          </button>
          <div>
            <span class="text-[10px] font-black text-slate-600 uppercase tracking-wider block">Merchant / Vendor</span>
            <h2 class="text-2xl sm:text-3xl font-black text-slate-950 mt-0.5">
              {{ receipt.merchant_name || 'Extracted Receipt' }}
            </h2>
            <p class="text-xs text-slate-600 font-bold mt-1">Receipt ID: #{{ receipt.id }} • {{ formatDate(receipt.created_at) }}</p>
          </div>
        </div>

        <div class="text-right border-t md:border-t-0 border-slate-950/10 pt-3 md:pt-0">
          <span class="text-[10px] font-black text-slate-600 uppercase tracking-wider block">Total Bill</span>
          <span class="text-3xl font-black font-mono text-slate-950 block">{{ formatCurrency(receipt.total_amount) }}</span>
        </div>
      </div>

      <!-- Interactive Bill Splitting Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Line Items Selector (2 cols) -->
        <div class="p-6 rounded-3xl bg-white border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] lg:col-span-2 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-base sm:text-lg font-black text-slate-950 flex items-center gap-2">
              <CheckSquareIcon class="w-5 h-5 text-slate-950" />
              <span>Extracted Line Items (Select What You Consumed)</span>
            </h3>
            <button
              @click="toggleSelectAll"
              class="text-xs font-black text-slate-950 hover:underline uppercase"
            >
              {{ isAllSelected ? 'Deselect All' : 'Select All' }}
            </button>
          </div>

          <div class="space-y-2.5">
            <div
              v-for="item in receipt.items"
              :key="item.id"
              @click="getItemClaimStatus(item) ? null : toggleItemClaim(item.id)"
              :class="[
                getItemClaimStatus(item)
                  ? 'bg-slate-100 text-slate-500 border-slate-300 opacity-80 cursor-default'
                  : (claimedItemIds.includes(item.id)
                      ? 'bg-slate-950 text-white border-slate-950 shadow-[3px_3px_0px_#0f172a] cursor-pointer'
                      : 'bg-slate-50 text-slate-950 border-slate-950 shadow-[2px_2px_0px_#0f172a] hover:bg-slate-100 cursor-pointer'),
                'p-4 rounded-2xl border-2 flex items-center justify-between transition-all'
              ]"
            >
              <div class="flex items-center space-x-3.5 min-w-0">
                <div
                  v-if="!getItemClaimStatus(item)"
                  :class="[
                    claimedItemIds.includes(item.id)
                      ? 'bg-amber-400 text-slate-950 border-slate-950'
                      : 'border-slate-950 bg-white text-transparent',
                    'w-5 h-5 rounded-lg border-2 flex items-center justify-center transition-colors shrink-0'
                  ]"
                >
                  <CheckIcon class="w-3.5 h-3.5 stroke-[3]" />
                </div>

                <div v-else class="w-5 h-5 rounded-lg bg-slate-300 border border-slate-950 text-slate-700 flex items-center justify-center shrink-0">
                  <CheckIcon class="w-3.5 h-3.5 stroke-[3]" />
                </div>

                <div class="min-w-0">
                  <span :class="[getItemClaimStatus(item) ? 'line-through text-slate-500' : (claimedItemIds.includes(item.id) ? 'text-white' : 'text-slate-950'), 'font-extrabold text-sm block truncate']">
                    {{ item.name }}
                  </span>
                  <span :class="[claimedItemIds.includes(item.id) ? 'text-amber-300' : 'text-slate-600', 'text-xs block font-mono font-bold']">
                    Qty: {{ item.quantity }} × {{ formatCurrency(item.unit_price) }}
                  </span>
                </div>
              </div>

              <div class="text-right shrink-0 flex flex-col items-end space-y-1">
                <span :class="[getItemClaimStatus(item) ? 'line-through text-slate-500' : (claimedItemIds.includes(item.id) ? 'text-amber-300' : 'text-slate-950'), 'text-base font-black font-mono block']">
                  {{ formatCurrency(item.total_price) }}
                </span>

                <!-- Participant Paid Badge & Undo Claim Button -->
                <div v-if="getItemClaimStatus(item)" class="flex items-center space-x-1.5">
                  <span class="px-2.5 py-0.5 rounded-full text-[9px] font-black bg-slate-200 text-slate-900 border border-slate-950 flex items-center gap-1">
                    ✓ Paid by {{ getItemClaimStatus(item).guest_name }} ({{ formatCurrency(getItemClaimStatus(item).amount_paid) }})
                  </span>
                  <button
                    type="button"
                    @click.stop="undoOwnerClaim(getItemClaimStatus(item))"
                    class="p-1 rounded-full text-slate-500 hover:text-rose-600 hover:bg-rose-100 transition-colors"
                    title="Undo claim"
                  >
                    <RotateCcwIcon class="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: Pro-Rata Math Breakdown & Save Expense Form (1 col) -->
        <div class="p-6 rounded-3xl bg-white border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] space-y-6 flex flex-col justify-between">
          <div class="space-y-4">
            <h3 class="text-lg font-black text-slate-950 flex items-center gap-2">
              <CalculatorIcon class="w-5 h-5 text-slate-950" />
              <span>Pro-Rata Calculation</span>
            </h3>

            <!-- Explainer Alert -->
            <div class="p-3.5 rounded-2xl bg-amber-300 border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a] text-xs text-slate-950 space-y-1">
              <span class="font-black block flex items-center gap-1">
                <InfoIcon class="w-4 h-4 shrink-0 text-slate-950" />
                SRS Pro-Rata Formula:
              </span>
              <p class="text-[11px] leading-relaxed font-bold">
                Tax, service fee, & discounts are calculated strictly proportional to your claimed items ({{ proRataData.pro_rata_percentage }}% of receipt).
              </p>
            </div>

            <!-- Mathematical Breakdown Card -->
            <div class="p-4 rounded-2xl bg-slate-50 border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a] space-y-2.5 text-xs font-bold text-slate-950">
              <div class="flex justify-between">
                <span>Claimed Subtotal:</span>
                <span class="font-mono font-black">{{ formatCurrency(proRataData.claimed_subtotal) }}</span>
              </div>

              <div class="flex justify-between text-slate-700">
                <span>SST Tax Share:</span>
                <span class="font-mono font-black">
                  {{ proRataData.is_tax_inclusive ? formatCurrency(proRataData.tax_share) + ' (Incl)' : '+ ' + formatCurrency(proRataData.tax_share) }}
                </span>
              </div>

              <div class="flex justify-between text-slate-700">
                <span>Service Charge Share:</span>
                <span class="font-mono font-black">+ {{ formatCurrency(proRataData.service_charge_share) }}</span>
              </div>

              <div v-if="proRataData.discount_share > 0" class="flex justify-between text-emerald-700 font-black">
                <span>Discount Deducted:</span>
                <span class="font-mono">- {{ formatCurrency(proRataData.discount_share) }}</span>
              </div>

              <div v-if="Math.abs(proRataData.rounding_share) > 0" class="flex justify-between text-slate-700">
                <span>Rounding Share:</span>
                <span class="font-mono">{{ proRataData.rounding_share > 0 ? '+' : '' }} {{ formatCurrency(proRataData.rounding_share) }}</span>
              </div>

              <div class="pt-3 border-t-2 border-slate-950 flex justify-between items-center">
                <span class="text-xs font-black uppercase tracking-wider">Your Final Total</span>
                <span class="text-2xl font-black font-mono text-emerald-600">{{ formatCurrency(proRataData.final_total) }}</span>
              </div>
            </div>
          </div>

          <!-- Log Expense Action Form -->
          <div class="space-y-4 border-t-2 border-slate-950 pt-4">
            <!-- Double-Logging Prevention Badge -->
            <div v-if="receipt.status === 'claimed'" class="p-3.5 rounded-2xl bg-emerald-400 border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a] text-slate-950 text-xs space-y-2">
              <div class="flex items-center space-x-2 font-black">
                <CheckCircleIcon class="w-4 h-4 text-slate-950" />
                <span>Expense Logged in Financial Ledger</span>
              </div>
              <Link href="/transactions" class="text-xs font-black text-slate-950 underline block">
                View Ledger Transactions →
              </Link>
            </div>

            <form v-else @submit.prevent="submitClaimedExpense" class="space-y-4">
              <h4 class="text-[10px] font-black text-slate-900 uppercase tracking-wider">
                Log Calculated Total as Expense
              </h4>

              <div>
                <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">Funding Account</label>
                <select
                  v-model="claimForm.account_id"
                  required
                  class="w-full px-3.5 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-sm focus:outline-none"
                >
                  <option value="" disabled>Select account</option>
                  <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                    {{ acc.name }} ({{ formatCurrency(acc.balance) }})
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">Expense Category</label>
                <select
                  v-model="claimForm.category_id"
                  required
                  class="w-full px-3.5 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-sm focus:outline-none"
                >
                  <option value="" disabled>Select category</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">Notes</label>
                <input
                  v-model="claimForm.notes"
                  type="text"
                  :placeholder="`SmartSplit from ${receipt.merchant_name || 'Receipt'}`"
                  class="w-full px-4 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-xs focus:outline-none"
                />
              </div>

              <!-- Quick Pay Shortcuts -->
              <div class="p-3.5 rounded-2xl bg-slate-100 border-2 border-slate-950 space-y-2">
                <span class="text-[10px] font-black text-slate-950 uppercase tracking-wider block">Quick Open Banking / eWallet App</span>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                  <a
                    v-for="(app, key) in appStoreLinks"
                    :key="key"
                    :href="getStoreHref(key)"
                    target="_blank"
                    :class="[
                      app.color,
                      'px-2 py-1.5 rounded-xl font-black border border-slate-950 text-[11px] flex items-center justify-center gap-1 shadow-[2px_2px_0px_#0f172a]'
                    ]"
                    :title="`Open ${app.name}`"
                  >
                    <span class="truncate">{{ app.name }}</span>
                  </a>
                </div>
              </div>

              <button
                type="submit"
                :disabled="claimedItemIds.length === 0 || claimForm.processing"
                class="w-full py-3.5 rounded-2xl bg-slate-950 text-amber-300 font-black text-sm border-2 border-slate-950 shadow-[4px_4px_0px_#0f172a] hover:bg-slate-900 disabled:opacity-40 disabled:cursor-not-allowed"
              >
                Log {{ formatCurrency(proRataData.final_total) }} Expense
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Original Scanned Receipt Modal -->
      <div
        v-if="showOriginalReceiptModal"
        class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
        @click.self="showOriginalReceiptModal = false"
      >
        <div class="bg-white rounded-3xl border-3 border-slate-950 shadow-[8px_8px_0px_#0f172a] max-w-xl w-full p-5 space-y-3 animate-scale-up text-slate-950">
          <div class="flex items-center justify-between border-b-2 border-slate-950 pb-2">
            <h4 class="font-black text-sm text-slate-950 flex items-center gap-2">
              <ReceiptIcon class="w-4 h-4 text-slate-950" />
              <span>Original Scanned Receipt</span>
            </h4>
            <button @click="showOriginalReceiptModal = false" class="w-8 h-8 rounded-full bg-slate-100 border-2 border-slate-950 flex items-center justify-center text-slate-950 font-bold hover:bg-rose-100 transition-colors">
              <XIcon class="w-4 h-4" />
            </button>
          </div>
          <div class="rounded-2xl overflow-hidden border-2 border-slate-950 bg-slate-50 flex items-center justify-center max-h-[75vh]">
            <img :src="receipt.image_path" class="max-w-full max-h-[75vh] object-contain rounded-xl" />
          </div>
        </div>
      </div>

      <!-- Table QR Code Modal -->
      <div
        v-if="showQrModal"
        class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
        @click.self="showQrModal = false"
      >
        <div class="bg-white rounded-3xl border-3 border-slate-950 shadow-[8px_8px_0px_#0f172a] max-w-sm w-full p-6 text-center space-y-4 animate-scale-up text-slate-950">
          <div class="flex items-center justify-between border-b-2 border-slate-950 pb-2">
            <h4 class="font-black text-sm text-slate-950 flex items-center gap-2">
              <QrCodeIcon class="w-4 h-4 text-slate-950" />
              <span>Scan Table QR Code</span>
            </h4>
            <button @click="showQrModal = false" class="w-8 h-8 rounded-full bg-slate-100 border-2 border-slate-950 flex items-center justify-center text-slate-950 font-bold hover:bg-rose-100 transition-colors">
              <XIcon class="w-4 h-4" />
            </button>
          </div>
          <p class="text-xs text-slate-700 font-bold">
            Hold up this QR code at the table! Friends can scan with their phone camera to join live.
          </p>
          <div class="p-4 bg-white rounded-2xl border-2 border-slate-950 inline-block shadow-[3px_3px_0px_#0f172a]">
            <img
              :src="`https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(shareUrl)}`"
              alt="Session QR Code"
              class="w-52 h-52 mx-auto rounded-lg object-contain"
            />
          </div>
          <div class="pt-2">
            <button @click="copyShareUrl" class="w-full py-3 rounded-2xl bg-slate-950 text-amber-300 font-black text-xs border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a]">
              {{ copied ? 'Copied Link!' : 'Copy Guest Link Instead' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatCurrency, formatDate } from '@/Utils/formatters';
import {
  CheckSquare as CheckSquareIcon,
  Check as CheckIcon,
  Calculator as CalculatorIcon,
  Info as InfoIcon,
  Sparkles as SparklesIcon,
  Users as UsersIcon,
  Share2 as Share2Icon,
  Copy as CopyIcon,
  RotateCcwIcon,
  CheckCircle as CheckCircleIcon,
  Receipt as ReceiptIcon,
  X as XIcon,
  Trash2 as Trash2Icon,
  QrCode as QrCodeIcon,
} from 'lucide-vue-next';

const props = defineProps({
  receipt: { type: Object, required: true },
  accounts: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  shareUrl: { type: String, default: null },
  taxesAndFees: { type: Array, default: () => [] },
  discounts: { type: Array, default: () => [] },
});

const showOriginalReceiptModal = ref(false);
const showQrModal = ref(false);

let syncInterval = null;

onMounted(() => {
  if (props.receipt.share_token) {
    syncInterval = setInterval(() => {
      router.reload({
        only: ['receipt'],
        preserveScroll: true,
        preserveState: true,
      });
    }, 3500);
  }
});

onUnmounted(() => {
  if (syncInterval) clearInterval(syncInterval);
});

const isIOS = typeof navigator !== 'undefined' && /iPhone|iPad|iPod/i.test(navigator.userAgent);

const appStoreLinks = {
  tng: {
    name: "Touch 'n Go",
    playStore: 'https://play.google.com/store/apps/details?id=my.com.tngdigital.ewallet',
    appStore: 'https://apps.apple.com/app/touch-n-go-ewallet/id1342373218',
    color: 'bg-blue-500 text-white',
  },
  mae: {
    name: 'Maybank MAE',
    playStore: 'https://play.google.com/store/apps/details?id=com.maybank2u.life',
    appStore: 'https://apps.apple.com/app/mae-by-maybank2u/id1481028763',
    color: 'bg-amber-400 text-slate-950',
  },
  cimb: {
    name: 'CIMB OCTO',
    playStore: 'https://play.google.com/store/apps/details?id=com.cimb.cimbocto',
    appStore: 'https://apps.apple.com/app/cimb-octo-my/id1608670830',
    color: 'bg-rose-500 text-white',
  },
  hlb: {
    name: 'HLB Connect',
    playStore: 'https://play.google.com/store/apps/details?id=my.com.hongleongconnect.mobileconnect',
    appStore: 'https://apps.apple.com/app/hlb-connect-mobile-banking/id1458055610',
    color: 'bg-blue-600 text-white',
  },
  rhb: {
    name: 'RHB Mobile',
    playStore: 'https://play.google.com/store/apps/details?id=com.rhbgroup.rhbmobilebanking',
    appStore: 'https://apps.apple.com/app/rhb-mobile-banking/id1435773177',
    color: 'bg-sky-500 text-white',
  },
  mypb: {
    name: 'MyPB',
    playStore: 'https://play.google.com/store/apps/details?id=com.pbb.mypb',
    appStore: 'https://apps.apple.com/app/mypb-by-public-bank/id1661667468',
    color: 'bg-red-500 text-white',
  },
  gxbank: {
    name: 'GXBank',
    playStore: 'https://play.google.com/store/apps/details?id=my.com.gxbank.app',
    appStore: 'https://apps.apple.com/app/gxbank/id6449176318',
    color: 'bg-cyan-400 text-slate-950',
  },
};

const getStoreHref = (appName) => {
  const target = appStoreLinks[appName];
  if (!target) return '#';
  if (isIOS) return target.appStore;
  return target.playStore;
};

const confirmDeleteReceipt = () => {
  if (confirm(`Are you sure you want to delete this receipt from "${props.receipt.merchant_name || 'Receipt #' + props.receipt.id}"?`)) {
    router.delete(`/receipts/${props.receipt.id}`);
  }
};
const copied = ref(false);

const getItemClaimStatus = (item) => {
  if (item.session_claims && item.session_claims.length > 0) {
    return item.session_claims[0];
  }
  return null;
};

const claimedItemIds = ref(
  props.receipt.items
    ? props.receipt.items.filter(i => !getItemClaimStatus(i)).map(i => i.id)
    : []
);

const isAllSelected = computed(() => {
  const availableItems = (props.receipt.items || []).filter(i => !getItemClaimStatus(i));
  return claimedItemIds.value.length === availableItems.length && availableItems.length > 0;
});

const toggleSelectAll = () => {
  const availableItems = (props.receipt.items || []).filter(i => !getItemClaimStatus(i));
  if (isAllSelected.value) {
    claimedItemIds.value = [];
  } else {
    claimedItemIds.value = availableItems.map(i => i.id);
  }
};

const toggleItemClaim = (id) => {
  const index = claimedItemIds.value.indexOf(id);
  if (index > -1) {
    claimedItemIds.value.splice(index, 1);
  } else {
    claimedItemIds.value.push(id);
  }
};

const createSession = () => {
  router.post(`/receipts/${props.receipt.id}/create-session`);
};

const copyShareUrl = () => {
  if (props.shareUrl) {
    navigator.clipboard.writeText(props.shareUrl);
    copied.value = true;
    setTimeout(() => {
      copied.value = false;
    }, 2500);
  }
};

const undoOwnerClaim = (claim) => {
  if (confirm(`Reset claim by ${claim.guest_name}? This item will become available again.`)) {
    router.delete(`/receipts/${props.receipt.id}/claims/${claim.id}`);
  }
};

const proRataData = computed(() => {
  const claimedItems = (props.receipt.items || []).filter(i => claimedItemIds.value.includes(i.id));
  const claimedSubtotal = claimedItems.reduce((sum, item) => sum + parseFloat(item.total_price), 0);
  const allItemsSubtotal = (props.receipt.items || []).reduce((sum, i) => sum + parseFloat(i.total_price), 0);
  const receiptSubtotal = parseFloat(props.receipt.subtotal) || allItemsSubtotal;

  const rawOcr = props.receipt.raw_ocr_data || {};
  const roundingAmount = parseFloat(rawOcr.rounding_amount || rawOcr.rounding || 0);

  const taxAmount = parseFloat(props.receipt.tax_amount || 0);
  const serviceAmount = parseFloat(props.receipt.service_charge_amount || 0);
  const discountAmount = parseFloat(props.receipt.discount_amount || 0);
  const totalAmount = parseFloat(props.receipt.total_amount || 0);

  let proRataRatio = 0;
  if (receiptSubtotal > 0) {
    proRataRatio = claimedSubtotal / receiptSubtotal;
  }

  let isTaxInclusive = false;
  if (rawOcr.is_tax_inclusive !== undefined) {
    isTaxInclusive = Boolean(rawOcr.is_tax_inclusive);
  } else if (totalAmount > 0) {
    const exclusiveEst = allItemsSubtotal - discountAmount + taxAmount + serviceAmount + roundingAmount;
    const inclusiveEst = allItemsSubtotal - discountAmount + serviceAmount + roundingAmount;

    const diffInclusive = Math.abs(inclusiveEst - totalAmount);
    const diffExclusive = Math.abs(exclusiveEst - totalAmount);

    if (diffInclusive < diffExclusive && diffInclusive <= 0.75) {
      isTaxInclusive = true;
    }
  }

  const taxShare = taxAmount * proRataRatio;
  const serviceChargeShare = serviceAmount * proRataRatio;
  const discountShare = discountAmount * proRataRatio;
  const roundingShare = roundingAmount * proRataRatio;

  let finalTotal = 0;
  if (isTaxInclusive) {
    finalTotal = claimedSubtotal - discountShare + serviceChargeShare + roundingShare;
  } else {
    finalTotal = claimedSubtotal + taxShare + serviceChargeShare - discountShare + roundingShare;
  }

  return {
    claimed_subtotal: claimedSubtotal,
    tax_share: taxShare,
    service_charge_share: serviceChargeShare,
    discount_share: discountShare,
    rounding_share: roundingShare,
    is_tax_inclusive: isTaxInclusive,
    final_total: Math.max(0, finalTotal),
    pro_rata_percentage: (proRataRatio * 100).toFixed(1),
  };
});

const claimForm = useForm({
  claimed_item_ids: [],
  account_id: props.accounts[0]?.id || '',
  category_id: props.categories[0]?.id || '',
  notes: '',
});

const submitClaimedExpense = () => {
  claimForm.claimed_item_ids = claimedItemIds.value;
  claimForm.post(`/receipts/${props.receipt.id}/claim`);
};
</script>
