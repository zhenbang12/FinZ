<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Top Navigation Header -->
      <div class="flex items-center justify-between">
        <Link href="/receipts" class="text-xs font-bold text-slate-600 hover:text-slate-900 flex items-center gap-1">
          ← Back to Receipts
        </Link>
        <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200 flex items-center gap-1.5">
          <SparklesIcon class="w-3.5 h-3.5 text-slate-700" />
          {{ receipt.raw_ocr_data?.ocr_engine || 'Google Gemini AI Vision' }}
        </span>
      </div>

      <!-- Live Group Session Share Banner -->
      <div class="rounded-2xl p-5 bg-slate-900 text-white shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="space-y-1">
          <div class="flex items-center space-x-2">
            <UsersIcon class="w-5 h-5 text-amber-400" />
            <h3 class="font-bold text-base text-white">Live Group Session Splitting</h3>
          </div>
          <p class="text-xs text-slate-200 font-medium">
            Generate a guest link so anyone can join without an account, tick what they ate, and pay their share!
          </p>
        </div>

        <div v-if="!shareUrl">
          <button
            @click="createSession"
            class="px-5 py-2.5 rounded-full bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs shadow-md transition-all flex items-center gap-2"
          >
            <Share2Icon class="w-4 h-4" />
            <span>Create Live Group Session</span>
          </button>
        </div>

        <div v-else class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
          <input
            type="text"
            readonly
            :value="shareUrl"
            class="px-3 py-2 rounded-xl bg-slate-950/80 border border-slate-700 text-amber-300 text-xs font-mono w-full sm:w-64 focus:outline-none"
          />
          <button
            @click="copyShareUrl"
            class="px-4 py-2 rounded-full bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs transition-all shrink-0 flex items-center justify-center gap-1.5"
          >
            <CopyIcon class="w-3.5 h-3.5" />
            <span>{{ copied ? 'Copied Link!' : 'Copy Guest Link' }}</span>
          </button>
        </div>
      </div>

      <!-- Receipt Summary Header -->
      <div class="minimal-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Merchant / Vendor</span>
          <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mt-0.5">
            {{ receipt.merchant_name || 'Extracted Receipt' }}
          </h2>
          <p class="text-xs text-slate-500 mt-1 font-medium">Receipt ID: #{{ receipt.id }} • {{ formatDate(receipt.created_at) }}</p>
        </div>

        <div class="flex items-center space-x-4 border-t md:border-t-0 border-slate-100 pt-3 md:pt-0">
          <div class="text-right">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Bill</span>
            <span class="text-2xl font-black text-slate-900 block">{{ formatCurrency(receipt.total_amount) }}</span>
          </div>
        </div>
      </div>

      <!-- Interactive Bill Splitting Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Line Items Selector (2 cols) -->
        <div class="minimal-card lg:col-span-2 p-6 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
              <CheckSquareIcon class="w-5 h-5 text-slate-700" />
              <span>Extracted Line Items (Select Items You Consumed)</span>
            </h3>
            <button
              @click="toggleSelectAll"
              class="text-xs font-bold text-slate-600 hover:text-slate-900"
            >
              {{ isAllSelected ? 'Deselect All' : 'Select All Items' }}
            </button>
          </div>

          <div class="space-y-2">
            <div
              v-for="item in receipt.items"
              :key="item.id"
              @click="getItemClaimStatus(item) ? null : toggleItemClaim(item.id)"
              :class="[
                getItemClaimStatus(item)
                  ? 'bg-slate-100/90 text-slate-400 border-slate-200 opacity-80 cursor-default'
                  : (claimedItemIds.includes(item.id)
                      ? 'bg-slate-900 text-white border-slate-900 shadow-sm cursor-pointer'
                      : 'bg-slate-50 text-slate-900 border-slate-200 hover:border-slate-300 cursor-pointer'),
                'p-4 rounded-xl border flex items-center justify-between transition-all'
              ]"
            >
              <div class="flex items-center space-x-3.5 min-w-0">
                <div
                  v-if="!getItemClaimStatus(item)"
                  :class="[
                    claimedItemIds.includes(item.id)
                      ? 'bg-white text-slate-900 border-white'
                      : 'border-slate-300 bg-white text-transparent',
                    'w-5 h-5 rounded border flex items-center justify-center transition-colors shrink-0'
                  ]"
                >
                  <CheckIcon class="w-3.5 h-3.5 stroke-[3]" />
                </div>

                <div v-else class="w-5 h-5 rounded bg-slate-200 text-slate-500 flex items-center justify-center shrink-0">
                  <CheckIcon class="w-3.5 h-3.5 stroke-[3]" />
                </div>

                <div class="min-w-0">
                  <span :class="[getItemClaimStatus(item) ? 'line-through text-slate-400' : (claimedItemIds.includes(item.id) ? 'text-white' : 'text-slate-900'), 'font-bold text-sm block truncate']">
                    {{ item.name }}
                  </span>
                  <span :class="[claimedItemIds.includes(item.id) ? 'text-slate-300' : 'text-slate-500', 'text-xs block font-medium']">
                    Qty: {{ item.quantity }} × {{ formatCurrency(item.unit_price) }}
                  </span>
                </div>
              </div>

              <div class="text-right shrink-0 flex flex-col items-end space-y-1">
                <span :class="[getItemClaimStatus(item) ? 'line-through text-slate-400' : (claimedItemIds.includes(item.id) ? 'text-white' : 'text-slate-900'), 'text-base font-extrabold block']">
                  {{ formatCurrency(item.total_price) }}
                </span>

                <!-- Participant Paid Badge & Undo Claim Button -->
                <div v-if="getItemClaimStatus(item)" class="flex items-center space-x-1.5">
                  <span class="px-2.5 py-0.5 rounded-full text-[9px] font-extrabold bg-slate-200 text-slate-700 border border-slate-300 flex items-center gap-1">
                    ✓ Paid by {{ getItemClaimStatus(item).guest_name }} ({{ formatCurrency(getItemClaimStatus(item).amount_paid) }})
                  </span>
                  <button
                    type="button"
                    @click.stop="undoOwnerClaim(getItemClaimStatus(item))"
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

        <!-- Right Column: Pro-Rata Math Breakdown & Save Expense Form (1 col) -->
        <div class="minimal-card p-6 space-y-6 flex flex-col justify-between">
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
              <CalculatorIcon class="w-5 h-5 text-slate-700" />
              <span>Pro-Rata Calculation</span>
            </h3>

            <!-- Explainer Alert -->
            <div class="p-3.5 rounded-xl bg-slate-100 border border-slate-200 text-xs text-slate-700 space-y-1">
              <span class="font-bold block flex items-center gap-1">
                <InfoIcon class="w-4 h-4 shrink-0 text-slate-900" />
                SRS Pro-Rata Formula:
              </span>
              <p class="text-[11px] leading-relaxed text-slate-600">
                Tax, service fee, & discounts are calculated strictly proportional to your claimed items ({{ proRataData.pro_rata_percentage }}% of receipt).
              </p>
            </div>

            <!-- Mathematical Breakdown Card -->
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
              <div class="flex justify-between text-xs text-slate-700 font-medium">
                <span>Claimed Items Subtotal:</span>
                <span class="font-bold text-slate-900">{{ formatCurrency(proRataData.claimed_subtotal) }}</span>
              </div>

              <div class="flex justify-between text-xs text-slate-500 font-medium">
                <span>Pro-Rata SST Tax Share:</span>
                <span class="font-semibold text-slate-700">
                  {{ proRataData.is_tax_inclusive ? formatCurrency(proRataData.tax_share) + ' (Included)' : '+ ' + formatCurrency(proRataData.tax_share) }}
                </span>
              </div>

              <div class="flex justify-between text-xs text-slate-500 font-medium">
                <span>Pro-Rata Service Charge:</span>
                <span class="font-semibold text-slate-700">+ {{ formatCurrency(proRataData.service_charge_share) }}</span>
              </div>

              <div v-if="proRataData.discount_share > 0" class="flex justify-between text-xs text-emerald-700 font-bold">
                <span>Pro-Rata Discount Deducted:</span>
                <span>- {{ formatCurrency(proRataData.discount_share) }}</span>
              </div>

              <div v-if="Math.abs(proRataData.rounding_share) > 0" class="flex justify-between text-xs text-slate-500 font-medium">
                <span>Pro-Rata Rounding:</span>
                <span>{{ proRataData.rounding_share > 0 ? '+' : '' }} {{ formatCurrency(proRataData.rounding_share) }}</span>
              </div>

              <div class="pt-3 border-t border-slate-200 flex justify-between items-center">
                <span class="text-xs font-bold text-slate-900 uppercase tracking-wider">Your Final Total</span>
                <span class="text-2xl font-black text-emerald-600">{{ formatCurrency(proRataData.final_total) }}</span>
              </div>
            </div>
          </div>

          <!-- Log Expense Action Form -->
          <div class="space-y-4 border-t border-slate-100 pt-4">
            <!-- Double-Logging Prevention Badge -->
            <div v-if="receipt.status === 'claimed'" class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs space-y-2">
              <div class="flex items-center space-x-2 font-bold">
                <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
                <span>Expense Logged in Financial Ledger</span>
              </div>
              <Link href="/transactions" class="text-xs font-bold text-emerald-800 underline block">
                View Ledger Transactions →
              </Link>
            </div>

            <form v-else @submit.prevent="submitClaimedExpense" class="space-y-4">
              <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                Log Calculated Total as Expense
              </h4>

              <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Select Funding Account</label>
                <select
                  v-model="claimForm.account_id"
                  required
                  class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm focus:outline-none focus:border-slate-900"
                >
                  <option value="" disabled>Select account</option>
                  <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                    {{ acc.name }} ({{ formatCurrency(acc.balance) }})
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Expense Category</label>
                <select
                  v-model="claimForm.category_id"
                  required
                  class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm focus:outline-none focus:border-slate-900"
                >
                  <option value="" disabled>Select category</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Notes</label>
                <input
                  v-model="claimForm.notes"
                  type="text"
                  :placeholder="`SmartSplit from ${receipt.merchant_name || 'Receipt'}`"
                  class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-xs focus:outline-none focus:border-slate-900"
                />
              </div>

              <button
                type="submit"
                :disabled="claimedItemIds.length === 0 || claimForm.processing"
                class="minimal-btn-primary w-full py-3.5 text-sm font-bold shadow-md disabled:opacity-40 disabled:cursor-not-allowed"
              >
                Log {{ formatCurrency(proRataData.final_total) }} Expense
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
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
  RotateCcw as RotateCcwIcon,
  CheckCircle as CheckCircleIcon,
} from 'lucide-vue-next';

const props = defineProps({
  receipt: { type: Object, required: true },
  accounts: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  shareUrl: { type: String, default: null },
});

const copied = ref(false);

const getItemClaimStatus = (item) => {
  if (item.session_claims && item.session_claims.length > 0) {
    return item.session_claims[0];
  }
  return null;
};

// Initially claim unclaimed items
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

// Pro-Rata Math Calculation
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
