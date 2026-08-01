<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 flex items-center gap-2.5">
            <RepeatIcon class="w-7 h-7 text-indigo-600" />
            <span>Shared Subscriptions</span>
          </h2>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 font-normal">
            Track recurring family & group subscriptions, shared member dues, and Google Sheet style payment grids.
          </p>
        </div>

        <button
          @click="showCreateModal = true"
          class="minimal-btn-primary flex items-center justify-center space-x-2 px-5 py-2.5 text-xs font-semibold"
        >
          <PlusIcon class="w-4 h-4" />
          <span>New Shared Subscription</span>
        </button>
      </div>

      <!-- Stats Summary Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="minimal-card p-5 border-l-4 border-l-indigo-500">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Active Subscriptions</span>
          <div class="text-3xl font-black text-slate-900 mt-0.5">{{ stats.totalSubscriptions }}</div>
          <span class="text-[10px] text-slate-400 mt-1 block font-medium">Shared plan groups</span>
        </div>

        <div class="minimal-card p-5 border-l-4 border-l-slate-700">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Monthly Cost</span>
          <div class="text-3xl font-black text-slate-900 mt-0.5">RM{{ formatCurrency(stats.totalMonthlyCost) }}</div>
          <span class="text-[10px] text-slate-400 mt-1 block font-medium">Combined recurring bill</span>
        </div>

        <div class="minimal-card p-5 border-l-4 border-l-rose-500 bg-rose-50/20">
          <span class="text-[10px] font-bold text-rose-500 uppercase tracking-wider">Uncollected Member Dues</span>
          <div class="text-3xl font-black text-rose-600 mt-0.5">RM{{ formatCurrency(stats.totalUncollectedDues) }}</div>
          <span class="text-[10px] text-slate-400 mt-1 block font-medium">Pending payments from members</span>
        </div>
      </div>

      <!-- Subscriptions Grid -->
      <div v-if="subscriptions.length === 0" class="minimal-card p-12 text-center space-y-3">
        <RepeatIcon class="w-12 h-12 text-slate-300 mx-auto mb-2" />
        <h3 class="text-base font-bold text-slate-700">No shared subscriptions yet</h3>
        <p class="text-xs text-slate-400 max-w-md mx-auto">
          Add your YouTube Premium, Netflix, Spotify, or Disney+ family plans to track billing cycles and member payments.
        </p>
        <button
          @click="showCreateModal = true"
          class="minimal-btn-primary px-5 py-2.5 text-xs font-bold inline-flex items-center gap-1.5 mt-2"
        >
          <PlusIcon class="w-4 h-4" />
          <span>Create First Subscription</span>
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <div
          v-for="sub in subscriptions"
          :key="sub.id"
          class="minimal-card minimal-card-hover p-6 flex flex-col justify-between space-y-5"
        >
          <div class="space-y-3">
            <div class="flex items-start justify-between">
              <div>
                <h3 class="font-extrabold text-lg text-slate-900 line-clamp-1">{{ sub.name }}</h3>
                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-indigo-600 bg-indigo-50 px-2.5 py-0.5 rounded-full border border-indigo-200 mt-1">
                  <CalendarIcon class="w-3 h-3" />
                  Cycle: {{ sub.billing_cycle_day }}th of every month
                </span>
              </div>
              <button
                @click="confirmDelete(sub)"
                class="text-slate-400 hover:text-rose-600 p-1 transition-colors"
                title="Delete Subscription"
              >
                <Trash2Icon class="w-4 h-4" />
              </button>
            </div>

            <div class="flex items-baseline justify-between pt-2 border-t border-slate-100">
              <div>
                <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Plan Cost</span>
                <p class="text-xl font-black text-slate-900">RM{{ formatCurrency(sub.total_monthly_cost) }}</p>
              </div>
              <div class="text-right">
                <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Pending Dues</span>
                <p :class="[sub.uncollected_dues > 0 ? 'text-rose-600 font-black' : 'text-emerald-600 font-bold', 'text-sm']">
                  {{ sub.uncollected_dues > 0 ? 'RM' + formatCurrency(sub.uncollected_dues) : 'All Clear' }}
                </p>
              </div>
            </div>

            <!-- Members List Pill Badges -->
            <div>
              <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-1.5">
                Shared Members ({{ sub.members.length }})
              </span>
              <div class="flex flex-wrap gap-1.5">
                <span
                  v-for="m in sub.members"
                  :key="m.id"
                  class="px-2.5 py-1 rounded-full bg-slate-100 border border-slate-200 text-slate-700 text-[11px] font-bold flex items-center gap-1"
                >
                  <UserIcon class="w-3 h-3 text-slate-500" />
                  <span>{{ m.name }}</span>
                  <span class="text-[10px] text-slate-400 font-normal">(RM{{ formatCurrency(m.default_share_amount) }})</span>
                </span>
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100">
            <Link
              :href="`/subscriptions/${sub.id}`"
              class="w-full minimal-btn-primary py-2.5 px-4 text-xs font-bold flex items-center justify-center space-x-2"
            >
              <TableIcon class="w-4 h-4" />
              <span>Open Payment Sheet Grid →</span>
            </Link>
          </div>
        </div>
      </div>

      <!-- Create New Subscription Modal -->
      <div v-if="showCreateModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="minimal-card max-w-lg w-full p-6 space-y-5 animate-scale-up max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-slate-900 flex items-center gap-2">
              <RepeatIcon class="w-5 h-5 text-indigo-600" />
              <span>New Shared Subscription</span>
            </h3>
            <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 p-1">
              <XIcon class="w-5 h-5" />
            </button>
          </div>

          <form @submit.prevent="submitCreate" class="space-y-4 text-xs">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Subscription / Plan Name</label>
              <input
                v-model="form.name"
                type="text"
                required
                placeholder="e.g. YouTube Premium"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-900 font-medium"
              />
            </div>

            <div class="grid grid-cols-3 gap-3">
              <div>
                <label class="block font-bold text-slate-700 mb-1">Billing Day</label>
                <input
                  v-model.number="form.billing_cycle_day"
                  type="number"
                  min="1"
                  max="31"
                  required
                  placeholder="27"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-900 font-medium"
                />
              </div>

              <div>
                <label class="block font-bold text-slate-700 mb-1">Start Month</label>
                <select
                  v-model.number="form.start_month"
                  class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-900 font-bold"
                >
                  <option v-for="(mName, mIdx) in monthOptions" :key="mIdx + 1" :value="mIdx + 1">
                    {{ mName }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block font-bold text-slate-700 mb-1">Start Year</label>
                <input
                  v-model.number="form.start_year"
                  type="number"
                  min="2020"
                  max="2030"
                  required
                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-900 font-medium"
                />
              </div>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Total Monthly Cost (MYR)</label>
              <input
                v-model.number="form.total_monthly_cost"
                type="number"
                step="0.01"
                min="0"
                required
                placeholder="45.00"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-900 font-medium"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Notes & Billing Rule</label>
              <textarea
                v-model="form.notes"
                rows="2"
                placeholder="e.g. 27th of Every Month Starts a new cycle (27/1 - 27/2 = February)"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-900 font-medium"
              ></textarea>
            </div>

            <!-- Member Split Allocation Section -->
            <div class="pt-2 border-t border-slate-100 space-y-3">
              <div class="flex items-center justify-between">
                <span class="font-bold text-slate-800 text-xs">Shared Members (People in Plan)</span>
                <div class="flex items-center space-x-2">
                  <button
                    type="button"
                    @click="addMyselfAsMember"
                    class="text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 px-2.5 py-1 rounded-lg font-bold text-xs flex items-center gap-1 transition-colors"
                  >
                    <UserIcon class="w-3.5 h-3.5" />
                    <span>+ Add Me ({{ currentUser?.name ? currentUser.name.split(' ')[0] : 'Myself' }})</span>
                  </button>
                  <button
                    type="button"
                    @click="addMemberRow"
                    class="text-slate-600 hover:text-slate-900 font-bold text-xs flex items-center gap-1"
                  >
                    <PlusIcon class="w-3.5 h-3.5" />
                    <span>Add Member</span>
                  </button>
                </div>
              </div>

              <div class="space-y-2">
                <div
                  v-for="(member, idx) in form.members"
                  :key="idx"
                  class="flex items-center gap-2 p-2 rounded-xl bg-slate-50 border border-slate-200"
                >
                  <input
                    v-model="member.name"
                    type="text"
                    placeholder="Member Name"
                    class="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-slate-900 font-bold text-xs"
                  />
                  <div class="w-32 flex items-center gap-1">
                    <span class="text-slate-400 font-bold text-xs">RM</span>
                    <input
                      v-model.number="member.default_share_amount"
                      type="number"
                      step="0.01"
                      min="0"
                      placeholder="0.00"
                      class="w-full px-2 py-1.5 rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-slate-900 font-bold text-xs"
                    />
                  </div>
                  <button
                    type="button"
                    @click="removeMemberRow(idx)"
                    class="text-slate-400 hover:text-rose-600 p-1"
                    title="Remove Member"
                  >
                    <XIcon class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
              <button
                type="button"
                @click="showCreateModal = false"
                class="minimal-btn-secondary px-4 py-2 text-xs font-semibold"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="form.processing"
                class="minimal-btn-primary px-5 py-2 text-xs font-semibold"
              >
                Save Subscription
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  Repeat as RepeatIcon,
  Plus as PlusIcon,
  Calendar as CalendarIcon,
  User as UserIcon,
  Table as TableIcon,
  Trash2 as Trash2Icon,
  X as XIcon,
} from 'lucide-vue-next';

