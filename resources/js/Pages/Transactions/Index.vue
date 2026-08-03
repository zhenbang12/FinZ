<template>
  <AppLayout>
    <div class="space-y-6 max-w-5xl mx-auto pb-12">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-950 flex items-center gap-2">
            <span>Financial Ledger</span>
          </h2>
          <p class="text-xs sm:text-sm text-slate-600 mt-1 font-bold">Manual expense logging, income & double-entry cross-account transfers in MYR.</p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
          <button
            @click="openModal('expense')"
            class="px-4 py-2.5 rounded-2xl bg-slate-950 text-white font-black text-xs flex items-center justify-center gap-1.5 border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a] hover:bg-slate-900 active:translate-x-0.5 active:translate-y-0.5 transition-all"
          >
            <PlusIcon class="w-4 h-4 text-amber-400" />
            <span>Expense</span>
          </button>
          <button
            @click="openModal('income')"
            class="px-4 py-2.5 rounded-2xl bg-emerald-400 text-slate-950 font-black text-xs flex items-center justify-center gap-1.5 border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a] hover:bg-emerald-300 active:translate-x-0.5 active:translate-y-0.5 transition-all"
          >
            <PlusIcon class="w-4 h-4 text-slate-950" />
            <span>Income</span>
          </button>
          <button
            @click="openModal('transfer')"
            class="px-4 py-2.5 rounded-2xl bg-sky-400 text-slate-950 font-black text-xs flex items-center justify-center gap-1.5 border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a] hover:bg-sky-300 active:translate-x-0.5 active:translate-y-0.5 transition-all"
          >
            <ArrowRightLeftIcon class="w-4 h-4 text-slate-950" />
            <span>Transfer</span>
          </button>
        </div>
      </div>

      <!-- Filter Bar -->
      <div class="p-5 rounded-3xl bg-white border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] space-y-3">
        <div class="flex items-center justify-between cursor-pointer select-none" @click="showFilters = !showFilters">
          <div class="flex items-center space-x-2">
            <span class="text-xs font-black text-slate-950 uppercase tracking-wider flex items-center gap-1.5">
              <FilterIcon class="w-4 h-4 text-slate-950" />
              Filter History
            </span>
            <span v-if="hasActiveFilters" class="px-2 py-0.5 rounded-full bg-amber-400 text-slate-950 text-[10px] font-black border border-slate-950">
              Active
            </span>
          </div>

          <div class="flex items-center space-x-3">
            <button
              v-if="hasActiveFilters"
              @click.stop="resetFilters"
              class="text-xs font-black text-rose-600 hover:text-rose-700 transition-colors uppercase"
            >
              Clear Filters
            </button>
            <ChevronDownIcon :class="[showFilters ? 'rotate-180' : '', 'w-4 h-4 text-slate-950 transition-transform']" />
          </div>
        </div>

        <div v-show="showFilters" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 pt-2 border-t-2 border-slate-950/10">
          <!-- Search input -->
          <div>
            <input
              v-model="filterForm.search"
              @input="applyFilters"
              type="text"
              placeholder="Search notes..."
              class="w-full px-3.5 py-2.5 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-xs focus:outline-none"
            />
          </div>

          <!-- Type filter -->
          <div>
            <select
              v-model="filterForm.type"
              @change="applyFilters"
              class="w-full px-3 py-2.5 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-xs focus:outline-none"
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
              class="w-full px-3 py-2.5 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-xs focus:outline-none"
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
              class="w-full px-3 py-2.5 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-xs focus:outline-none"
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
              class="w-full px-3 py-2.5 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-xs focus:outline-none"
            />
          </div>
        </div>
      </div>

      <!-- Transactions List -->
      <div class="p-6 rounded-3xl bg-white border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] space-y-4">
        <div v-if="transactions.data.length === 0" class="text-center py-12 text-slate-500 text-sm font-bold">
          No transactions match your search/filters.
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="tx in transactions.data"
            :key="tx.id"
            class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a] transition-all group"
          >
            <div class="flex items-center space-x-3.5 min-w-0">
              <div
                :class="[
                  tx.type === 'expense' ? 'bg-rose-400 text-slate-950' :
                  tx.type === 'income' ? 'bg-emerald-400 text-slate-950' :
                  'bg-sky-400 text-slate-950',
                  'w-10 h-10 rounded-2xl flex items-center justify-center border-2 border-slate-950 shrink-0 shadow-[2px_2px_0px_#0f172a]'
                ]"
              >
                <ArrowUpRightIcon v-if="tx.type === 'expense'" class="w-4 h-4" />
                <ArrowDownLeftIcon v-else-if="tx.type === 'income'" class="w-4 h-4" />
                <ArrowRightLeftIcon v-else class="w-4 h-4" />
              </div>

              <div class="min-w-0">
                <div class="flex items-center space-x-2">
                  <span class="font-black text-sm text-slate-950 truncate">
                    {{ tx.notes || (tx.category ? tx.category.name : 'Transaction') }}
                  </span>
                  <span
                    v-if="tx.receipt_id"
                    class="px-2 py-0.5 rounded-full text-[9px] font-black bg-amber-400 text-slate-950 border border-slate-950 shrink-0 uppercase"
                  >
                    SmartSplit
                  </span>
                </div>

                <div class="text-xs text-slate-600 flex items-center gap-2 mt-0.5 truncate font-bold">
                  <span class="font-black text-slate-950">{{ tx.account?.name }}</span>
                  <span v-if="tx.type === 'transfer' && tx.destination_account" class="text-slate-950 font-black">→ {{ tx.destination_account.name }}</span>
                  <span v-if="tx.category">• {{ tx.category.name }}</span>
                  <span class="text-slate-500">• {{ formatDate(tx.date) }}</span>
                </div>
              </div>
            </div>

            <div class="flex items-center space-x-2 shrink-0">
              <div class="text-right">
                <span
                  :class="[
                    tx.type === 'expense' ? 'text-rose-600' :
                    tx.type === 'income' ? 'text-emerald-600' :
                    'text-slate-950',
                    'text-base font-black font-mono block'
                  ]"
                >
                  {{ tx.type === 'expense' ? '-' : (tx.type === 'income' ? '+' : '') }}{{ formatCurrency(tx.amount) }}
                </span>
              </div>

              <!-- Edit Transaction Button -->
              <button
                @click="openEditModal(tx)"
                class="p-2 rounded-xl bg-slate-100 border border-slate-950 text-slate-950 hover:bg-amber-300 transition-colors"
                title="Edit transaction"
              >
                <PencilIcon class="w-3.5 h-3.5" />
              </button>

              <!-- Delete Transaction Button -->
              <button
                @click="deleteTransaction(tx)"
                class="p-2 rounded-xl bg-rose-500 border border-slate-950 text-white hover:bg-rose-600 transition-colors"
                title="Delete transaction"
              >
                <Trash2Icon class="w-3.5 h-3.5" />
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
              link.active ? 'bg-slate-950 text-amber-300 font-black border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a]' : 'bg-slate-100 text-slate-900 border-2 border-slate-950 hover:bg-slate-200 font-bold',
              !link.url ? 'opacity-30 cursor-not-allowed' : '',
              'px-3.5 py-1.5 rounded-2xl text-xs transition-colors'
            ]"
          />
        </div>
      </div>
    </div>

    <!-- Modal Form -->
    <div v-if="showModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl border-3 border-slate-950 shadow-[8px_8px_0px_#0f172a] max-w-lg w-full p-6 space-y-5 animate-scale-up text-slate-950">
        <div class="flex items-center justify-between border-b-2 border-slate-950 pb-3">
          <h3 class="text-xl font-black text-slate-950">
            {{ editingTransactionId ? 'Edit Entry' : (modalType === 'expense' ? 'Log Expense' : (modalType === 'income' ? 'Log Income' : 'Cross-Account Transfer')) }}
          </h3>
          <button @click="closeModal" class="w-8 h-8 rounded-full bg-slate-100 border-2 border-slate-950 flex items-center justify-center text-slate-950 font-bold hover:bg-rose-100 transition-colors">
            <XIcon class="w-4 h-4" />
          </button>
        </div>

        <form @submit.prevent="submitTransaction" class="space-y-4">
          <div>
            <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">
              Amount (MYR / RM)
            </label>
            <div class="relative">
              <span class="absolute left-4 top-3 text-slate-950 font-black">RM</span>
              <input
                v-model="form.amount"
                type="number"
                step="0.01"
                required
                placeholder="0.00"
                class="w-full pl-12 pr-4 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-black font-mono text-lg focus:outline-none"
              />
            </div>

            <!-- Insufficient Balance Real-Time Warning -->
            <div
              v-if="form.type !== 'income' && selectedOriginAccount && (parseFloat(form.amount) > (selectedOriginAccount.balance + (editingTransactionId ? originalAmount : 0)))"
              class="mt-2 p-2.5 rounded-2xl bg-rose-300 border-2 border-slate-950 text-slate-950 text-xs font-black flex items-center gap-1.5 shadow-[2px_2px_0px_#0f172a]"
            >
              <AlertTriangleIcon class="w-4 h-4 text-slate-950 shrink-0" />
              <span>Insufficient funds in {{ selectedOriginAccount.name }}! Available: {{ formatCurrency(selectedOriginAccount.balance + (editingTransactionId ? originalAmount : 0)) }}</span>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <div>
              <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">
                {{ modalType === 'transfer' ? 'From Account' : 'Account' }}
              </label>
              <select
                v-model="form.account_id"
                required
                class="w-full px-3.5 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-sm focus:outline-none"
              >
                <option value="" disabled>Select account</option>
                <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                  {{ acc.name }} ({{ formatCurrency(acc.balance) }})
                </option>
              </select>
            </div>

            <div v-if="modalType === 'transfer'">
              <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">
                To Account
              </label>
              <select
                v-model="form.destination_account_id"
                required
                class="w-full px-3.5 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-sm focus:outline-none"
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
              <div class="flex items-center justify-between mb-1">
                <label class="block text-xs font-black text-slate-900 uppercase tracking-wider">
                  Category
                </label>
                <div class="flex items-center space-x-2">
                  <button
                    v-if="form.category_id && selectedCategory?.user_id"
                    type="button"
                    @click="openEditCategoryModal(selectedCategory)"
                    class="text-[11px] font-black text-slate-950 hover:underline"
                  >
                    ✏️ Edit
                  </button>
                  <button
                    type="button"
                    @click="openNewCategoryModal"
                    class="text-[11px] font-black text-slate-950 hover:underline"
                  >
                    + Add Category
                  </button>
                </div>
              </div>
              <select
                v-model="form.category_id"
                required
                class="w-full px-3.5 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-sm focus:outline-none"
              >
                <option value="" disabled>Select category</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.name }} {{ cat.user_id ? '(Custom)' : '' }}
                </option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">
              Date
            </label>
            <input
              v-model="form.date"
              type="date"
              required
              class="w-full px-3.5 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-sm focus:outline-none"
            />
          </div>

          <div>
            <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">
              Notes
            </label>
            <input
              v-model="form.notes"
              type="text"
              placeholder="e.g. Dinner with friends, Fuel top up"
              class="w-full px-4 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-sm focus:outline-none"
            />
          </div>

          <div class="flex items-center justify-end space-x-3 pt-3 border-t-2 border-slate-950">
            <button
              type="button"
              @click="closeModal"
              class="px-5 py-2.5 rounded-2xl text-slate-800 hover:bg-slate-100 text-xs font-bold"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="form.processing || (form.type !== 'income' && selectedOriginAccount && parseFloat(form.amount) > (selectedOriginAccount.balance + (editingTransactionId ? originalAmount : 0)))"
              class="px-6 py-2.5 rounded-2xl bg-slate-950 text-amber-300 border-2 border-slate-950 font-black text-xs shadow-[3px_3px_0px_#0f172a] hover:bg-slate-900 active:translate-x-0.5 active:translate-y-0.5 disabled:opacity-50"
            >
              {{ editingTransactionId ? 'Update Entry' : 'Save Entry' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Category Create / Edit Modal -->
    <div v-if="showCategoryModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl border-3 border-slate-950 shadow-[8px_8px_0px_#0f172a] max-w-md w-full p-6 space-y-5 animate-scale-up text-slate-950">
        <div class="flex items-center justify-between border-b-2 border-slate-950 pb-3">
          <h3 class="text-xl font-black text-slate-950">
            {{ editingCategory ? 'Edit Category' : 'Create New Category' }}
          </h3>
          <button @click="closeCategoryModal" class="w-8 h-8 rounded-full bg-slate-100 border-2 border-slate-950 flex items-center justify-center text-slate-950 font-bold hover:bg-rose-100 transition-colors">
            <XIcon class="w-4 h-4" />
          </button>
        </div>

        <form @submit.prevent="submitCategory" class="space-y-4">
          <div>
            <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">
              Category Name
            </label>
            <input
              v-model="categoryForm.name"
              type="text"
              required
              placeholder="e.g. Subscriptions, Gaming, Medical"
              class="w-full px-4 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-sm focus:outline-none"
            />
          </div>

          <div>
            <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">
              Category Type
            </label>
            <select
              v-model="categoryForm.type"
              class="w-full px-3.5 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-sm focus:outline-none"
            >
              <option value="expense">Expense</option>
              <option value="income">Income</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">
              Color Tag
            </label>
            <div class="flex items-center space-x-3">
              <input
                v-model="categoryForm.color"
                type="color"
                class="w-12 h-12 rounded-2xl bg-white border-2 border-slate-950 cursor-pointer p-1"
              />
              <span class="text-xs font-mono font-black text-slate-950 uppercase">{{ categoryForm.color }}</span>
            </div>
          </div>

          <div class="flex items-center justify-between pt-3 border-t-2 border-slate-950">
            <button
              v-if="editingCategory && editingCategory.user_id"
              type="button"
              @click="deleteCategory(editingCategory)"
              class="text-xs font-black text-rose-600 hover:text-rose-700 uppercase"
            >
              Delete Category
            </button>
            <div v-else></div>

            <div class="flex items-center space-x-3">
              <button
                type="button"
                @click="closeCategoryModal"
                class="px-4 py-2 rounded-2xl text-slate-800 hover:bg-slate-100 text-xs font-bold"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="categoryForm.processing"
                class="px-6 py-2.5 rounded-2xl bg-slate-950 text-amber-300 border-2 border-slate-950 font-black text-xs shadow-[3px_3px_0px_#0f172a] disabled:opacity-50"
              >
                {{ editingCategory ? 'Update Category' : 'Create Category' }}
              </button>
            </div>
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
  ChevronDown as ChevronDownIcon,
  Pencil as PencilIcon,
  Trash2 as Trash2Icon,
  ArrowUpRight as ArrowUpRightIcon,
  ArrowDownLeft as ArrowDownLeftIcon,
  X as XIcon,
  AlertTriangle as AlertTriangleIcon,
} from 'lucide-vue-next';

const props = defineProps({
  transactions: { type: Object, default: () => ({ data: [], links: [] }) },
  accounts: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
});

const showModal = ref(false);
const modalType = ref('expense');
const editingTransactionId = ref(null);
const originalAmount = ref(0);

const showCategoryModal = ref(false);
const editingCategory = ref(null);

const categoryForm = useForm({
  name: '',
  type: 'expense',
  color: '#6366f1',
});

const selectedCategory = computed(() => {
  return props.categories.find(c => c.id === form.category_id);
});

const selectedOriginAccount = computed(() => {
  return props.accounts.find(a => a.id === form.account_id) || null;
});

const openNewCategoryModal = () => {
  editingCategory.value = null;
  categoryForm.name = '';
  categoryForm.type = 'expense';
  categoryForm.color = '#6366f1';
  showCategoryModal.value = true;
};

const openEditCategoryModal = (cat) => {
  if (!cat) return;
  editingCategory.value = cat;
  categoryForm.name = cat.name;
  categoryForm.type = cat.type || 'expense';
  categoryForm.color = cat.color || '#6366f1';
  showCategoryModal.value = true;
};

const closeCategoryModal = () => {
  showCategoryModal.value = false;
  editingCategory.value = null;
};

const submitCategory = () => {
  if (editingCategory.value) {
    categoryForm.put(`/categories/${editingCategory.value.id}`, {
      onSuccess: () => closeCategoryModal(),
    });
  } else {
    categoryForm.post('/categories', {
      onSuccess: () => closeCategoryModal(),
    });
  }
};

const deleteCategory = (cat) => {
  if (confirm(`Delete custom category "${cat.name}"?`)) {
    router.delete(`/categories/${cat.id}`, {
      onSuccess: () => closeCategoryModal(),
    });
  }
};

const filterForm = ref({
  search: props.filters.search || '',
  type: props.filters.type || '',
  account_id: props.filters.account_id || '',
  category_id: props.filters.category_id || '',
  start_date: props.filters.start_date || '',
});

const showFilters = ref(!!(props.filters.search || props.filters.type || props.filters.account_id || props.filters.category_id || props.filters.start_date));

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
  editingTransactionId.value = null;
  originalAmount.value = 0;
  modalType.value = type;
  form.type = type;
  form.account_id = props.accounts[0]?.id || '';
  form.destination_account_id = type === 'transfer' ? (props.accounts[1]?.id || '') : '';
  form.category_id = type === 'transfer' ? '' : (props.categories[0]?.id || '');
  form.amount = '';
  form.date = todayStr;
  form.notes = '';
  showModal.value = true;
};

const openEditModal = (tx) => {
  editingTransactionId.value = tx.id;
  originalAmount.value = parseFloat(tx.amount) || 0;
  modalType.value = tx.type;
  form.type = tx.type;
  form.account_id = tx.account_id || '';
  form.destination_account_id = tx.type === 'transfer' ? (tx.destination_account_id || '') : '';
  form.category_id = tx.type === 'transfer' ? '' : (tx.category_id || '');
  form.amount = tx.amount;
  form.date = tx.date ? tx.date.substring(0, 10) : todayStr;
  form.notes = tx.notes || '';
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  editingTransactionId.value = null;
  originalAmount.value = 0;
};

const submitTransaction = () => {
  if (form.type === 'transfer') {
    form.category_id = null;
  } else {
    form.destination_account_id = null;
  }
  if (editingTransactionId.value) {
    form.put(`/transactions/${editingTransactionId.value}`, {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post('/transactions', {
      onSuccess: () => closeModal(),
    });
  }
};

const deleteTransaction = (tx) => {
  if (confirm(`Delete this transaction of ${formatCurrency(tx.amount)}? Associated account balances will automatically revert.`)) {
    router.delete(`/transactions/${tx.id}`);
  }
};
</script>
