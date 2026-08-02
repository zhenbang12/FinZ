<template>
  <div class="min-h-screen bg-[#f8fafc] text-[#0f172a] font-sans selection:bg-[#0f172a] selection:text-white p-4 sm:p-6 md:p-8 max-w-4xl mx-auto space-y-6">
    <!-- Header Logo & Brand -->
    <div class="flex items-center justify-between border-b border-slate-200 pb-4">
      <div class="flex items-center space-x-3">
        <div class="w-10 h-10 rounded-full bg-[#0f172a] text-white flex items-center justify-center font-black text-xl shadow-md">
          Z
        </div>
        <div>
          <h1 class="text-xl font-bold tracking-tight text-slate-900">SmartSplit Group Session</h1>
          <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-widest">Live Interactive Bill Splitting</p>
        </div>
      </div>

      <div class="flex items-center space-x-2">
        <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1.5">
          <UsersIcon class="w-3.5 h-3.5" />
          Live Guest Session
        </span>
      </div>
    </div>

    <!-- Flash Message Toast Notification -->
    <div v-if="flashSuccess" class="minimal-card p-4 rounded-2xl border border-emerald-500/40 shadow-xl flex items-center justify-between text-emerald-800 bg-emerald-50">
      <div class="flex items-center space-x-3">
        <CheckCircleIcon class="w-5 h-5 text-emerald-600 shrink-0" />
        <span class="text-sm font-medium">{{ flashSuccess }}</span>
      </div>
    </div>

    <!-- Receipt Header & Session Progress Card -->
    <div class="minimal-card p-6 space-y-5">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center space-x-3.5">
          <button
            v-if="receipt.image_path"
            type="button"
            @click="showReceiptModal = true"
            class="shrink-0 group focus:outline-none"
            title="Click to view original scanned receipt image"
          >
            <img
              :src="receipt.image_path"
              class="w-14 h-14 rounded-2xl object-cover border border-slate-300 group-hover:scale-105 group-hover:border-indigo-500 transition-all shadow-xs"
            />
          </button>
          <div>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Merchant / Restaurant</span>
            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mt-0.5">
              {{ receipt.merchant_name || 'Receipt Session' }}
            </h2>
            <p class="text-xs text-slate-500 mt-1 font-medium">Host: {{ receipt.user?.name || 'FinZ User' }} • {{ formatDate(receipt.created_at) }}</p>
          </div>
        </div>

        <div class="text-right">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Bill</span>
          <span class="text-3xl font-black text-slate-900 block">{{ formatCurrency(receipt.total_amount) }}</span>
        </div>
      </div>

      <!-- Live Group Session Progress Bar -->
      <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
        <div class="flex items-center justify-between text-xs font-bold">
          <span class="text-slate-700 flex items-center gap-1.5">
            <UsersIcon class="w-4 h-4 text-slate-900" />
            Group Payment Progress: {{ formatCurrency(totalPaidByGroup) }} of {{ formatCurrency(receipt.total_amount) }} Paid
          </span>
          <span :class="[remainingUnpaid <= 0.05 ? 'text-emerald-600 font-extrabold' : 'text-amber-600', 'text-xs']">
            {{ remainingUnpaid <= 0.05 ? '✓ Fully Paid!' : formatCurrency(remainingUnpaid) + ' Unpaid' }}
          </span>
        </div>

        <div class="w-full h-3 rounded-full bg-slate-200 overflow-hidden">
          <div
            class="h-full bg-emerald-500 rounded-full transition-all duration-500"
            :style="{ width: `${Math.min(100, (totalPaidByGroup / (receipt.total_amount || 1)) * 100)}%` }"
          ></div>
        </div>
      </div>
    </div>

    <!-- Main Group Splitting Form -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left Column: Line Items (2 cols) -->
      <div class="minimal-card lg:col-span-2 p-6 space-y-5">
        <div class="space-y-1">
          <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
            <CheckSquareIcon class="w-5 h-5 text-slate-700" />
            <span>Select Items You Ate / Consumed</span>
          </h3>
          <p class="text-xs text-slate-500 font-medium">Tick the items you consumed. Items paid by others are grayed out below with an undo button if there was a mistake.</p>
        </div>

        <!-- Guest Name Input -->
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1 uppercase tracking-wider">
            Your Name (Guest Participant)
          </label>
          <input
            v-model="guestName"
            type="text"
            required
            placeholder="e.g. Alex, Chloe, Ben"
            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-bold text-sm focus:outline-none focus:border-slate-900"
          />
        </div>

        <!-- Receipt Line Items List -->
        <div class="space-y-2.5">
          <div
            v-for="item in receipt.items"
            :key="item.id"
            @click="getItemClaimStatus(item) ? null : toggleItemSelection(item.id)"
            :class="[
              getItemClaimStatus(item)
                ? 'bg-slate-100/90 text-slate-400 border-slate-200 opacity-80 cursor-default'
                : (selectedItemIds.includes(item.id)
                    ? 'bg-slate-900 text-white border-slate-900 shadow-sm cursor-pointer'
                    : 'bg-slate-50 text-slate-900 border-slate-200 hover:border-slate-300 cursor-pointer'),
              'p-4 rounded-xl border flex items-center justify-between transition-all'
            ]"
          >
            <div class="flex items-center space-x-3.5 min-w-0">
              <!-- Checkbox Icon -->
              <div
                v-if="!getItemClaimStatus(item)"
                :class="[
                  selectedItemIds.includes(item.id)
                    ? 'bg-white text-slate-900 border-white'
                    : 'border-slate-300 bg-white text-transparent',
                  'w-5 h-5 rounded border flex items-center justify-center transition-colors shrink-0'
                ]"
              >
                <CheckIcon class="w-3.5 h-3.5 stroke-[3]" />
              </div>

              <!-- Paid Lock Icon if claimed -->
              <div v-else class="w-5 h-5 rounded bg-slate-200 text-slate-500 flex items-center justify-center shrink-0">
                <CheckIcon class="w-3.5 h-3.5 stroke-[3]" />
              </div>

              <div class="min-w-0">
                <div class="flex items-center space-x-2">
                  <span :class="[getItemClaimStatus(item) ? 'line-through text-slate-400' : (selectedItemIds.includes(item.id) ? 'text-white' : 'text-slate-900'), 'font-bold text-sm block truncate']">
                    {{ item.name }}
                  </span>
                </div>
                <span :class="[selectedItemIds.includes(item.id) ? 'text-slate-300' : 'text-slate-500', 'text-xs block font-medium']">
                  Qty: {{ item.quantity }} × {{ formatCurrency(item.unit_price) }}
                </span>
              </div>
            </div>

            <div class="text-right shrink-0 flex flex-col items-end space-y-1">
              <span :class="[getItemClaimStatus(item) ? 'line-through text-slate-400' : (selectedItemIds.includes(item.id) ? 'text-white' : 'text-slate-900'), 'text-base font-extrabold block']">
                {{ formatCurrency(item.total_price) }}
              </span>

              <!-- Participant Paid Name Badge & Undo Button -->
              <div v-if="getItemClaimStatus(item)" class="flex items-center space-x-1.5">
                <span class="px-2.5 py-0.5 rounded-full text-[9px] font-extrabold bg-slate-200 text-slate-700 border border-slate-300 flex items-center gap-1">
                  ✓ Paid by {{ getItemClaimStatus(item).guest_name }} ({{ formatCurrency(getItemClaimStatus(item).amount_paid) }})
                </span>
                <button
                  type="button"
                  @click.stop="undoGuestClaim(getItemClaimStatus(item))"
                  class="p-1 rounded-full text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                  title="Undo / Reset this claim if there was a mistake"
                >
                  <RotateCcwIcon class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Pro-Rata Total & Pay Action (1 col) -->
      <div class="minimal-card p-6 space-y-6 flex flex-col justify-between">
        <div class="space-y-4">
          <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
            <CalculatorIcon class="w-5 h-5 text-slate-700" />
            <span>Your Calculation</span>
          </h3>

          <div class="p-3.5 rounded-xl bg-slate-100 border border-slate-200 text-xs text-slate-700 space-y-1">
            <span class="font-bold block flex items-center gap-1">
              <InfoIcon class="w-4 h-4 shrink-0 text-slate-900" />
              Pro-Rata Tax & Discount Share:
            </span>
            <p class="text-[11px] leading-relaxed text-slate-600">
              SST tax, service fee, and discounts are split proportionally for your selected items ({{ proRataData.pro_rata_percentage }}%).
            </p>
          </div>

          <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
            <div class="flex justify-between text-xs text-slate-700 font-medium">
              <span>Selected Items Subtotal:</span>
              <span class="font-bold text-slate-900">{{ formatCurrency(proRataData.claimed_subtotal) }}</span>
            </div>

            <div class="flex justify-between text-xs text-slate-500 font-medium">
              <span>Pro-Rata SST Tax Share:</span>
              <span class="font-semibold text-slate-700">+ {{ formatCurrency(proRataData.tax_share) }}</span>
            </div>

            <div class="flex justify-between text-xs text-slate-500 font-medium">
              <span>Pro-Rata Service Charge:</span>
              <span class="font-semibold text-slate-700">+ {{ formatCurrency(proRataData.service_charge_share) }}</span>
            </div>

            <div v-if="proRataData.discount_share > 0" class="flex justify-between text-xs text-emerald-700 font-bold">
              <span>Pro-Rata Discount Deducted:</span>
              <span>- {{ formatCurrency(proRataData.discount_share) }}</span>
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-between items-center">
              <span class="text-xs font-bold text-slate-900 uppercase tracking-wider">Your Final Total</span>
              <span class="text-2xl font-black text-emerald-600">{{ formatCurrency(proRataData.final_total) }}</span>
            </div>
          </div>

          <!-- Quick Pay via Banking / eWallet App Shortcuts -->
          <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
            <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider block">Quick Open eWallet / Banking App</span>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
              <a
                v-for="(app, key) in appStoreLinks"
                :key="key"
                :href="getStoreHref(key)"
                target="_blank"
                @click="openEWalletApp($event, key)"
                :class="[
                  app.color,
                  'px-2.5 py-2 rounded-xl font-extrabold text-[11px] sm:text-xs flex items-center justify-center gap-1 transition-colors shadow-xs'
                ]"
                :title="`Open ${app.name}`"
              >
                <span class="truncate">{{ app.name }}</span>
              </a>
            </div>
          </div>
        </div>

        <form @submit.prevent="submitGuestClaim" class="space-y-3 pt-4 border-t border-slate-100">
          <button
            type="submit"
            :disabled="!guestName.trim() || selectedItemIds.length === 0 || form.processing"
            class="minimal-btn-primary w-full py-3.5 text-sm font-bold shadow-md disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center space-x-2"
          >
            <CheckCircleIcon class="w-4 h-4 text-white" />
            <span>Mark {{ formatCurrency(proRataData.final_total) }} as Paid</span>
          </button>
        </form>
      </div>
    </div>

    <!-- Original Scanned Receipt Modal for Guests -->
    <div
      v-if="showReceiptModal"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
      @click.self="showReceiptModal = false"
    >
      <div class="minimal-card max-w-xl w-full p-4 sm:p-5 space-y-3 animate-scale-up">
        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
          <h4 class="font-bold text-sm text-slate-900 flex items-center gap-2">
            <ReceiptIcon class="w-4 h-4 text-indigo-600" />
            <span>Original Scanned Receipt</span>
          </h4>
          <button @click="showReceiptModal = false" class="text-slate-400 hover:text-slate-600 p-1">
            <XIcon class="w-5 h-5" />
          </button>
        </div>
        <div class="rounded-xl overflow-hidden border border-slate-200 bg-slate-50 flex items-center justify-center max-h-[75vh]">
          <img :src="receipt.image_path" class="max-w-full max-h-[75vh] object-contain rounded-xl shadow-xs" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import { formatCurrency, formatDate } from '@/Utils/formatters';
