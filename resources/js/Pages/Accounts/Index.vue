<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
            Account Management
          </h2>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 font-normal">Create, edit, and manage your bank accounts, e-wallets, cash & credit cards.</p>
        </div>

        <button
          @click="openModal('create')"
          class="minimal-btn-primary flex items-center justify-center space-x-2 px-5 py-2.5 text-xs font-semibold"
        >
          <PlusIcon class="w-4 h-4" />
          <span>Add New Account</span>
        </button>
      </div>

      <!-- Combined Balance Banner -->
      <div class="minimal-card p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div class="flex items-center gap-4">
          <div>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Combined Balance</span>
            <div class="text-2xl sm:text-3xl font-black text-slate-900 mt-0.5">{{ formatCurrency(totalNetWorth) }}</div>
          </div>
        </div>
        <div class="flex items-center space-x-2">
          <span class="px-3.5 py-1.5 rounded-full bg-slate-100 text-slate-800 border border-slate-200 text-xs font-bold">
            {{ accounts.length }} {{ accounts.length === 1 ? 'Account' : 'Accounts' }}
          </span>
          <Link href="/" class="px-3.5 py-1.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 text-xs font-bold hover:bg-indigo-100 transition-colors">
            ← Back to Overview
          </Link>
        </div>
      </div>

      <!-- Accounts Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <div
          v-for="acc in accounts"
          :key="acc.id"
          class="minimal-card minimal-card-hover p-6 flex flex-col justify-between space-y-4"
        >
          <div class="flex items-start justify-between">
            <div class="flex items-center space-x-3.5">
              <div
                class="w-11 h-11 rounded-full flex items-center justify-center text-white font-bold text-base shadow-sm shrink-0"
                :style="{ backgroundColor: acc.color || '#0f172a' }"
              >
                {{ acc.name.charAt(0) }}
              </div>
              <div>
                <h3 class="font-bold text-base text-slate-900">{{ acc.name }}</h3>
                <span class="inline-block px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[9px] uppercase font-bold tracking-wider mt-0.5">
                  {{ acc.type }}
                </span>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center space-x-1">
              <button
                @click="openModal('edit', acc)"
                class="p-2 rounded-full text-slate-400 hover:text-slate-900 hover:bg-slate-100 transition-colors"
                title="Edit Account"
              >
                <PencilIcon class="w-4 h-4" />
              </button>
              <button
                @click="deleteAccount(acc)"
                class="p-2 rounded-full text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                title="Delete Account"
              >
                <Trash2Icon class="w-4 h-4" />
              </button>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100">
            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Current Balance</span>
            <div class="text-2xl font-black text-slate-900 mt-0.5">
              {{ formatCurrency(acc.balance) }}
            </div>
            <div class="flex items-center justify-between text-xs text-slate-500 mt-2 font-medium">
              <span>Initial: {{ formatCurrency(acc.initial_balance) }}</span>
              <span>Currency: {{ acc.currency }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Account Create / Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="minimal-card max-w-lg w-full p-6 space-y-5 animate-scale-up">
        <div class="flex items-center justify-between">
          <h3 class="text-xl font-bold text-slate-900">
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
              placeholder="e.g. Maybank Savings, Touch 'n Go, GrabPay"
              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm focus:outline-none focus:border-slate-900"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                <option value="e-wallet">E-Wallet (TnG / GrabPay)</option>
                <option value="cash">Cash Wallet</option>
                <option value="credit_card">Credit Card</option>
              </select>
            </div>

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
import { ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatCurrency } from '@/Utils/formatters';
import {
  Plus as PlusIcon,
  Pencil as PencilIcon,
  Trash2 as Trash2Icon,
  X as XIcon,
} from 'lucide-vue-next';

const props = defineProps({
  accounts: { type: Array, default: () => [] },
  totalNetWorth: { type: Number, default: 0 },
});

const showModal = ref(false);
const isEditing = ref(false);
const editingAccountId = ref(null);

const form = useForm({
  name: '',
  type: 'bank',
  currency: 'MYR',
  initial_balance: 0,
  color: '#0f172a',
  icon: 'wallet',
});

const openModal = (mode, account = null) => {
  if (mode === 'edit' && account) {
    isEditing.value = true;
    editingAccountId.value = account.id;
    form.name = account.name;
    form.type = account.type;
    form.currency = account.currency || 'MYR';
    form.initial_balance = account.initial_balance;
    form.color = account.color || '#0f172a';
  } else {
    isEditing.value = false;
    editingAccountId.value = null;
    form.name = '';
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
</script>
