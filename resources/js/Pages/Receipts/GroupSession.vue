<template>
  <div class="min-h-screen bg-[#f8fafc] text-[#0f172a] font-sans selection:bg-[#0f172a] selection:text-white p-4 sm:p-6 md:p-8 max-w-4xl mx-auto space-y-6">
    <!-- Header Logo & Brand -->
    <div class="flex items-center justify-between border-b-3 border-slate-950 pb-4">
      <div class="flex items-center space-x-3">
        <div class="w-12 h-12 rounded-2xl bg-amber-300 border-2 border-slate-950 text-slate-950 flex items-center justify-center font-black text-2xl shadow-[3px_3px_0px_#0f172a]">
          Z
        </div>
        <div>
          <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-950 font-mono">SmartSplit Group Session</h1>
          <p class="text-[10px] text-slate-700 font-black uppercase tracking-widest">Live Interactive Bill Splitting</p>
        </div>
      </div>

      <div class="flex items-center space-x-2">
        <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-400 text-slate-950 border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a] flex items-center gap-1.5 uppercase font-mono">
          <UsersIcon class="w-3.5 h-3.5 text-slate-950" />
          Live Session
        </span>
      </div>
    </div>

    <!-- Flash Message Toast Notification -->
    <div v-if="flashSuccess" class="bg-emerald-300 rounded-2xl border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] p-4 flex items-center justify-between text-slate-950">
      <div class="flex items-center space-x-3 font-black text-xs">
        <CheckCircleIcon class="w-5 h-5 text-slate-950 shrink-0" />
        <span>{{ flashSuccess }}</span>
      </div>
    </div>

    <!-- Receipt Header & Session Progress Card -->
    <div class="p-6 rounded-3xl bg-white border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] space-y-5">
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
              class="w-16 h-16 rounded-2xl object-cover border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a] group-hover:scale-105 transition-all"
            />
          </button>
          <div>
            <span class="text-[10px] font-black text-slate-600 uppercase tracking-wider block">Merchant / Restaurant</span>
            <h2 class="text-2xl sm:text-3xl font-black text-slate-950 mt-0.5">
              {{ receipt.merchant_name || 'Receipt Session' }}
            </h2>
            <p class="text-xs text-slate-600 font-bold mt-1">Host: {{ receipt.user?.name || 'FinZ User' }} • {{ formatDate(receipt.created_at) }}</p>
          </div>
        </div>

        <div class="text-right">
          <span class="text-[10px] font-black text-slate-600 uppercase tracking-wider block">Total Bill</span>
          <span class="text-3xl font-black font-mono text-slate-950 block">{{ formatCurrency(receipt.total_amount) }}</span>
        </div>
      </div>

      <!-- Live Group Session Progress Bar -->
      <div class="p-4 rounded-2xl bg-amber-300 border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a] space-y-2 text-slate-950">
        <div class="flex items-center justify-between text-xs font-black">
          <span class="text-slate-950 flex items-center gap-1.5 uppercase">
            <UsersIcon class="w-4 h-4 text-slate-950" />
            Group Progress: {{ formatCurrency(totalPaidByGroup) }} of {{ formatCurrency(receipt.total_amount) }} Paid
          </span>
          <span :class="[remainingUnpaid <= 0.05 ? 'text-slate-950 font-black' : 'text-slate-950', 'text-xs font-mono']">
            {{ remainingUnpaid <= 0.05 ? '✓ Fully Paid!' : formatCurrency(remainingUnpaid) + ' Unpaid' }}
          </span>
        </div>

        <div class="w-full h-3 rounded-full bg-white border-2 border-slate-950 overflow-hidden">
          <div
            class="h-full bg-emerald-400 rounded-full transition-all duration-500"
            :style="{ width: `${Math.min(100, (totalPaidByGroup / (receipt.total_amount || 1)) * 100)}%` }"
          ></div>
        </div>
      </div>
    </div>

    <!-- Main Group Splitting Form -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left Column: Line Items (2 cols) -->
      <div class="p-6 rounded-3xl bg-white border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] lg:col-span-2 space-y-5">
        <div class="space-y-1">
          <h3 class="text-lg font-black text-slate-950 flex items-center gap-2">
            <CheckSquareIcon class="w-5 h-5 text-slate-950" />
            <span>Select Items You Ate / Consumed</span>
          </h3>
          <p class="text-xs text-slate-600 font-bold">Tick the items you consumed. Items paid by others are grayed out below.</p>
        </div>

        <!-- Guest Name Input -->
        <div>
          <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">
            Your Name (Guest Participant)
          </label>
          <input
            v-model="guestName"
            type="text"
            required
            placeholder="e.g. Alex, Chloe, Ben"
            class="w-full px-4 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-black text-sm focus:outline-none"
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
                ? 'bg-slate-100 text-slate-500 border-slate-300 opacity-80 cursor-default'
                : (selectedItemIds.includes(item.id)
                    ? 'bg-slate-950 text-white border-slate-950 shadow-[3px_3px_0px_#0f172a] cursor-pointer'
                    : 'bg-slate-50 text-slate-950 border-slate-950 shadow-[2px_2px_0px_#0f172a] hover:bg-slate-100 cursor-pointer'),
              'p-4 rounded-2xl border-2 flex items-center justify-between transition-all'
            ]"
          >
            <div class="flex items-center space-x-3.5 min-w-0">
              <div
                v-if="!getItemClaimStatus(item)"
                :class="[
                  selectedItemIds.includes(item.id)
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
                <div class="flex items-center space-x-2">
                  <span :class="[getItemClaimStatus(item) ? 'line-through text-slate-500' : (selectedItemIds.includes(item.id) ? 'text-white' : 'text-slate-950'), 'font-black text-sm block truncate']">
                    {{ item.name }}
                  </span>
                </div>
                <span :class="[selectedItemIds.includes(item.id) ? 'text-amber-300' : 'text-slate-600', 'text-xs block font-mono font-bold']">
                  Qty: {{ item.quantity }} × {{ formatCurrency(item.unit_price) }}
                </span>
              </div>
            </div>

            <div class="text-right shrink-0 flex flex-col items-end space-y-1">
              <span :class="[getItemClaimStatus(item) ? 'line-through text-slate-500' : (selectedItemIds.includes(item.id) ? 'text-amber-300' : 'text-slate-950'), 'text-base font-black font-mono block']">
                {{ formatCurrency(item.total_price) }}
              </span>

              <!-- Participant Paid Name Badge & Undo Button -->
              <div v-if="getItemClaimStatus(item)" class="flex items-center space-x-1.5">
                <span class="px-2.5 py-0.5 rounded-full text-[9px] font-black bg-slate-200 text-slate-900 border border-slate-950 flex items-center gap-1">
                  ✓ Paid by {{ getItemClaimStatus(item).guest_name }} ({{ formatCurrency(getItemClaimStatus(item).amount_paid) }})
                </span>
                <button
                  type="button"
                  @click.stop="undoGuestClaim(getItemClaimStatus(item))"
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

      <!-- Right Column: Pro-Rata Total & Pay Action (1 col) -->
      <div class="p-6 rounded-3xl bg-white border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] space-y-6 flex flex-col justify-between">
        <div class="space-y-4">
          <h3 class="text-lg font-black text-slate-950 flex items-center gap-2">
            <CalculatorIcon class="w-5 h-5 text-slate-950" />
            <span>Your Calculation</span>
          </h3>

          <div class="p-3.5 rounded-2xl bg-amber-300 border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a] text-xs text-slate-950 space-y-1">
            <span class="font-black block flex items-center gap-1">
              <InfoIcon class="w-4 h-4 shrink-0 text-slate-950" />
              Pro-Rata Tax & Discount Share:
            </span>
            <p class="text-[11px] leading-relaxed font-bold">
              SST tax, service fee, and discounts are split proportionally for your selected items ({{ proRataData.pro_rata_percentage }}%).
            </p>
          </div>

          <div class="p-4 rounded-2xl bg-slate-50 border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a] space-y-2.5 text-xs font-bold text-slate-950">
            <div class="flex justify-between">
              <span>Selected Items Subtotal:</span>
              <span class="font-mono font-black">{{ formatCurrency(proRataData.claimed_subtotal) }}</span>
            </div>

            <div class="flex justify-between text-slate-700">
              <span>SST Tax Share:</span>
              <span class="font-mono font-black">+ {{ formatCurrency(proRataData.tax_share) }}</span>
            </div>

            <div class="flex justify-between text-slate-700">
              <span>Service Charge:</span>
              <span class="font-mono font-black">+ {{ formatCurrency(proRataData.service_charge_share) }}</span>
            </div>

            <div v-if="proRataData.discount_share > 0" class="flex justify-between text-emerald-700 font-black">
              <span>Discount Deducted:</span>
              <span class="font-mono">- {{ formatCurrency(proRataData.discount_share) }}</span>
            </div>

            <div class="pt-3 border-t-2 border-slate-950 flex justify-between items-center">
              <span class="text-xs font-black uppercase tracking-wider">Your Final Total</span>
              <span class="text-2xl font-black font-mono text-emerald-600">{{ formatCurrency(proRataData.final_total) }}</span>
            </div>
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
        </div>

        <form @submit.prevent="submitGuestClaim" class="space-y-3 pt-4 border-t-2 border-slate-950">
          <button
            type="submit"
            :disabled="!guestName.trim() || selectedItemIds.length === 0 || form.processing"
            class="w-full py-3.5 rounded-2xl bg-slate-950 text-amber-300 font-black text-sm border-2 border-slate-950 shadow-[4px_4px_0px_#0f172a] hover:bg-slate-900 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center space-x-2"
          >
            <CheckCircleIcon class="w-4 h-4 text-amber-400" />
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
      <div class="bg-white rounded-3xl border-3 border-slate-950 shadow-[8px_8px_0px_#0f172a] max-w-xl w-full p-5 space-y-3 animate-scale-up text-slate-950">
        <div class="flex items-center justify-between border-b-2 border-slate-950 pb-2">
          <h4 class="font-black text-sm text-slate-950 flex items-center gap-2">
            <ReceiptIcon class="w-4 h-4 text-slate-950" />
            <span>Original Scanned Receipt</span>
          </h4>
          <button @click="showReceiptModal = false" class="w-8 h-8 rounded-full bg-slate-100 border-2 border-slate-950 flex items-center justify-center text-slate-950 font-bold hover:bg-rose-100 transition-colors">
            <XIcon class="w-4 h-4" />
          </button>
        </div>
        <div class="rounded-2xl overflow-hidden border-2 border-slate-950 bg-slate-50 flex items-center justify-center max-h-[75vh]">
          <img :src="receipt.image_path" class="max-w-full max-h-[75vh] object-contain rounded-xl" />
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