import {
  Users as UsersIcon,
  CheckSquare as CheckSquareIcon,
  Check as CheckIcon,
  Calculator as CalculatorIcon,
  Info as InfoIcon,
  CheckCircle as CheckCircleIcon,
  RotateCcw as RotateCcwIcon,
  Image as ImageIcon,
  Receipt as ReceiptIcon,
  X as XIcon,
} from 'lucide-vue-next';

const props = defineProps({
  receipt: { type: Object, required: true },
  taxesAndFees: { type: Array, default: () => [] },
  discounts: { type: Array, default: () => [] },
  existingClaims: { type: Array, default: () => [] },
});

let syncInterval = null;

onMounted(() => {
  // Real-time live auto sync every 3.5 seconds
  syncInterval = setInterval(() => {
    router.reload({
      only: ['receipt', 'existingClaims'],
      preserveScroll: true,
      preserveState: true,
    });
  }, 3500);
});

onUnmounted(() => {
  if (syncInterval) clearInterval(syncInterval);
});

const isAndroid = typeof navigator !== 'undefined' && /Android/i.test(navigator.userAgent);
const isIOS = typeof navigator !== 'undefined' && /iPhone|iPad|iPod/i.test(navigator.userAgent);

const appStoreLinks = {
  tng: {
    name: "Touch 'n Go",
    scheme: 'tngdwallet://',
    intentScheme: 'tngdwallet',
    intentPackage: 'com.touchngo.ewallet',
    playStore: 'https://play.google.com/store/apps/details?id=my.com.tngdigital.ewallet',
    appStore: 'https://apps.apple.com/app/touch-n-go-ewallet/id1342373218',
    color: 'bg-blue-600 hover:bg-blue-700 text-white',
  },
  mae: {
    name: 'Maybank MAE',
    scheme: 'maybank2u://',
    intentScheme: 'mae',
    intentPackage: 'com.maybank2u.life',
    playStore: 'https://play.google.com/store/apps/details?id=com.maybank2u.life',
    appStore: 'https://apps.apple.com/app/mae-by-maybank2u/id1481028763',
    color: 'bg-amber-400 hover:bg-amber-500 text-slate-950',
  },
  cimb: {
    name: 'CIMB OCTO',
    scheme: 'cimbocto://',
    intentScheme: 'cimbocto',
    intentPackage: 'com.cimb.cimbocto',
    playStore: 'https://play.google.com/store/apps/details?id=com.cimb.cimbocto',
    appStore: 'https://apps.apple.com/app/cimb-octo-my/id1608670830',
    color: 'bg-rose-600 hover:bg-rose-700 text-white',
  },
  hlb: {
    name: 'HLB Connect',
    scheme: 'hlbconnect://',
    intentScheme: 'hlbconnect',
    intentPackage: 'my.com.hlb.connect',
    playStore: 'https://play.google.com/store/apps/details?id=my.com.hlb.connect',
    appStore: 'https://apps.apple.com/app/hlb-connect-mobile-banking/id1458055610',
    color: 'bg-blue-700 hover:bg-blue-800 text-white',
  },
  rhb: {
    name: 'RHB Mobile',
    scheme: 'rhbmb://',
    intentScheme: 'rhbmobile',
    intentPackage: 'com.rhbgroup.rhbmobilebanking',
    playStore: 'https://play.google.com/store/apps/details?id=com.rhbgroup.rhbmobilebanking',
    appStore: 'https://apps.apple.com/app/rhb-mobile-banking/id1435773177',
    color: 'bg-sky-600 hover:bg-sky-700 text-white',
  },
  mypb: {
    name: 'MyPB',
    scheme: 'mypb://',
    intentScheme: 'mypb',
    intentPackage: 'com.pbb.mypb',
    playStore: 'https://play.google.com/store/apps/details?id=com.pbb.mypb',
    appStore: 'https://apps.apple.com/app/mypb-by-public-bank/id1661667468',
    color: 'bg-red-600 hover:bg-red-700 text-white',
  },
  gxbank: {
    name: 'GXBank',
    scheme: 'gxbank://',
    intentScheme: 'gxbank',
    intentPackage: 'my.com.gxbank.app',
    playStore: 'https://play.google.com/store/apps/details?id=my.com.gxbank.app',
    appStore: 'https://apps.apple.com/app/gxbank/id6449176318',
    color: 'bg-purple-600 hover:bg-purple-700 text-white',
  },
};