defineProps({
  subscriptions: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({
      totalSubscriptions: 0,
      totalMonthlyCost: 0,
      totalUncollectedDues: 0,
    }),
  },
});

const monthOptions = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December'
];

const currentDate = new Date();
const currentMonthIndex = currentDate.getMonth() + 1; // 1-12
const currentYearNum = currentDate.getFullYear();
const showCreateModal = ref(false);

const form = useForm({
  name: '',
  billing_cycle_day: 27,
  start_month: currentMonthIndex,
  start_year: currentYearNum,
  total_monthly_cost: null,
  currency: 'MYR',
  notes: '',
  members: [
    { name: '', default_share_amount: null },
  ],
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const addMyselfAsMember = () => {
  const myName = currentUser.value?.name || 'Me';
  const exists = form.members.some(m => m.name && m.name.trim().toLowerCase() === myName.trim().toLowerCase());
  if (!exists) {
    if (form.members.length === 1 && !form.members[0].name) {
      form.members[0].name = myName;
    } else {
      form.members.push({ name: myName, default_share_amount: null });
    }
  }
};

const addMemberRow = () => {
  form.members.push({ name: '', default_share_amount: null });
};

const removeMemberRow = (idx) => {
  form.members.splice(idx, 1);
};

const submitCreate = () => {
  const validMembers = form.members
    .filter((m) => m.name && m.name.trim() !== '')
    .map((m) => ({
      name: m.name.trim(),
      default_share_amount: m.default_share_amount ? parseFloat(m.default_share_amount) : 0,
    }));

  form
    .transform((data) => ({
      ...data,
      members: validMembers,
    }))
    .post('/subscriptions', {
      onSuccess: () => {
        showCreateModal.value = false;
        form.reset();
        form.members = [{ name: '', default_share_amount: null }];
      },
    });
};

const confirmDelete = (sub) => {
  if (confirm(`Are you sure you want to delete "${sub.name}"?`)) {
    router.delete(`/subscriptions/${sub.id}`);
  }
};

const formatCurrency = (val) => {
  const num = parseFloat(val) || 0;
  return num.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>
