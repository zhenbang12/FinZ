<template>
  <AppLayout>
    <div class="space-y-6 max-w-5xl mx-auto pb-12">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-950 flex items-center gap-2">
            <span>Analytics & Reports</span>
          </h2>
          <p class="text-xs sm:text-sm text-slate-600 mt-1 font-bold">Categorized spending breakdowns and monthly expenditure trends in MYR.</p>
        </div>

        <!-- Time Period Selector -->
        <div class="flex items-center bg-white p-1 sm:p-1.5 rounded-2xl border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a] overflow-x-auto">
          <button
            v-for="p in ['daily', 'weekly', 'monthly', 'yearly']"
            :key="p"
            @click="changePeriod(p)"
            :class="[
              period === p ? 'bg-slate-950 text-amber-300 font-black shadow-[2px_2px_0px_#0f172a]' : 'text-slate-800 hover:text-slate-950 font-bold',
              'px-3 sm:px-4 py-1.5 rounded-xl text-xs capitalize transition-all whitespace-nowrap shrink-0 border border-transparent'
            ]"
          >
            {{ p }}
          </button>
        </div>
      </div>

      <!-- Totals Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="p-6 rounded-3xl bg-rose-300 border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] space-y-1 text-slate-950">
          <span class="text-[10px] font-black uppercase tracking-wider block">Total Expenditure ({{ period }})</span>
          <div class="text-3xl font-black font-mono text-slate-950">{{ formatCurrency(totalExpenses) }}</div>
          <span class="text-[10px] text-slate-900 font-bold block">From {{ startDate }} to {{ endDate }}</span>
        </div>

        <div class="p-6 rounded-3xl bg-emerald-400 border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] space-y-1 text-slate-950">
          <span class="text-[10px] font-black uppercase tracking-wider block">Total Income ({{ period }})</span>
          <div class="text-3xl font-black font-mono text-slate-950">{{ formatCurrency(totalIncome) }}</div>
          <span class="text-[10px] text-slate-900 font-bold block">From {{ startDate }} to {{ endDate }}</span>
        </div>
      </div>

      <!-- Charts Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Category Expense Pie/Donut Chart Card -->
        <div class="p-6 rounded-3xl bg-white border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] space-y-4">
          <h3 class="text-lg font-black text-slate-950 flex items-center gap-2">
            <PieChartIcon class="w-5 h-5 text-slate-950" />
            <span>Category Expenditure Breakdown</span>
          </h3>

          <div v-if="categoryBreakdown.length === 0" class="text-center py-12 text-slate-500 font-bold text-sm">
            No expenses recorded for this period.
          </div>

          <div v-else class="space-y-6">
            <div class="h-64 relative flex items-center justify-center">
              <Doughnut :data="doughnutChartData" :options="doughnutOptions" />
            </div>

            <!-- Legend breakdown list -->
            <div class="grid grid-cols-2 gap-3 pt-4 border-t-2 border-slate-950/10">
              <div
                v-for="cat in categoryBreakdown"
                :key="cat.category_name"
                class="flex items-center justify-between p-2.5 rounded-2xl bg-slate-50 border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a] text-xs"
              >
                <span class="text-slate-950 flex items-center gap-2 truncate font-extrabold">
                  <span class="w-3 h-3 rounded-full shrink-0 border border-slate-950" :style="{ backgroundColor: cat.color || '#0f172a' }"></span>
                  {{ cat.category_name }}
                </span>
                <span class="font-black font-mono text-slate-950 shrink-0">{{ formatCurrency(cat.total_amount) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Monthly Trends Bar Graph Card -->
        <div class="p-6 rounded-3xl bg-white border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] space-y-4">
          <h3 class="text-lg font-black text-slate-950 flex items-center gap-2">
            <BarChart2Icon class="w-5 h-5 text-slate-950" />
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
        borderColor: '#0f172a',
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
        backgroundColor: '#fb7185',
        borderColor: '#0f172a',
        borderWidth: 2,
        borderRadius: 8,
      },
      {
        label: 'Income (MYR)',
        data: props.monthlyTrends.map(m => m.income),
        backgroundColor: '#34d399',
        borderColor: '#0f172a',
        borderWidth: 2,
        borderRadius: 8,
      },
    ],
  };
});

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      labels: { color: '#0f172a', font: { family: 'Inter', size: 12, weight: 'bold' } },
    },
    tooltip: {
      callbacks: {
        label: (context) => ` ${context.dataset.label}: RM ${parseFloat(context.raw).toFixed(2)}`,
      },
    },
  },
  scales: {
    x: {
      ticks: { color: '#0f172a', font: { weight: 'bold' } },
      grid: { color: 'rgba(15,23,42,0.1)' },
    },
    y: {
      ticks: { color: '#0f172a', font: { weight: 'bold' } },
      grid: { color: 'rgba(15,23,42,0.1)' },
    },
  },
};
</script>