const getStoreHref = (appName) => {
  const target = appStoreLinks[appName];
  if (!target) return '#';
  if (isIOS) return target.appStore;
  if (isAndroid && target.intentScheme && target.intentPackage && appName !== 'tng') {
    return `intent://open#Intent;scheme=${target.intentScheme};package=${target.intentPackage};end`;
  }
  return target.playStore;
};

const openEWalletApp = (event, appName) => {
  const target = appStoreLinks[appName];
  if (!target) return;
  if (isIOS) {
    window.location.href = target.scheme;
  } else if (isAndroid && appName === 'tng') {
    window.location.href = target.scheme;
  }
};

const page = usePage();
const showReceiptModal = ref(false);
const flashSuccess = computed(() => page.props.flash?.success);

const guestName = ref('');
const selectedItemIds = ref([]);

const getItemClaimStatus = (item) => {
  if (item.session_claims && item.session_claims.length > 0) {
    return item.session_claims[0];
  }
  return null;
};

const totalPaidByGroup = computed(() => {
  if (!props.receipt.session_claims) return 0;
  return props.receipt.session_claims.reduce((sum, c) => sum + parseFloat(c.amount_paid || 0), 0);
});

const remainingUnpaid = computed(() => {
  return Math.max(0, parseFloat(props.receipt.total_amount || 0) - totalPaidByGroup.value);
});

