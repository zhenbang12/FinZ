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

    <!-- Receipt Header Card -->
    <div class="minimal-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Merchant / Restaurant</span>
        <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mt-0.5">
          {{ receipt.merchant_name || 'Receipt Session' }}
        </h2>
        <p class="text-xs text-slate-500 mt-1 font-medium">Host: {{ receipt.user?.name || 'FinZ User' }} • {{ formatDate(receipt.created_at) }}</p>
      </div>

      <div class="text-right">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Bill</span>
        <span class="text-3xl font-black text-slate-900 block">{{ formatCurrency(receipt.total_amount) }}</span>
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
          <p class="text-xs text-slate-500 font-medium">Tick the items you consumed. Items paid by others are grayed out below.</p>
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
                ? 'bg-slate-100/90 text-slate-400 border-slate-200 opacity-60 cursor-not-allowed'
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

            <div class="text-right shrink-0 flex flex-col items-end">
              <span :class="[getItemClaimStatus(item) ? 'line-through text-slate-400' : (selectedItemIds.includes(item.id) ? 'text-white' : 'text-slate-900'), 'text-base font-extrabold block']">
                {{ formatCurrency(item.total_price) }}
              </span>

              <!-- Participant Paid Name Badge -->
              <span
                v-if="getItemClaimStatus(item)"
                class="mt-1 px-2.5 py-0.5 rounded-full text-[9px] font-extrabold bg-slate-200 text-slate-700 border border-slate-300 flex items-center gap-1"
              >
                ✓ Paid by {{ getItemClaimStatus(item).guest_name }} ({{ formatCurrency(getItemClaimStatus(item).amount_paid) }})
              </span>
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
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { formatCurrency, formatDate } from '@/Utils/formatters';
import {
  Users as UsersIcon,
  CheckSquare as CheckSquareIcon,
  Check as CheckIcon,
  Calculator as CalculatorIcon,
  Info as InfoIcon,
  CheckCircle as CheckCircleIcon,
} from 'lucide-vue-next';

const props = defineProps({
  receipt: { type: Object, required: true },
  sessionUrl: { type: String, required: true },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

const guestName = ref('');
const selectedItemIds = ref([]);

const getItemClaimStatus = (item) => {
  if (item.session_claims && item.session_claims.length > 0) {
    return item.session_claims[0];
  }
  return null;
};

const toggleItemSelection = (itemId) => {
  const idx = selectedItemIds.value.indexOf(itemId);
  if (idx > -1) {
    selectedItemIds.value.splice(idx, 1);
  } else {
    selectedItemIds.value.push(itemId);
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
