<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header Banner -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
            Overview
          </h2>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 font-normal">Real-time financial tracking & smart bill management in MYR.</p>
        </div>

        <!-- Desktop Action Buttons -->
        <div class="hidden sm:flex items-center gap-2">
          <button
            @click="openModal('expense')"
            class="minimal-btn-primary flex items-center justify-center space-x-1.5 px-4 py-2.5 text-xs font-semibold"
          >
            <PlusIcon class="w-4 h-4 text-white" />
            <span>Log Expense</span>
          </button>
          <button
            @click="openModal('income')"
            class="minimal-btn-secondary flex items-center justify-center space-x-1.5 px-3.5 py-2.5 text-xs font-semibold"
          >
            <PlusIcon class="w-4 h-4 text-emerald-600" />
            <span>Log Income</span>
          </button>
          <button
            @click="openModal('transfer')"
            class="minimal-btn-secondary flex items-center justify-center space-x-1.5 px-3.5 py-2.5 text-xs font-semibold"
          >
            <ArrowRightLeftIcon class="w-4 h-4 text-sky-600" />
            <span>Transfer</span>
          </button>
        </div>
      </div>

      <!-- MOBILE VIEW: Compact Quick Summary Layout -->
      <div class="block sm:hidden space-y-6">
        <!-- 2 Stat Summary Boxes: Yesterday's Spent & Today's Spent -->
        <div class="grid grid-cols-2 gap-3.5">
          <div class="minimal-card p-4 border-l-4 border-l-slate-400 bg-slate-50/90 shadow-xs">
            <div class="flex items-center justify-between text-slate-500 mb-1">
              <span class="text-[10px] font-extrabold uppercase tracking-wider">Yesterday's Spent</span>
              <CalendarIcon class="w-3.5 h-3.5 text-slate-400" />
            </div>
            <div class="text-xl font-black text-slate-800 tracking-tight">
              {{ formatCurrency(yesterdaySpending) }}
            </div>
          </div>

          <div class="minimal-card p-4 border-l-4 border-l-rose-500 bg-rose-50/40 shadow-xs">
            <div class="flex items-center justify-between text-rose-600 mb-1">
              <span class="text-[10px] font-extrabold uppercase tracking-wider">Today's Spent</span>
              <SparklesIcon class="w-3.5 h-3.5 text-rose-500" />
            </div>
            <div class="text-xl font-black text-rose-600 tracking-tight">
              {{ formatCurrency(todaySpending) }}
            </div>
          </div>
        </div>

        <!-- Mobile Net Worth + Monthly Summary -->
        <div class="minimal-card p-4 space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Net Worth</span>
            <TrendingUpIcon class="w-3.5 h-3.5 text-slate-400" />
          </div>
          <div class="text-2xl font-black text-slate-900 tracking-tight">{{ formatCurrency(totalNetWorth) }}</div>
          <div class="flex items-center gap-3 pt-2 border-t border-slate-100">
            <div class="flex items-center gap-1.5 text-[11px]">
              <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
              <span class="text-slate-500 font-medium">Income</span>
              <span class="font-bold text-emerald-700">{{ formatCurrency(monthlyIncome) }}</span>
            </div>
            <div class="flex items-center gap-1.5 text-[11px]">
              <span class="w-2 h-2 rounded-full bg-rose-500"></span>
              <span class="text-slate-500 font-medium">Expenses</span>
              <span class="font-bold text-rose-700">{{ formatCurrency(monthlyExpenses) }}</span>
            </div>
          </div>
        </div>

        <!-- 3 Primary Mobile Action Buttons -->
        <div class="grid grid-cols-3 gap-2">
          <button
            @click="openModal('expense')"
            class="minimal-btn-primary py-3 px-2 rounded-2xl flex flex-col items-center justify-center gap-1 shadow-sm text-center active:scale-95 transition-all"
          >
            <PlusIcon class="w-4 h-4 text-white" />
            <span class="text-[11px] font-bold">Log Expense</span>
          </button>

          <button
            @click="openModal('income')"
            class="bg-emerald-600 hover:bg-emerald-700 text-white py-3 px-2 rounded-2xl flex flex-col items-center justify-center gap-1 shadow-sm text-center active:scale-95 transition-all"
          >
            <PlusIcon class="w-4 h-4 text-white" />
            <span class="text-[11px] font-bold">Log Income</span>
          </button>

          <button
            @click="openModal('transfer')"
            class="minimal-btn-secondary py-3 px-2 rounded-2xl flex flex-col items-center justify-center gap-1 shadow-sm text-center active:scale-95 transition-all border-slate-300"
          >
            <ArrowRightLeftIcon class="w-4 h-4 text-sky-600" />
            <span class="text-[11px] font-bold text-slate-900">Transfer</span>
          </button>
        </div>

        <!-- Pinned Accounts (Mobile) -->
        <div class="minimal-card p-4 space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
              <PinIcon class="w-3.5 h-3.5 text-indigo-600" />
              <span>Pinned Accounts</span>
            </h3>

            <button
              @click="showPinModal = true"
              class="text-[10px] font-bold text-indigo-600 hover:underline"
            >
              Manage Pins ({{ pinnedAccounts.length }})
            </button>
          </div>

          <div class="grid grid-cols-1 gap-2">
            <div
              v-for="acc in pinnedAccounts"
              :key="acc.id"
              class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between"
            >
              <div class="flex items-center space-x-3">
                <div
                  class="w-7 h-7 rounded-full flex items-center justify-center text-white font-bold shrink-0"
                  :style="{ backgroundColor: acc.color || '#0f172a' }"
                >
                  <WalletIcon class="w-3.5 h-3.5 text-white" />
                </div>
                <div>
                  <span class="font-bold text-slate-900 text-xs block truncate">{{ acc.name }}</span>
                  <span class="text-[10px] text-slate-400 capitalize">{{ acc.type }}</span>
                </div>
              </div>
              <span class="font-extrabold text-slate-900 text-sm">{{ formatCurrency(acc.balance) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- DESKTOP / TABLET VIEW: Detailed Analytics & Net Worth Layout -->
      <div class="hidden sm:block space-y-6">
        <!-- Net Worth Hero Card -->
        <div class="minimal-card-hero p-6 sm:p-8 space-y-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Net Worth</span>
              <div class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight mt-0.5">
                {{ formatCurrency(totalNetWorth) }}
              </div>
            </div>
            <div class="flex items-center space-x-3 text-xs">
              <div class="px-4 py-2 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 font-semibold flex items-center gap-1.5">
                <TrendingUpIcon class="w-4 h-4 text-emerald-600" />
                <span>Income: {{ formatCurrency(monthlyIncome) }}</span>
              </div>
              <div class="px-4 py-2 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 font-semibold flex items-center gap-1.5">
                <ArrowUpRightIcon class="w-4 h-4 text-rose-600" />
                <span>Expenses: {{ formatCurrency(monthlyExpenses) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Accounts Grid & Pinned Accounts -->
        <div class="minimal-card p-6 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
              <WalletIcon class="w-5 h-5 text-slate-700" />
              <span>Financial Accounts ({{ accounts.length }})</span>
            </h3>
            <button
              v-if="accounts.length > 0"
              @click="showPinModal = true"
              class="text-xs font-bold text-indigo-600 hover:underline flex items-center gap-1"
            >
              <PinIcon class="w-3.5 h-3.5 text-indigo-600" />
              <span>Manage Pinned Accounts</span>
            </button>
          </div>

          <!-- Empty State -->
          <div v-if="accounts.length === 0" class="text-center py-10">
            <WalletIcon class="w-10 h-10 text-slate-300 mx-auto mb-3" />
            <p class="text-sm font-bold text-slate-500 mb-1">No accounts yet</p>
            <p class="text-xs text-slate-400 mb-4">Create your first bank account, e-wallet, or cash account to start tracking.</p>
            <Link href="/accounts" class="minimal-btn-primary px-5 py-2 text-xs font-bold inline-flex items-center gap-1.5">
              <PlusIcon class="w-3.5 h-3.5" />
              <span>Create First Account</span>
            </Link>
          </div>

          <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="acc in accounts"
              :key="acc.id"
              class="minimal-card p-4 bg-slate-50/70 hover:bg-slate-100/80 transition-all flex items-center justify-between"
            >
              <div class="flex items-center space-x-3 min-w-0">
                <div
                  class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold shrink-0 shadow-xs"
                  :style="{ backgroundColor: acc.color || '#0f172a' }"
                >
                  <WalletIcon class="w-4 h-4 text-white" />
                </div>
                <div class="min-w-0">
                  <div class="flex items-center space-x-1.5">
                    <span class="font-bold text-slate-900 text-sm truncate">{{ acc.name }}</span>
                    <span v-if="acc.is_pinned" class="text-[9px] bg-indigo-100 text-indigo-800 font-extrabold px-1.5 py-0.5 rounded-full border border-indigo-200">
                      Pinned
                    </span>
                  </div>
                  <span class="text-xs text-slate-500 capitalize font-medium block truncate">{{ acc.type }}</span>
                </div>
              </div>
              <span class="font-black text-slate-900 text-base shrink-0 ml-2">{{ formatCurrency(acc.balance) }}</span>
            </div>
          </div>
        </div>

        <!-- Top Spending Categories This Month -->
        <div v-if="topCategories.length > 0" class="minimal-card p-6 space-y-4">
          <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
            <PieChartIcon class="w-5 h-5 text-slate-700" />
            <span>Top Spending Categories (This Month)</span>
          </h3>

          <div class="space-y-2.5">
            <div
              v-for="(cat, idx) in topCategories"
              :key="cat.category_name"
              class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200/60"
            >
              <div class="flex items-center gap-3">
                <span class="text-[10px] font-extrabold text-slate-400 w-5 text-center">{{ idx + 1 }}</span>
                <span class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: cat.color || '#0f172a' }"></span>
                <span class="text-sm font-bold text-slate-800">{{ cat.category_name }}</span>
              </div>
              <span class="font-extrabold text-rose-600 text-sm">{{ formatCurrency(cat.total_amount) }}</span>
            </div>
          </div>

          <Link href="/analytics" class="text-xs font-bold text-slate-600 hover:text-slate-900 block text-right">
            View Full Analytics →
          </Link>
        </div>
      </div>

      <!-- Recent Ledger Activity (Shared across Mobile & Desktop) -->
      <div class="minimal-card p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
            <ClockIcon class="w-5 h-5 text-slate-700" />
            <span>Recent Ledger Activity</span>
          </h3>
          <Link href="/transactions" class="text-xs font-bold text-slate-600 hover:text-slate-900">
            View All →
          </Link>
        </div>

        <div v-if="recentTransactions.length === 0" class="text-center py-8 text-slate-400 text-xs">
          No recent transactions found. Log your first expense or transfer above!
        </div>

        <div v-else class="space-y-2.5">
          <div
            v-for="tx in recentTransactions"
            :key="tx.id"
            class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50/80 hover:bg-slate-100/80 border border-slate-200/60 text-xs transition-all"
          >
            <div class="flex items-center space-x-3.5 min-w-0">
              <div
                :class="[
                  tx.type === 'expense' ? 'bg-rose-100 text-rose-700 border-rose-200' :
                  tx.type === 'income' ? 'bg-emerald-100 text-emerald-700 border-emerald-200' :
                  'bg-sky-100 text-sky-700 border-sky-200',
                  'w-9 h-9 rounded-full flex items-center justify-center border shrink-0'
                ]"
              >
                <ArrowUpRightIcon v-if="tx.type === 'expense'" class="w-4 h-4" />
                <ArrowDownLeftIcon v-else-if="tx.type === 'income'" class="w-4 h-4" />
                <ArrowRightLeftIcon v-else class="w-4 h-4" />
              </div>

              <div class="min-w-0">
                <span class="font-bold text-slate-900 text-sm block truncate">
                  {{ tx.notes || (tx.category ? tx.category.name : 'Transaction') }}
                </span>
                <div class="text-xs text-slate-500 font-medium flex items-center gap-1.5 truncate mt-0.5">
                  <span class="text-slate-700 font-bold">{{ tx.account?.name }}</span>
                  <span v-if="tx.type === 'transfer' && tx.destination_account" class="text-sky-600 font-bold">→ {{ tx.destination_account.name }}</span>
                  <span v-if="tx.category">• {{ tx.category.name }}</span>
                  <span>• {{ formatDate(tx.date) }}</span>
                </div>
              </div>
            </div>

            <span
              :class="[
                tx.type === 'expense' ? 'text-rose-600' :
                tx.type === 'income' ? 'text-emerald-600' :
                'text-slate-900',
                'font-extrabold text-sm shrink-0 ml-3'
              ]"
            >
              {{ tx.type === 'expense' ? '-' : (tx.type === 'income' ? '+' : '') }}{{ formatCurrency(tx.amount) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Pin Accounts Selection Modal -->
    <div v-if="showPinModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="minimal-card max-w-md w-full p-6 space-y-4 animate-scale-up">
        <div class="flex items-center justify-between">
          <h3 class="text-xl font-bold text-slate-900 flex items-center gap-2">
            <PinIcon class="w-5 h-5 text-indigo-600" />
            <span>Pin Favourite Accounts</span>
          </h3>
          <button @click="showPinModal = false" class="text-slate-400 hover:text-slate-600 p-1">
            <XIcon class="w-5 h-5" />
          </button>
        </div>

        <p class="text-xs text-slate-500 font-medium">Select maximum 3 favourite accounts to pin to Quick Hub</p>

        <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
          <div
            v-for="acc in accounts"
            :key="acc.id"
            @click="togglePinAccount(acc)"
            :class="[
              acc.is_pinned ? 'bg-indigo-50 border-indigo-300 ring-1 ring-indigo-500/20' : 'bg-slate-50 border-slate-200',
              'p-3 rounded-xl border flex items-center justify-between cursor-pointer transition-all hover:bg-slate-100'
            ]"
          >
            <div class="flex items-center space-x-3">
              <div
                class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold"
                :style="{ backgroundColor: acc.color || '#0f172a' }"
              >
                <WalletIcon class="w-3.5 h-3.5 text-white" />
              </div>
              <div>
                <span class="font-bold text-xs text-slate-900 block">{{ acc.name }}</span>
                <span class="text-[10px] text-slate-400">{{ formatCurrency(acc.balance) }}</span>
              </div>
            </div>

            <span
              :class="[
                acc.is_pinned ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-600',
                'px-2.5 py-1 rounded-full text-[10px] font-bold transition-colors'
              ]"
            >
              {{ acc.is_pinned ? 'Pinned' : 'Pin' }}
            </span>
          </div>
        </div>

        <div class="flex justify-end pt-2">
          <button
            @click="showPinModal = false"
            class="minimal-btn-primary px-6 py-2 text-xs font-bold"
          >
            Done
          </button>
        </div>
      </div>
    </div>

    <!-- Quick Transaction Logging Modal -->
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

        <!-- Transaction Type Switcher Pill -->
        <div class="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200">
          <button
            type="button"
            @click="setModalType('expense')"
            :class="[modalType === 'expense' ? 'bg-rose-600 text-white font-bold shadow-xs' : 'text-slate-600 font-semibold', 'flex-1 py-1.5 text-xs rounded-lg transition-all text-center']"
          >
            Expense
          </button>
          <button
            type="button"
            @click="setModalType('income')"
            :class="[modalType === 'income' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-slate-600 font-semibold', 'flex-1 py-1.5 text-xs rounded-lg transition-all text-center']"
          >
            Income
          </button>
          <button
            type="button"
            @click="setModalType('transfer')"
            :class="[modalType === 'transfer' ? 'bg-sky-600 text-white font-bold shadow-xs' : 'text-slate-600 font-semibold', 'flex-1 py-1.5 text-xs rounded-lg transition-all text-center']"
          >
            Transfer
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
            <label class="block text-xs font-semibold text-slate-600 mb-1">Date</label>
            <input
              v-model="form.date"
              type="date"
              required
              class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-xs focus:outline-none focus:border-slate-900"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Notes / Merchant Name</label>
            <input
              v-model="form.notes"
              type="text"
              placeholder="e.g. Grocery shopping at Lotus's"
              class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-xs focus:outline-none focus:border-slate-900"
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
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatCurrency, formatDate } from '@/Utils/formatters';
import {
  Calendar as CalendarIcon,
  Sparkles as SparklesIcon,
  Plus as PlusIcon,
  ArrowRightLeft as ArrowRightLeftIcon,
  TrendingUp as TrendingUpIcon,
  Wallet as WalletIcon,
  Clock as ClockIcon,
  ArrowUpRight as ArrowUpRightIcon,
  ArrowDownLeft as ArrowDownLeftIcon,
  Pin as PinIcon,
  PieChart as PieChartIcon,
  X as XIcon,
} from 'lucide-vue-next';

const props = defineProps({
  accounts: { type: Array, default: () => [] },
  pinnedAccounts: { type: Array, default: () => [] },
  totalNetWorth: { type: Number, default: 0 },
  todaySpending: { type: Number, default: 0 },
  yesterdaySpending: { type: Number, default: 0 },
  recentTransactions: { type: Array, default: () => [] },
  monthlyExpenses: { type: Number, default: 0 },
  monthlyIncome: { type: Number, default: 0 },
  topCategories: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
});

const showPinModal = ref(false);
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

const togglePinAccount = (acc) => {
  router.post(`/accounts/${acc.id}/toggle-pin`, {}, { preserveState: true });
};

const setModalType = (type) => {
  modalType.value = type;
  form.type = type;
  if (type === 'transfer') {
    if (!form.destination_account_id) {
      form.destination_account_id = props.accounts.find(a => a.id !== form.account_id)?.id || '';
    }
  } else {
    form.destination_account_id = '';
    if (!form.category_id && props.categories.length > 0) {
      form.category_id = props.categories[0].id;
    }
  }
};

const openModal = (type) => {
  setModalType(type);
  form.account_id = props.accounts[0]?.id || '';
  form.amount = '';
  form.date = todayStr;
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
