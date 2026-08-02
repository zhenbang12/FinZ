<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
            Account Management
          </h2>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 font-normal">Organize your accounts by Current, Savings, and E-Wallets with custom ordering.</p>
        </div>

        <button
          @click="openModal('create')"
          class="minimal-btn-primary flex items-center justify-center space-x-2 px-5 py-2.5 text-xs font-semibold w-full sm:w-auto"
        >
          <PlusIcon class="w-4 h-4" />
          <span>Add New Account</span>
        </button>
      </div>

      <!-- Combined Balance Banner & Category Totals Summary (Mobile Grid 2x2, Desktop 4x1) -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <!-- Net Worth Card -->
        <div class="minimal-card p-3.5 sm:p-4 col-span-2 lg:col-span-1 flex flex-col justify-between border-l-4 border-l-slate-900 rounded-2xl">
          <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider block">Total Net Worth</span>
          <div class="text-lg sm:text-xl font-black text-slate-900 mt-1.5">{{ formatCurrency(totalNetWorth) }}</div>
          <span class="text-[10px] text-slate-500 font-semibold mt-1 block">{{ accounts.length }} Total Accounts</span>
        </div>

        <!-- Current Accounts Card -->
        <div class="minimal-card p-3.5 sm:p-4 flex flex-col justify-between border-l-4 border-l-blue-600 rounded-2xl">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Current</span>
            <span class="p-1.5 bg-blue-50 rounded-lg text-blue-600">
              <CreditCardIcon class="w-4 h-4" />
            </span>
          </div>
          <div class="text-lg sm:text-xl font-black text-slate-900 mt-1.5">{{ formatCurrency(categoryTotals.current || 0) }}</div>
          <span class="text-[10px] text-slate-500 mt-1">Daily spending</span>
        </div>

        <!-- Savings Accounts Card -->
        <div class="minimal-card p-3.5 sm:p-4 flex flex-col justify-between border-l-4 border-l-emerald-600 rounded-2xl">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Savings</span>
            <span class="p-1.5 bg-emerald-50 rounded-lg text-emerald-600">
              <PiggyBankIcon class="w-4 h-4" />
            </span>
          </div>
          <div class="text-lg sm:text-xl font-black text-slate-900 mt-1.5">{{ formatCurrency(categoryTotals.savings || 0) }}</div>
          <span class="text-[10px] text-slate-500 mt-1">Emergency & yield</span>
        </div>

        <!-- E-Wallets & Other Card -->
        <div class="minimal-card p-3.5 sm:p-4 flex flex-col justify-between border-l-4 border-l-indigo-600 rounded-2xl">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">E-Wallet & Other</span>
            <span class="p-1.5 bg-indigo-50 rounded-lg text-indigo-600">
              <WalletIcon class="w-4 h-4" />
            </span>
          </div>
          <div class="text-lg sm:text-xl font-black text-slate-900 mt-1.5">{{ formatCurrency(categoryTotals.other || 0) }}</div>
          <span class="text-[10px] text-slate-500 mt-1">TnG, Credit Cards</span>
        </div>
      </div>

      <!-- Categorized Account Lists -->
      <div v-for="section in categorizedSections" :key="section.key" class="space-y-3">
        <!-- Section Header -->
        <div class="flex flex-row items-center justify-between pt-3 border-b border-slate-200/80 pb-2">
          <div class="flex items-center gap-2">
            <component :is="section.iconComponent" class="w-4 h-4 text-slate-700" />
            <h3 class="text-sm sm:text-base font-extrabold text-slate-900">{{ section.title }}</h3>
            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[10px] sm:text-xs font-bold">
              {{ section.items.length }}
            </span>
          </div>
          <span class="text-xs font-bold text-slate-700">
            Subtotal: {{ formatCurrency(section.subtotal) }}
          </span>
        </div>

        <!-- Empty Section State -->
        <div v-if="section.items.length === 0" class="p-5 text-center border border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
          <p class="text-xs text-slate-400 font-medium">No {{ section.title.toLowerCase() }} configured.</p>
        </div>

        <!-- Section Accounts Grid (Mobile Optimized Cards) -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
          <div
            v-for="(acc, index) in section.items"
            :key="acc.id"
            class="minimal-card p-4 flex flex-col justify-between space-y-3 relative"
          >
            <div class="flex items-start justify-between gap-2">
              <div class="flex items-center space-x-3 min-w-0">
                <div
                  class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold text-xs shadow-xs shrink-0"
                  :style="{ backgroundColor: acc.color || '#0f172a' }"
                >
                  {{ acc.name.charAt(0) }}
                </div>
                <div class="min-w-0">
                  <h4 class="font-bold text-sm text-slate-900 truncate">{{ acc.name }}</h4>
                  <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="inline-block px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[9px] uppercase font-bold tracking-wider">
                      {{ getCategoryLabel(acc.category) }}
                    </span>
                    <span class="text-[10px] text-slate-400 font-medium capitalize">({{ acc.type }})</span>
                  </div>
                </div>
              </div>

              <!-- Mobile-Friendly Action & Reorder Buttons -->
              <div class="flex items-center space-x-1 shrink-0">
                <!-- Reorder Up -->
                <button
                  @click="reorderItem(section.items, index, -1)"
                  :disabled="index === 0"
                  class="p-1.5 rounded-lg text-slate-400 hover:text-slate-900 hover:bg-slate-100 disabled:opacity-20 disabled:cursor-not-allowed transition-colors"
                  title="Move Up"
                >
                  <ArrowUpIcon class="w-3.5 h-3.5" />
                </button>
                <!-- Reorder Down -->
                <button
                  @click="reorderItem(section.items, index, 1)"
                  :disabled="index === section.items.length - 1"
                  class="p-1.5 rounded-lg text-slate-400 hover:text-slate-900 hover:bg-slate-100 disabled:opacity-20 disabled:cursor-not-allowed transition-colors"
                  title="Move Down"
                >
                  <ArrowDownIcon class="w-3.5 h-3.5" />
                </button>

                <button
                  @click="openModal('edit', acc)"
                  class="p-1.5 rounded-lg text-slate-400 hover:text-slate-900 hover:bg-slate-100 transition-colors"
                  title="Edit Account"
                >
                  <PencilIcon class="w-3.5 h-3.5" />
                </button>
                <button
                  @click="deleteAccount(acc)"
                  class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                  title="Delete Account"
                >
                  <Trash2Icon class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>

            <div class="pt-2.5 border-t border-slate-100">
              <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Current Balance</span>
              <div class="text-xl font-black text-slate-900 mt-0.5">
                {{ formatCurrency(acc.balance) }}
              </div>
              <div class="flex items-center justify-between text-[11px] text-slate-500 mt-1 font-medium">
                <span>Initial: {{ formatCurrency(acc.initial_balance) }}</span>
                <span class="font-bold text-slate-700">{{ acc.currency }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Account Create / Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="minimal-card max-w-lg w-full p-5 sm:p-6 space-y-4 sm:space-y-5 animate-scale-up">
        <div class="flex items-center justify-between">
          <h3 class="text-lg sm:text-xl font-bold text-slate-900">
            {{ isEditing ? 'Edit Account' : 'Create New Account' }}
          </h3>
          <button @click="closeModal" class="text-slate-400 hover:text-slate-600 p-1">
            <XIcon class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="saveAccount" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
              Account Name
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              placeholder="e.g. Maybank Current, GXBank Savings, Touch 'n Go"
              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm focus:outline-none focus:border-slate-900"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">
                Account Category
              </label>
              <select
                v-model="form.category"
                required
                class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm focus:outline-none focus:border-slate-900"
              >
                <option value="current">Current / Spending Account</option>
                <option value="savings">Savings Account</option>
                <option value="wallet">E-Wallet / App</option>
                <option value="credit_card">Credit Card</option>
                <option value="investment">Investment / Fixed Deposit</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">
                Account Type
              </label>
              <select
                v-model="form.type"
                required
                class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm focus:outline-none focus:border-slate-900"
              >
                <option value="bank">Bank Account</option>
                <option value="e-wallet">E-Wallet</option>
                <option value="cash">Cash</option>
                <option value="credit_card">Credit Card</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">
                Currency
              </label>
              <input
                v-model="form.currency"
                type="text"
                readonly
                class="w-full px-3 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-500 text-sm focus:outline-none cursor-not-allowed"
              />
            </div>

            <div v-if="!isEditing">
              <label class="block text-xs font-semibold text-slate-600 mb-1">
                Initial Starting Balance (MYR)
              </label>
              <input
                v-model="form.initial_balance"
                type="number"
                step="0.01"
                required
                placeholder="0.00"
                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm focus:outline-none focus:border-slate-900"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
              Accent Color
            </label>
            <div class="flex items-center space-x-3">
              <input
                v-model="form.color"
                type="color"
                class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer p-0.5"
              />
              <span class="text-xs text-slate-600 font-mono font-bold">{{ form.color }}</span>
            </div>
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
              {{ isEditing ? 'Update Account' : 'Create Account' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatCurrency } from '@/Utils/formatters';
import {
  Plus as PlusIcon,
  Pencil as PencilIcon,
  Trash2 as Trash2Icon,
  X as XIcon,
  ArrowUp as ArrowUpIcon,
  ArrowDown as ArrowDownIcon,
  CreditCard as CreditCardIcon,
  PiggyBank as PiggyBankIcon,
  Wallet as WalletIcon,
} from 'lucide-vue-next';

const props = defineProps({
  accounts: { type: Array, default: () => [] },
  totalNetWorth: { type: Number, default: 0 },
  categoryTotals: { type: Object, default: () => ({ current: 0, savings: 0, other: 0 }) },
});

const showModal = ref(false);
const isEditing = ref(false);
const editingAccountId = ref(null);

const form = useForm({
  name: '',
  category: 'current',
  type: 'bank',
  currency: 'MYR',
  initial_balance: 0,
  color: '#0f172a',
  icon: 'wallet',
});

const categorizedSections = computed(() => {
  const current = props.accounts.filter(a => (a.category || 'current') === 'current');
  const savings = props.accounts.filter(a => a.category === 'savings');
  const other = props.accounts.filter(a => !['current', 'savings'].includes(a.category));

  return [
    {
      key: 'current',
      title: 'Current / Daily Accounts',
      iconComponent: CreditCardIcon,
      items: current,
      subtotal: current.reduce((sum, a) => sum + parseFloat(a.balance || 0), 0),
    },
    {
      key: 'savings',
      title: 'Savings Accounts',
      iconComponent: PiggyBankIcon,
      items: savings,
      subtotal: savings.reduce((sum, a) => sum + parseFloat(a.balance || 0), 0),
    },
    {
      key: 'other',
      title: 'E-Wallets, Cash & Credit Cards',
      iconComponent: WalletIcon,
      items: other,
      subtotal: other.reduce((sum, a) => sum + parseFloat(a.balance || 0), 0),
    },
  ];
});

const getCategoryLabel = (category) => {
  switch (category) {
    case 'current': return 'Current';
    case 'savings': return 'Savings';
    case 'wallet': return 'E-Wallet';
    case 'credit_card': return 'Credit Card';
    case 'investment': return 'Investment';
    default: return 'Account';
  }
};

const openModal = (mode, account = null) => {
  if (mode === 'edit' && account) {
    isEditing.value = true;
    editingAccountId.value = account.id;
    form.name = account.name;
    form.category = account.category || 'current';
    form.type = account.type;
    form.currency = account.currency || 'MYR';
    form.initial_balance = account.initial_balance;
    form.color = account.color || '#0f172a';
  } else {
    isEditing.value = false;
    editingAccountId.value = null;
    form.name = '';
    form.category = 'current';
    form.type = 'bank';
    form.currency = 'MYR';
    form.initial_balance = 0;
    form.color = '#0f172a';
  }
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
};

const saveAccount = () => {
  if (isEditing.value && editingAccountId.value) {
    form.put(`/accounts/${editingAccountId.value}`, {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post('/accounts', {
      onSuccess: () => closeModal(),
    });
  }
};

const deleteAccount = (acc) => {
  if (confirm(`Are you sure you want to delete account "${acc.name}"?`)) {
    router.delete(`/accounts/${acc.id}`);
  }
};

const reorderItem = (list, index, direction) => {
  const targetIndex = index + direction;
  if (targetIndex < 0 || targetIndex >= list.length) return;

  const newList = [...props.accounts];
  const itemA = list[index];
  const itemB = list[targetIndex];

  // Swap sort_order between itemA and itemB
  const indexA = newList.findIndex(a => a.id === itemA.id);
  const indexB = newList.findIndex(a => a.id === itemB.id);

  if (indexA !== -1 && indexB !== -1) {
    const tempOrder = newList[indexA].sort_order || indexA;
    newList[indexA].sort_order = newList[indexB].sort_order || indexB;
    newList[indexB].sort_order = tempOrder;

    const ordersPayload = newList.map((acc, idx) => ({
      id: acc.id,
      sort_order: acc.sort_order ?? idx,
    }));

    router.post('/accounts/reorder', { orders: ordersPayload }, { preserveScroll: true });
  }
};
</script>
