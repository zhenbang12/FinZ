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
              @click="toggleItemClaim(item.id)"
              :class="[
                claimedItemIds.includes(item.id)
                  ? 'bg-slate-900 text-white border-slate-900 shadow-sm'
                  : 'bg-slate-50 text-slate-900 border-slate-200 hover:border-slate-300',
                'p-4 rounded-xl border flex items-center justify-between cursor-pointer transition-all'
              ]"
            >
              <div class="flex items-center space-x-3.5 min-w-0">
                <div
                  :class="[
                    claimedItemIds.includes(item.id)
                      ? 'bg-white text-slate-900 border-white'
                      : 'border-slate-300 bg-white text-transparent',
                    'w-5 h-5 rounded border flex items-center justify-center transition-colors shrink-0'
                  ]"
                >
                  <CheckIcon class="w-3.5 h-3.5 stroke-[3]" />
                </div>

                <div class="min-w-0">
                  <span :class="[claimedItemIds.includes(item.id) ? 'text-white' : 'text-slate-900', 'font-bold text-sm block truncate']">{{ item.name }}</span>
                  <span :class="[claimedItemIds.includes(item.id) ? 'text-slate-300' : 'text-slate-500', 'text-xs block font-medium']">
                    Qty: {{ item.quantity }} × {{ formatCurrency(item.unit_price) }}
                  </span>
                </div>
              </div>

              <div class="text-right shrink-0">
                <span :class="[claimedItemIds.includes(item.id) ? 'text-white' : 'text-slate-900', 'text-base font-extrabold block']">{{ formatCurrency(item.total_price) }}</span>
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

          <!-- REQ-3.5: Log Expense Action Form -->
          <form @submit.prevent="submitClaimedExpense" class="space-y-4 border-t border-slate-100 pt-4">
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
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatCurrency, formatDate } from '@/Utils/formatters';
import {
  CheckSquare as CheckSquareIcon,
  Check as CheckIcon,
  Calculator as CalculatorIcon,
  Info as InfoIcon,
  Sparkles as SparklesIcon,
} from 'lucide-vue-next';

const props = defineProps({
  receipt: { type: Object, required: true },
  accounts: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
});

// Initially claim all items by default
const claimedItemIds = ref(props.receipt.items ? props.receipt.items.map(i => i.id) : []);

const isAllSelected = computed(() => {
  return claimedItemIds.value.length === (props.receipt.items?.length || 0);
});

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    claimedItemIds.value = [];
  } else {
    claimedItemIds.value = props.receipt.items.map(i => i.id);
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

// Pro-Rata Math Calculation
const proRataData = computed(() => {
  const claimedItems = (props.receipt.items || []).filter(i => claimedItemIds.value.includes(i.id));
  const claimedSubtotal = claimedItems.reduce((sum, item) => sum + parseFloat(item.total_price), 0);
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
    total_tax_share: netAdjustment,
    final_total: finalTotal,
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
