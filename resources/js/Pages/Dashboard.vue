<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header Banner -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
            Overview
          </h2>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 font-normal">Real-time net worth tracking & receipt parsing in MYR.</p>
        </div>

        <!-- Minimal Action Buttons -->
        <div class="flex items-center gap-2">
          <Link
            href="/receipts"
            class="minimal-btn-primary flex items-center justify-center space-x-2 px-5 py-2.5 text-xs font-semibold"
          >
            <CameraIcon class="w-4 h-4 text-white" />
            <span>Scan Receipt</span>
          </Link>
          <button
            @click="openModal('expense')"
            class="minimal-btn-secondary flex items-center justify-center space-x-1.5 px-4 py-2.5 text-xs font-semibold"
          >
            <PlusIcon class="w-4 h-4 text-emerald-600" />
            <span>Log Expense</span>
          </button>
          <button
            @click="openModal('transfer')"
            class="minimal-btn-secondary flex items-center justify-center space-x-1.5 px-4 py-2.5 text-xs font-semibold"
          >
            <ArrowRightLeftIcon class="w-4 h-4 text-sky-600" />
            <span>Transfer</span>
          </button>
        </div>
      </div>

      <!-- Net Worth Hero Card (Minimalist Light Hero) -->
      <div class="minimal-card-hero p-6 sm:p-8 space-y-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div>
            <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1 flex items-center gap-2">
              <TrendingUpIcon class="w-4 h-4 text-slate-900" />
              <span>Total Net Worth (MYR)</span>
            </div>
            <div class="text-3xl sm:text-5xl font-black text-slate-900 tracking-tight">
              {{ formatCurrency(totalNetWorth) }}
            </div>
            <p class="text-xs text-slate-500 mt-2 flex items-center gap-2 font-medium">
              <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
              Live calculated across {{ accounts.length }} financial accounts
            </p>
          </div>

          <!-- Monthly Summary Stats -->
          <div class="grid grid-cols-2 gap-4 border-t md:border-t-0 md:border-l border-slate-200 pt-4 md:pt-0 md:pl-8">
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Monthly Expenses</span>
              <span class="text-lg sm:text-xl font-bold text-rose-600 mt-0.5 block">{{ formatCurrency(monthlyExpenses) }}</span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Monthly Income</span>
              <span class="text-lg sm:text-xl font-bold text-emerald-600 mt-0.5 block">{{ formatCurrency(monthlyIncome) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Accounts Overview Grid -->
      <div>
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
            <WalletIcon class="w-5 h-5 text-slate-700" />
            <span>Accounts</span>
          </h3>
          <Link href="/accounts" class="text-xs font-bold text-slate-600 hover:text-slate-900 transition-colors">
            Manage Accounts →
          </Link>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div
            v-for="account in accounts"
            :key="account.id"
            class="minimal-card minimal-card-hover p-5 flex flex-col justify-between"
          >
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center space-x-3">
                <div
                  class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm shadow-sm shrink-0"
                  :style="{ backgroundColor: account.color || '#0f172a' }"
                >
                  <span class="uppercase">{{ account.name.charAt(0) }}</span>
                </div>
                <div class="min-w-0">
                  <h4 class="font-bold text-sm text-slate-900 truncate max-w-[130px]">{{ account.name }}</h4>
                  <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">{{ account.type }}</span>
                </div>
              </div>
            </div>

            <div>
              <div class="text-xl font-extrabold text-slate-900">
                {{ formatCurrency(account.balance) }}
              </div>
              <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Currency: {{ account.currency }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Two Column Layout: Recent Activity & Spending -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Transactions Feed (2 cols) -->
        <div class="lg:col-span-2 minimal-card p-6 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
              <ReceiptIcon class="w-5 h-5 text-slate-700" />
              <span>Recent Activity</span>
            </h3>
            <Link href="/transactions" class="text-xs font-bold text-slate-600 hover:text-slate-900">
              View All →
            </Link>
          </div>

          <div v-if="recentTransactions.length === 0" class="text-center py-8 text-slate-400 text-sm font-medium">
            No transactions logged yet.
          </div>

          <div v-else class="space-y-2.5">
            <div
              v-for="tx in recentTransactions"
              :key="tx.id"
              class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50 hover:bg-slate-100/80 border border-slate-200/60 transition-all"
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
                  <p class="text-sm font-bold text-slate-900 truncate">
                    {{ tx.notes || (tx.category ? tx.category.name : 'Transaction') }}
                  </p>
                  <p class="text-xs text-slate-500 flex items-center gap-2 truncate mt-0.5 font-medium">
                    <span>{{ tx.account?.name }}</span>
                    <span v-if="tx.destination_account" class="text-sky-600 font-semibold">→ {{ tx.destination_account.name }}</span>
                    <span>•</span>
                    <span>{{ formatDate(tx.date) }}</span>
                  </p>
                </div>
              </div>

              <div class="text-right shrink-0">
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
                <span v-if="tx.receipt_id" class="text-[9px] text-slate-700 font-bold bg-slate-200/80 px-2 py-0.5 rounded-full border border-slate-300">
                  SmartSplit
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Top Category Spending Widget (1 col) -->
        <div class="minimal-card p-6 space-y-4">
          <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
            <PieChartIcon class="w-5 h-5 text-slate-700" />
            <span>Category Spending</span>
          </h3>

          <div v-if="topCategories.length === 0" class="text-center py-8 text-slate-400 text-sm font-medium">
            No categorized expenses recorded.
          </div>

          <div v-else class="space-y-4">
            <div v-for="cat in topCategories" :key="cat.category_name" class="space-y-1.5">
              <div class="flex items-center justify-between text-xs font-semibold">
                <span class="text-slate-700 flex items-center gap-2 font-medium">
                  <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: cat.color || '#0f172a' }"></span>
                  {{ cat.category_name }}
                </span>
                <span class="text-slate-900 font-bold">{{ formatCurrency(cat.total_amount) }}</span>
              </div>
              <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                <div
                  class="h-full rounded-full transition-all duration-500"
                  :style="{
                    width: `${Math.min(100, (cat.total_amount / (monthlyExpenses || 1)) * 100)}%`,
                    backgroundColor: cat.color || '#0f172a'
                  }"
                ></div>
              </div>
            </div>

            <div class="pt-3 border-t border-slate-100">
              <Link href="/analytics" class="text-xs font-bold text-slate-700 hover:text-slate-900 block text-center">
                Full Analytics Report →
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Log Transaction Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="minimal-card max-w-lg w-full p-6 space-y-5 animate-scale-up">
        <div class="flex items-center justify-between">
          <h3 class="text-xl font-bold text-slate-900">
            {{ modalType === 'expense' ? 'Log Expense' : 'Log Cross-Account Transfer' }}
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
              placeholder="e.g. Lunch at Nasi Kandar"
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
              Save Transaction
            </button>
          </div>
        </form>
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
  Plus as PlusIcon,
  ArrowRightLeft as ArrowRightLeftIcon,
  TrendingUp as TrendingUpIcon,
  Wallet as WalletIcon,
  Receipt as ReceiptIcon,
  PieChart as PieChartIcon,
  ArrowUpRight as ArrowUpRightIcon,
  ArrowDownLeft as ArrowDownLeftIcon,
  X as XIcon,
} from 'lucide-vue-next';

const props = defineProps({
  accounts: { type: Array, default: () => [] },
  totalNetWorth: { type: Number, default: 0 },
  recentTransactions: { type: Array, default: () => [] },
  monthlyExpenses: { type: Number, default: 0 },
  monthlyIncome: { type: Number, default: 0 },
  topCategories: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
});

const showModal = ref(false);
const modalType = ref('expense');

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
</script>
