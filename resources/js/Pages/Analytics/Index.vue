<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
            Analytics & Reports
          </h2>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 font-normal">Categorized spending breakdowns and monthly expenditure trends in MYR.</p>
        </div>

        <!-- Time Period Selector -->
        <div class="flex items-center bg-slate-100 p-1.5 rounded-full border border-slate-200">
          <button
            v-for="p in ['weekly', 'monthly', 'yearly']"
            :key="p"
            @click="changePeriod(p)"
            :class="[
              period === p ? 'bg-slate-900 text-white font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900 font-medium',
              'px-4 py-1.5 rounded-full text-xs capitalize transition-all'
            ]"
          >
            {{ p }}
          </button>
        </div>
      </div>

      <!-- Totals Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="minimal-card p-6 border-l-4 border-l-rose-500">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Expenditure ({{ period }})</span>
          <div class="text-3xl font-black text-rose-600 mt-0.5">{{ formatCurrency(totalExpenses) }}</div>
          <span class="text-[10px] text-slate-400 mt-1 block font-medium">From {{ startDate }} to {{ endDate }}</span>
        </div>

        <div class="minimal-card p-6 border-l-4 border-l-emerald-500">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Income ({{ period }})</span>
          <div class="text-3xl font-black text-emerald-600 mt-0.5">{{ formatCurrency(totalIncome) }}</div>
          <span class="text-[10px] text-slate-400 mt-1 block font-medium">From {{ startDate }} to {{ endDate }}</span>
        </div>
      </div>

      <!-- Charts Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Category Expense Pie/Donut Chart Card -->
        <div class="minimal-card p-6 space-y-4">
          <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
            <PieChartIcon class="w-5 h-5 text-slate-700" />
            <span>Category Expenditure Breakdown</span>
          </h3>

          <div v-if="categoryBreakdown.length === 0" class="text-center py-12 text-slate-400 text-sm font-medium">
            No expenses recorded for this period.
          </div>

          <div v-else class="space-y-6">
            <div class="h-64 relative flex items-center justify-center">
              <Doughnut :data="doughnutChartData" :options="doughnutOptions" />
            </div>

            <!-- Legend breakdown list -->
            <div class="grid grid-cols-2 gap-3 pt-4 border-t border-slate-100">
              <div
                v-for="cat in categoryBreakdown"
                :key="cat.category_name"
                class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs"
              >
                <span class="text-slate-700 flex items-center gap-2 truncate font-medium">
                  <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: cat.color || '#0f172a' }"></span>
                  {{ cat.category_name }}
                </span>
                <span class="font-bold text-slate-900 shrink-0">{{ formatCurrency(cat.total_amount) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Monthly Trends Bar Graph Card -->
        <div class="minimal-card p-6 space-y-4">
          <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
            <BarChart2Icon class="w-5 h-5 text-slate-700" />
            <span>Monthly Cash Flow Trend (Past 6 Months)</span>
          </h3>

          <div class="h-80 relative flex items-center justify-center">
            <Bar :data="barChartData" :options="barOptions" />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatCurrency } from '@/Utils/formatters';
import { PieChart as PieChartIcon, BarChart2 as BarChart2Icon } from 'lucide-vue-next';

// Chart.js Registration
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  ArcElement,
} from 'chart.js';
import { Doughnut, Bar } from 'vue-chartjs';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);

const props = defineProps({
  period: { type: String, default: 'monthly' },
  categoryBreakdown: { type: Array, default: () => [] },
  monthlyTrends: { type: Array, default: () => [] },
  totalExpenses: { type: Number, default: 0 },
  totalIncome: { type: Number, default: 0 },
  startDate: { type: String, default: '' },
  endDate: { type: String, default: '' },
});

const changePeriod = (newPeriod) => {
  router.get('/analytics', { period: newPeriod }, { preserveState: true });
};

// Doughnut Chart Configuration
const doughnutChartData = computed(() => {
  return {
    labels: props.categoryBreakdown.map(c => c.category_name),
    datasets: [
      {
        data: props.categoryBreakdown.map(c => c.total_amount),
        backgroundColor: props.categoryBreakdown.map(c => c.color || '#0f172a'),
        borderWidth: 2,
        borderColor: '#ffffff',
      },
    ],
  };
});

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label: (context) => ` ${context.label}: RM ${parseFloat(context.raw).toFixed(2)}`,
      },
    },
  },
};

// Bar Chart Configuration
const barChartData = computed(() => {
  return {
    labels: props.monthlyTrends.map(m => m.month),
    datasets: [
      {
        label: 'Expenses (MYR)',
        data: props.monthlyTrends.map(m => m.expense),
        backgroundColor: '#e11d48',
        borderRadius: 6,
      },
      {
        label: 'Income (MYR)',
        data: props.monthlyTrends.map(m => m.income),
        backgroundColor: '#059669',
        borderRadius: 6,
      },
    ],
  };
});

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      labels: { color: '#475569', font: { family: 'Inter', size: 12 } },
    },
    tooltip: {
      callbacks: {
        label: (context) => ` ${context.dataset.label}: RM ${parseFloat(context.raw).toFixed(2)}`,
      },
    },
  },
  scales: {
    x: {
      ticks: { color: '#64748b' },
      grid: { color: 'rgba(0,0,0,0.04)' },
    },
    y: {
      ticks: { color: '#64748b' },
      grid: { color: 'rgba(0,0,0,0.04)' },
    },
  },
};
</script>
