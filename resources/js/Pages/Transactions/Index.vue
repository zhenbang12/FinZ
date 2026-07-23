<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
            Financial Ledger
          </h2>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 font-normal">Manual expense logging, income & double-entry cross-account transfers in MYR.</p>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="openModal('expense')"
            class="minimal-btn-primary flex items-center justify-center space-x-2 px-5 py-2.5 text-xs font-semibold"
          >
            <PlusIcon class="w-4 h-4" />
            <span>Log Expense</span>
          </button>
          <button
            @click="openModal('transfer')"
            class="minimal-btn-secondary flex items-center justify-center space-x-2 px-4 py-2.5 text-xs font-semibold"
          >
            <ArrowRightLeftIcon class="w-4 h-4 text-sky-600" />
            <span>Transfer</span>
          </button>
        </div>
      </div>

      <!-- Filter Bar -->
      <div class="minimal-card p-4 sm:p-5 space-y-3">
        <div class="flex items-center justify-between">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
            <FilterIcon class="w-4 h-4 text-slate-600" />
            Filter History
          </span>
          <button
            v-if="hasActiveFilters"
            @click="resetFilters"
            class="text-xs font-bold text-rose-600 hover:text-rose-700 transition-colors"
          >
            Clear Filters
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
          <!-- Search input -->
          <div>
            <input
              v-model="filterForm.search"
              @input="applyFilters"
              type="text"
              placeholder="Search notes..."
              class="w-full px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-xs focus:outline-none focus:border-slate-900"
            />
          </div>

          <!-- Type filter -->
          <div>
            <select
              v-model="filterForm.type"
              @change="applyFilters"
              class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-xs focus:outline-none focus:border-slate-900"
            >
              <option value="">All Types (Expense, Income, Transfer)</option>
              <option value="expense">Expense</option>
              <option value="income">Income</option>
              <option value="transfer">Cross-Account Transfer</option>
            </select>
          </div>

          <!-- Account filter -->
          <div>
            <select
              v-model="filterForm.account_id"
              @change="applyFilters"
              class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-xs focus:outline-none focus:border-slate-900"
            >
              <option value="">All Accounts</option>
              <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                {{ acc.name }}
              </option>
            </select>
          </div>

          <!-- Category filter -->
          <div>
            <select
              v-model="filterForm.category_id"
              @change="applyFilters"
              class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-xs focus:outline-none focus:border-slate-900"
            >
              <option value="">All Categories</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>

          <!-- Start Date -->
          <div>
            <input
              v-model="filterForm.start_date"
              @change="applyFilters"
              type="date"
              class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-xs focus:outline-none focus:border-slate-900"
            />
          </div>
        </div>
      </div>

      <!-- Transactions List -->
      <div class="minimal-card p-6 space-y-4">
        <div v-if="transactions.data.length === 0" class="text-center py-12 text-slate-400 text-sm font-medium">
          No transactions match your search/filters.
        </div>

        <div v-else class="space-y-2.5">
          <div
            v-for="tx in transactions.data"
            :key="tx.id"
            class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50 hover:bg-slate-100/80 border border-slate-200/60 transition-all group"
          >
            <div class="flex items-center space-x-3.5 min-w-0">
              <div
                :class="[
                  tx.type === 'expense' ? 'bg-rose-100 text-rose-700 border-rose-200' :
                  tx.type === 'income' ? 'bg-emerald-100 text-emerald-700 border-emerald-200' :
                  'bg-sky-100 text-sky-700 border-sky-200',
                  'w-10 h-10 rounded-full flex items-center justify-center border shrink-0'
                ]"
              >
                <ArrowUpRightIcon v-if="tx.type === 'expense'" class="w-4 h-4" />
                <ArrowDownLeftIcon v-else-if="tx.type === 'income'" class="w-4 h-4" />
                <ArrowRightLeftIcon v-else class="w-4 h-4" />
              </div>

              <div class="min-w-0">
                <div class="flex items-center space-x-2">
                  <span class="font-bold text-sm text-slate-900 truncate">
                    {{ tx.notes || (tx.category ? tx.category.name : 'Transaction') }}
                  </span>
                  <span
                    v-if="tx.receipt_id"
                    class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-slate-200 text-slate-700 border border-slate-300 shrink-0"
                  >
                    SmartSplit Receipt
                  </span>
                </div>

                <div class="text-xs text-slate-500 flex items-center gap-2 mt-0.5 truncate font-medium">
                  <span class="font-semibold text-slate-700">{{ tx.account?.name }}</span>
                  <span v-if="tx.destination_account" class="text-sky-600 font-semibold">→ {{ tx.destination_account.name }}</span>
                  <span v-if="tx.category" class="text-slate-400">• {{ tx.category.name }}</span>
                  <span class="text-slate-400">• {{ formatDate(tx.date) }}</span>
                </div>
              </div>
            </div>

            <div class="flex items-center space-x-4 shrink-0">
              <div class="text-right">
                <span
                  :class="[
                    tx.type === 'expense' ? 'text-rose-600' :
                    tx.type === 'income' ? 'text-emerald-600' :
                    'text-slate-900',
                    'text-base font-extrabold block'
                  ]"
                >
                  {{ tx.type === 'expense' ? '-' : (tx.type === 'income' ? '+' : '') }}{{ formatCurrency(tx.amount) }}
                </span>
              </div>

              <button
                @click="deleteTransaction(tx)"
                class="p-2 rounded-full text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors opacity-80 group-hover:opacity-100"
                title="Delete transaction"
              >
                <Trash2Icon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <!-- Pagination Links -->
        <div v-if="transactions.links && transactions.links.length > 3" class="flex items-center justify-center space-x-1.5 pt-4">
          <Link
            v-for="(link, i) in transactions.links"
            :key="i"
            :href="link.url || '#'"
            v-html="link.label"
            :class="[
              link.active ? 'bg-slate-900 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200',
              !link.url ? 'opacity-40 cursor-not-allowed' : '',
              'px-3.5 py-1.5 rounded-full text-xs border border-slate-200 transition-colors'
            ]"
          />
        </div>
      </div>
    </div>

    <!-- Modal Form -->
    <div v-if="showModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="minimal-card max-w-lg w-full p-6 space-y-5 animate-scale-up">
        <div class="flex items-center justify-between">
          <h3 class="text-xl font-bold text-slate-900">
            {{ modalType === 'expense' ? 'Log Expense' : (modalType === 'income' ? 'Log Income' : 'Cross-Account Transfer') }}
          </h3>
          <button @click="closeModal" class="text-slate-400 hover:text-slate-600 p-1">
            <XIcon class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitTransaction" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">
              Amount (MYR / RM)
            </label>
            <div class="relative">
              <span class="absolute left-3.5 top-3 text-slate-900 font-bold">RM</span>
              <input
                v-model="form.amount"
                type="number"
                step="0.01"
                required
                placeholder="0.00"
                class="w-full pl-12 pr-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-bold text-lg focus:outline-none focus:border-slate-900"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">
                {{ modalType === 'transfer' ? 'From Account' : 'Account' }}
              </label>
              <select
                v-model="form.account_id"
                required
                class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm focus:outline-none focus:border-slate-900"
              >
                <option value="" disabled>Select account</option>
                <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                  {{ acc.name }} ({{ formatCurrency(acc.balance) }})
                </option>
              </select>
            </div>

            <div v-if="modalType === 'transfer'">
              <label class="block text-xs font-semibold text-slate-600 mb-1">
                To Account
              </label>
              <select
                v-model="form.destination_account_id"
                required
                class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm focus:outline-none focus:border-slate-900"
              >
                <option value="" disabled>Select destination</option>
                <option
                  v-for="acc in accounts.filter(a => a.id !== form.account_id)"
                  :key="acc.id"
                  :value="acc.id"
                >
                  {{ acc.name }} ({{ formatCurrency(acc.balance) }})
                </option>
              </select>
            </div>

            <div v-else>
              <label class="block text-xs font-semibold text-slate-600 mb-1">
                Category
              </label>
              <select
                v-model="form.category_id"
                required
                class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm focus:outline-none focus:border-slate-900"
              >
                <option value="" disabled>Select category</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.name }}
                </option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
              Date
            </label>
            <input
              v-model="form.date"
              type="date"
              required
              class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm focus:outline-none focus:border-slate-900"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
              Notes
            </label>
            <input
              v-model="form.notes"
              type="text"
              placeholder="e.g. Dinner with friends, Fuel top up"
              class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm focus:outline-none focus:border-slate-900"
            />
          </div>

          <div class="flex items-center justify-end space-x-3 pt-3">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 rounded-full text-slate-600 hover:text-slate-900 text-xs font-semibold"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="form.processing"
              class="minimal-btn-primary px-6 py-2.5 text-xs disabled:opacity-50"
            >
              Save Entry
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatCurrency, formatDate } from '@/Utils/formatters';
import {
  Plus as PlusIcon,
  ArrowRightLeft as ArrowRightLeftIcon,
  Filter as FilterIcon,
  Trash2 as Trash2Icon,
  ArrowUpRight as ArrowUpRightIcon,
  ArrowDownLeft as ArrowDownLeftIcon,
  X as XIcon,
} from 'lucide-vue-next';

