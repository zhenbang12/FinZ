<template>
  <aside aria-label="Demo Mode Notice">
    <!-- Top Neo-Brutalist Demo Mode Banner -->
    <div class="bg-amber-300 border-b-3 border-slate-950 px-4 py-2.5 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-[0_3px_0px_#0f172a] z-40 relative">
      <div class="flex items-center space-x-3 text-slate-950">
        <div class="flex items-center space-x-1.5 bg-slate-950 text-amber-300 px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider border border-slate-950 shrink-0">
          <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
          <span>Demo Sandbox</span>
        </div>
        <p class="text-xs font-black text-slate-950 leading-tight">
          <span class="hidden md:inline">You are viewing FinZ via private link. </span>
          <span class="text-slate-800 font-extrabold">All core modules active in Read-Only mode. Modifications are locked.</span>
        </p>
      </div>

      <div class="flex items-center space-x-2 shrink-0">
        <button
          @click="showTourModal = true"
          type="button"
          class="px-3 py-1.5 rounded-xl bg-white hover:bg-slate-50 text-slate-950 font-black text-xs border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a] active:translate-x-0.5 active:translate-y-0.5 transition-all flex items-center gap-1.5"
        >
          <CompassIcon class="w-3.5 h-3.5 text-slate-950" />
          <span>App Tour</span>
        </button>

        <button
          @click="exitDemo"
          type="button"
          class="px-3 py-1.5 rounded-xl bg-slate-950 hover:bg-rose-600 text-white font-black text-xs border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a] active:translate-x-0.5 active:translate-y-0.5 transition-all flex items-center gap-1.5"
        >
          <LogOutIcon class="w-3.5 h-3.5 text-amber-300" />
          <span>Exit Demo</span>
        </button>
      </div>
    </div>

    <!-- Interactive Neo-Brutalist Tour / Features Modal -->
    <div
      v-if="showTourModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs animate-fade-in"
      @click.self="showTourModal = false"
    >
      <div class="bg-white rounded-3xl border-3 border-slate-950 shadow-[8px_8px_0px_#0f172a] max-w-2xl w-full p-6 sm:p-8 space-y-6 max-h-[90vh] overflow-y-auto text-slate-950">
        <!-- Modal Header -->
        <div class="flex items-start justify-between border-b-2 border-slate-950 pb-4">
          <div class="flex items-center space-x-3">
            <div class="w-12 h-12 rounded-2xl bg-amber-300 border-2 border-slate-950 flex items-center justify-center font-black text-xl text-slate-950 shadow-[3px_3px_0px_#0f172a]">
              ⚡
            </div>
            <div>
              <h2 class="text-xl font-black text-slate-950 font-mono">FinZ Interactive Showcase</h2>
              <p class="text-xs text-slate-600 font-bold">What to explore in this live sandboxed demonstration</p>
            </div>
          </div>
          <button
            @click="showTourModal = false"
            class="p-2 rounded-xl bg-slate-100 border-2 border-slate-950 hover:bg-rose-300 transition-colors"
          >
            <XIcon class="w-4 h-4 text-slate-950" />
          </button>
        </div>

        <!-- Modules Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
          <div class="p-3.5 rounded-2xl bg-slate-50 border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a] space-y-1.5">
            <div class="flex items-center space-x-2">
              <span class="w-6 h-6 rounded-lg bg-amber-400 text-slate-950 flex items-center justify-center font-black text-xs border border-slate-950">1</span>
              <h4 class="text-xs font-black uppercase text-slate-950">Overview & Net Worth</h4>
            </div>
            <p class="text-[11px] font-bold text-slate-600">
              Live consolidated net worth (MYR 15,330.00) across Maybank, GXBank, and Touch 'n Go eWallet with daily expenditure tracking.
            </p>
          </div>

          <div class="p-3.5 rounded-2xl bg-slate-50 border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a] space-y-1.5">
            <div class="flex items-center space-x-2">
              <span class="w-6 h-6 rounded-lg bg-emerald-400 text-slate-950 flex items-center justify-center font-black text-xs border border-slate-950">2</span>
              <h4 class="text-xs font-black uppercase text-slate-950">SmartSplit Receipt OCR</h4>
            </div>
            <p class="text-[11px] font-bold text-slate-600">
              Check out the parsed <strong>Haidilao Hotpot</strong> receipt with 6% SST, 10% Service Charge, and interactive pro-rata bill split calculator.
            </p>
          </div>

          <div class="p-3.5 rounded-2xl bg-slate-50 border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a] space-y-1.5">
            <div class="flex items-center space-x-2">
              <span class="w-6 h-6 rounded-lg bg-sky-400 text-slate-950 flex items-center justify-center font-black text-xs border border-slate-950">3</span>
              <h4 class="text-xs font-black uppercase text-slate-950">Shared Subscriptions</h4>
            </div>
            <p class="text-[11px] font-bold text-slate-600">
              Inspect YouTube Premium Family dues, tracking payments and pending debts across roommates (Alex, Sarah, Jason).
            </p>
          </div>

          <div class="p-3.5 rounded-2xl bg-slate-50 border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a] space-y-1.5">
            <div class="flex items-center space-x-2">
              <span class="w-6 h-6 rounded-lg bg-fuchsia-400 text-slate-950 flex items-center justify-center font-black text-xs border border-slate-950">4</span>
              <h4 class="text-xs font-black uppercase text-slate-950">Ledger & Analytics</h4>
            </div>
            <p class="text-[11px] font-bold text-slate-600">
              16+ Malaysian transactions categorized with visual 6-month cashflow bar charts and category spending breakdown.
            </p>
          </div>
        </div>

        <!-- Security Notice -->
        <div class="p-4 rounded-2xl bg-amber-100 border-2 border-slate-950 space-y-1">
          <p class="text-xs font-black text-slate-950 flex items-center gap-1.5">
            <ShieldCheckIcon class="w-4 h-4 text-slate-950" />
            <span>Strict Sandbox Security Guarantee</span>
          </p>
          <p class="text-[11px] font-bold text-slate-700">
            For maximum security and data consistency, all write operations (adding, updating, or deleting records) and superuser routes are strictly restricted. You can safely explore the entire application UI and calculations.
          </p>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3 pt-2">
          <button
            @click="showTourModal = false"
            type="button"
            class="px-5 py-2.5 rounded-2xl bg-slate-950 text-amber-300 hover:bg-slate-900 font-black text-xs border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a] active:translate-x-0.5 active:translate-y-0.5 transition-all"
          >
            Start Exploring
          </button>
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import {
  Compass as CompassIcon,
  LogOut as LogOutIcon,
  X as XIcon,
  ShieldCheck as ShieldCheckIcon,
} from 'lucide-vue-next';

const showTourModal = ref(false);

const exitDemo = () => {
  router.post('/demo/exit');
};
</script>