const toggleItemSelection = (itemId) => {
  const idx = selectedItemIds.value.indexOf(itemId);
  if (idx > -1) {
    selectedItemIds.value.splice(idx, 1);
  } else {
    selectedItemIds.value.push(itemId);
  }
};

const undoGuestClaim = (claim) => {
  if (confirm(`Undo claim for ${claim.guest_name}? This will make the item available for selection again.`)) {
    router.delete(`/receipts/session/${props.receipt.share_token}/claim/${claim.id}`);
  }
};

// Pro-Rata Math Calculation
const proRataData = computed(() => {
  const selectedItems = (props.receipt.items || []).filter(i => selectedItemIds.value.includes(i.id));
  const claimedSubtotal = selectedItems.reduce((sum, item) => sum + parseFloat(item.total_price), 0);
  const receiptSubtotal = parseFloat(props.receipt.subtotal) || (props.receipt.items || []).reduce((sum, i) => sum + parseFloat(i.total_price), 0);

  let proRataRatio = 0;
  let taxShare = 0;
  let serviceChargeShare = 0;
  let discountShare = 0;

  if (receiptSubtotal > 0) {
    proRataRatio = claimedSubtotal / receiptSubtotal;
    taxShare = (parseFloat(props.receipt.tax_amount || 0) * proRataRatio);
    serviceChargeShare = (parseFloat(props.receipt.service_charge_amount || 0) * proRataRatio);
    discountShare = (parseFloat(props.receipt.discount_amount || 0) * proRataRatio);
  }

  const netAdjustment = taxShare + serviceChargeShare - discountShare;
  const finalTotal = claimedSubtotal + netAdjustment;

  return {
    claimed_subtotal: claimedSubtotal,
    tax_share: taxShare,
    service_charge_share: serviceChargeShare,
    discount_share: discountShare,
    final_total: finalTotal,
    pro_rata_percentage: (proRataRatio * 100).toFixed(1),
  };
});

const form = useForm({
  guest_name: '',
  item_ids: [],
});

const submitGuestClaim = () => {
  form.guest_name = guestName.value;
  form.item_ids = selectedItemIds.value;
  form.post(`/receipts/session/${props.receipt.share_token}/claim`, {
    onSuccess: () => {
      selectedItemIds.value = [];
    },
  });
};
</script>