const props = defineProps({
  transactions: { type: Object, default: () => ({ data: [], links: [] }) },
  accounts: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
});

const showModal = ref(false);
const modalType = ref('expense');

const filterForm = ref({
  search: props.filters.search || '',
  type: props.filters.type || '',
  account_id: props.filters.account_id || '',
  category_id: props.filters.category_id || '',
  start_date: props.filters.start_date || '',
});

const hasActiveFilters = computed(() => {
  return (
    filterForm.value.search ||
    filterForm.value.type ||
    filterForm.value.account_id ||
    filterForm.value.category_id ||
    filterForm.value.start_date
  );
});

const applyFilters = () => {
  router.get('/transactions', filterForm.value, { preserveState: true, replace: true });
};

const resetFilters = () => {
  filterForm.value = { search: '', type: '', account_id: '', category_id: '', start_date: '' };
  applyFilters();
};

const todayStr = new Date().toISOString().substring(0, 10);

const form = useForm({
  type: 'expense',
  account_id: '',
  destination_account_id: '',
  category_id: '',
  amount: '',
  date: todayStr,
  notes: '',
});

const openModal = (type) => {
  modalType.value = type;
  form.type = type;
  form.account_id = props.accounts[0]?.id || '';
  form.destination_account_id = props.accounts[1]?.id || '';
  form.category_id = props.categories[0]?.id || '';
  form.amount = '';
  form.notes = '';
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
};

const submitTransaction = () => {
  form.post('/transactions', {
    onSuccess: () => closeModal(),
  });
};

const deleteTransaction = (tx) => {
  if (confirm(`Delete this transaction of ${formatCurrency(tx.amount)}? Associated account balances will automatically revert.`)) {
    router.delete(`/transactions/${tx.id}`);
  }
};
</script>
