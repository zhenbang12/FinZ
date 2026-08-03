<template>
  <AppLayout>
    <div class="space-y-6 max-w-5xl mx-auto pb-12">
      <!-- Header Banner -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-950 flex items-center gap-2">
            <span>Overview Dashboard</span>
          </h2>
          <p class="text-xs sm:text-sm text-slate-600 mt-1 font-bold">Real-time financial tracking & smart bill management in MYR.</p>
        </div>

        <!-- Desktop Action Buttons -->
        <div class="hidden sm:flex items-center gap-2">
          <button
            @click="openModal('expense')"
            class="px-4 py-2.5 rounded-2xl bg-slate-950 text-white font-black text-xs flex items-center justify-center gap-1.5 border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a] hover:bg-slate-900 active:translate-x-0.5 active:translate-y-0.5 transition-all"
          >
            <PlusIcon class="w-4 h-4 text-amber-400" />
            <span>Log Expense</span>
          </button>
          <button
            @click="openModal('income')"
            class="px-4 py-2.5 rounded-2xl bg-emerald-400 text-slate-950 font-black text-xs flex items-center justify-center gap-1.5 border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a] hover:bg-emerald-300 active:translate-x-0.5 active:translate-y-0.5 transition-all"
          >
            <PlusIcon class="w-4 h-4 text-slate-950" />
            <span>Log Income</span>
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

      <!-- MOBILE VIEW: Compact Quick Summary Layout -->
      <div class="block sm:hidden space-y-5">
        <!-- 2 Stat Summary Boxes: Yesterday's Spent & Today's Spent -->
        <div class="grid grid-cols-2 gap-3">
          <div class="p-4 rounded-3xl bg-slate-100 border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] space-y-1">
            <div class="flex items-center justify-between text-slate-700">
              <span class="text-[10px] font-black uppercase tracking-wider">Yesterday</span>
              <CalendarIcon class="w-3.5 h-3.5 text-slate-800" />
            </div>
            <div class="text-xl font-black text-slate-950 font-mono tracking-tight">
              {{ formatCurrency(yesterdaySpending) }}
            </div>
          </div>

          <div class="p-4 rounded-3xl bg-rose-300 border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] space-y-1">
            <div class="flex items-center justify-between text-slate-950">
              <span class="text-[10px] font-black uppercase tracking-wider">Today</span>
              <SparklesIcon class="w-3.5 h-3.5 text-slate-950" />
            </div>
            <div class="text-xl font-black text-slate-950 font-mono tracking-tight">
              {{ formatCurrency(todaySpending) }}
            </div>
          </div>
        </div>

        <!-- Mobile Net Worth + Monthly Summary -->
        <div class="relative p-5 rounded-3xl bg-amber-300 border-3 border-slate-950 shadow-[5px_5px_0px_#0f172a] space-y-2.5 text-slate-950 overflow-hidden">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-black uppercase tracking-wider">TOTAL NET WORTH</span>
            <TrendingUpIcon class="w-4 h-4 text-slate-950" />
          </div>
          <div class="text-3xl font-black font-mono tracking-tight">{{ formatCurrency(totalNetWorth) }}</div>
          <div class="flex items-center gap-3 pt-2 border-t-2 border-dashed border-slate-950/30">
            <div class="flex items-center gap-1.5 text-xs font-black">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 border border-slate-950"></span>
              <span class="text-slate-800">In:</span>
              <span class="font-mono text-slate-950">{{ formatCurrency(monthlyIncome) }}</span>
            </div>
            <div class="flex items-center gap-1.5 text-xs font-black">
              <span class="w-2.5 h-2.5 rounded-full bg-rose-500 border border-slate-950"></span>
              <span class="text-slate-800">Out:</span>
              <span class="font-mono text-slate-950">{{ formatCurrency(monthlyExpenses) }}</span>
            </div>
          </div>
        </div>

        <!-- 3 Primary Mobile Action Buttons -->
        <div class="grid grid-cols-3 gap-2">
          <button
            @click="openModal('expense')"
            class="py-3 px-2 rounded-2xl bg-slate-950 text-white font-black text-xs border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a] flex flex-col items-center justify-center gap-1 text-center active:translate-x-0.5 active:translate-y-0.5 transition-all"
          >
            <PlusIcon class="w-4 h-4 text-amber-400" />
            <span class="text-[11px]">Expense</span>
          </button>

          <button
            @click="openModal('income')"
            class="py-3 px-2 rounded-2xl bg-emerald-400 text-slate-950 font-black text-xs border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a] flex flex-col items-center justify-center gap-1 text-center active:translate-x-0.5 active:translate-y-0.5 transition-all"
          >
            <PlusIcon class="w-4 h-4 text-slate-950" />
            <span class="text-[11px]">Income</span>
          </button>

          <button
            @click="openModal('transfer')"
            class="py-3 px-2 rounded-2xl bg-sky-400 text-slate-950 font-black text-xs border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a] flex flex-col items-center justify-center gap-1 text-center active:translate-x-0.5 active:translate-y-0.5 transition-all"
          >
            <ArrowRightLeftIcon class="w-4 h-4 text-slate-950" />
            <span class="text-[11px]">Transfer</span>
          </button>
        </div>

        <!-- Pinned Accounts (Mobile) -->
        <div class="p-5 rounded-3xl bg-white border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
              <PinIcon class="w-3.5 h-3.5 text-indigo-600" />
              <span>Pinned Accounts</span>
            </h3>

            <button
              @click="showPinModal = true"
              class="text-[10px] font-black text-slate-950 hover:underline uppercase"
            >
              Manage ({{ pinnedAccounts.length }})
            </button>
          </div>

          <div class="grid grid-cols-1 gap-2">
            <div
              v-for="acc in pinnedAccounts"
              :key="acc.id"
              class="p-3 rounded-2xl bg-slate-50 border-2 border-slate-950 flex items-center justify-between shadow-[2px_2px_0px_#0f172a]"
            >
              <div class="flex items-center space-x-3">
                <div
                  class="w-7 h-7 rounded-xl flex items-center justify-center text-white font-bold shrink-0 border border-slate-950"
                  :style="{ backgroundColor: acc.color || '#0f172a' }"
                >
                  <WalletIcon class="w-3.5 h-3.5 text-white" />
                </div>
                <div>
                  <span class="font-extrabold text-slate-950 text-xs block truncate">{{ acc.name }}</span>
                  <span class="text-[10px] text-slate-600 capitalize font-bold">{{ acc.type }}</span>
                </div>
              </div>
              <span class="font-black font-mono text-slate-950 text-sm">{{ formatCurrency(acc.balance) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- DESKTOP / TABLET VIEW: Detailed Analytics & Net Worth Layout -->
      <div class="hidden sm:block space-y-6">
        <!-- Net Worth Hero Card -->
        <div class="relative p-7 sm:p-8 rounded-3xl bg-amber-300 border-3 border-slate-950 shadow-[6px_6px_0px_#0f172a] space-y-6 text-slate-950 overflow-hidden">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <span class="text-xs font-black uppercase tracking-wider text-slate-900 block">TOTAL NET WORTH</span>
              <div class="text-4xl font-black font-mono text-slate-950 tracking-tight mt-1">
                {{ formatCurrency(totalNetWorth) }}
              </div>
            </div>
            <div class="flex items-center space-x-3 text-xs font-black">
              <div class="px-4 py-2.5 rounded-2xl bg-emerald-400 border-2 border-slate-950 text-slate-950 shadow-[2px_2px_0px_#0f172a] flex items-center gap-2">
                <TrendingUpIcon class="w-4 h-4 text-slate-950" />
                <span>Income: {{ formatCurrency(monthlyIncome) }}</span>
              </div>
              <div class="px-4 py-2.5 rounded-2xl bg-rose-400 border-2 border-slate-950 text-slate-950 shadow-[2px_2px_0px_#0f172a] flex items-center gap-2">
                <ArrowUpRightIcon class="w-4 h-4 text-slate-950" />
                <span>Expenses: {{ formatCurrency(monthlyExpenses) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Accounts Grid & Pinned Accounts -->
        <div class="p-6 rounded-3xl bg-white border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-black text-slate-950 flex items-center gap-2">
              <WalletIcon class="w-5 h-5 text-slate-950" />
              <span>Financial Accounts ({{ accounts.length }})</span>
            </h3>
            <button
              v-if="accounts.length > 0"
              @click="showPinModal = true"
              class="text-xs font-black text-slate-950 hover:underline flex items-center gap-1 uppercase"
            >
              <PinIcon class="w-3.5 h-3.5 text-indigo-600" />
              <span>Manage Pins</span>
            </button>
          </div>

          <!-- Empty State -->
          <div v-if="accounts.length === 0" class="text-center py-10">
            <WalletIcon class="w-10 h-10 text-slate-400 mx-auto mb-3" />
            <p class="text-sm font-black text-slate-900 mb-1">No accounts yet</p>
            <p class="text-xs text-slate-600 font-medium mb-4">Create your first bank account, e-wallet, or cash account to start tracking.</p>
            <Link href="/accounts" class="px-5 py-2.5 rounded-2xl bg-slate-950 text-white font-black text-xs inline-flex items-center gap-1.5 border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a]">
              <PlusIcon class="w-3.5 h-3.5 text-amber-400" />
              <span>Create First Account</span>
            </Link>
          </div>

          <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
            <div
              v-for="acc in accounts"
              :key="acc.id"
              class="p-4 rounded-2xl bg-slate-50 border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a] flex items-center justify-between hover:-translate-y-0.5 transition-all"
            >
              <div class="flex items-center space-x-3 min-w-0">
                <div
                  class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-bold shrink-0 border border-slate-950"
                  :style="{ backgroundColor: acc.color || '#0f172a' }"
                >
                  <WalletIcon class="w-4 h-4 text-white" />
                </div>
                <div class="min-w-0">
                  <div class="flex items-center space-x-1.5">
                    <span class="font-extrabold text-slate-950 text-sm truncate">{{ acc.name }}</span>
                    <span v-if="acc.is_pinned" class="text-[9px] bg-indigo-100 text-indigo-900 font-black px-1.5 py-0.5 rounded-full border border-indigo-400">
                      Pinned
                    </span>
                  </div>
                  <span class="text-xs text-slate-600 capitalize font-bold block truncate">{{ acc.type }}</span>
                </div>
              </div>
              <span class="font-black font-mono text-slate-950 text-base shrink-0 ml-2">{{ formatCurrency(acc.balance) }}</span>
            </div>
          </div>
        </div>

        <!-- Top Spending Categories This Month -->
        <div v-if="topCategories.length > 0" class="p-6 rounded-3xl bg-white border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] space-y-4">
          <h3 class="text-lg font-black text-slate-950 flex items-center gap-2">
            <PieChartIcon class="w-5 h-5 text-slate-950" />
            <span>Top Spending Categories (This Month)</span>
          </h3>

          <div class="space-y-2.5">
            <div
              v-for="(cat, idx) in topCategories"
              :key="cat.category_name"
              class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50 border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a]"
            >
              <div class="flex items-center gap-3">
                <span class="text-xs font-black text-slate-950 w-5 text-center">{{ idx + 1 }}</span>
                <span class="w-3.5 h-3.5 rounded-full shrink-0 border border-slate-950" :style="{ backgroundColor: cat.color || '#0f172a' }"></span>
                <span class="text-sm font-extrabold text-slate-950">{{ cat.category_name }}</span>
              </div>
              <span class="font-black font-mono text-rose-600 text-sm">{{ formatCurrency(cat.total_amount) }}</span>
            </div>
          </div>

          <Link href="/analytics" class="text-xs font-black text-slate-950 hover:underline block text-right">
            View Full Analytics →
          </Link>
        </div>
      </div>

      <!-- Recent Ledger Activity (Shared across Mobile & Desktop) -->
      <div class="p-6 rounded-3xl bg-white border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-black text-slate-950 flex items-center gap-2">
            <ClockIcon class="w-5 h-5 text-slate-950" />
            <span>Recent Ledger Activity</span>
          </h3>
          <Link href="/transactions" class="text-xs font-black text-slate-950 hover:underline">
            View All →
          </Link>
        </div>

        <div v-if="recentTransactions.length === 0" class="text-center py-8 text-slate-500 font-bold text-xs">
          No recent transactions found. Log your first expense or transfer above!
        </div>

        <div v-else class="space-y-2.5">
          <div
            v-for="tx in recentTransactions"
            :key="tx.id"
            class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50 border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a] text-xs"
          >
            <div class="flex items-center space-x-3.5 min-w-0">
              <div
                :class="[
                  tx.type === 'expense' ? 'bg-rose-400 text-slate-950' :
                  tx.type === 'income' ? 'bg-emerald-400 text-slate-950' :
                  'bg-sky-400 text-slate-950',
                  'w-9 h-9 rounded-xl flex items-center justify-center border-2 border-slate-950 shrink-0'
                ]"
              >
                <ArrowUpRightIcon v-if="tx.type === 'expense'" class="w-4 h-4" />
                <ArrowDownLeftIcon v-else-if="tx.type === 'income'" class="w-4 h-4" />
                <ArrowRightLeftIcon v-else class="w-4 h-4" />
              </div>

              <div class="min-w-0">
                <span class="font-black text-slate-950 text-sm block truncate">
                  {{ tx.notes || (tx.category ? tx.category.name : 'Transaction') }}
                </span>
                <div class="text-xs text-slate-600 font-bold flex items-center gap-1.5 truncate mt-0.5">
                  <span class="text-slate-950 font-extrabold">{{ tx.account?.name }}</span>
                  <span v-if="tx.type === 'transfer' && tx.destination_account" class="text-slate-950 font-black">→ {{ tx.destination_account.name }}</span>
                  <span v-if="tx.category">• {{ tx.category.name }}</span>
                  <span>• {{ formatDate(tx.date) }}</span>
                </div>
              </div>
            </div>

            <span
              :class="[
                tx.type === 'expense' ? 'text-rose-600' :
                tx.type === 'income' ? 'text-emerald-600' :
                'text-slate-950',
                'font-black font-mono text-sm shrink-0 ml-3'
              ]"
            >
              {{ tx.type === 'expense' ? '-' : (tx.type === 'income' ? '+' : '') }}{{ formatCurrency(tx.amount) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Pin Accounts Selection Modal -->
    <div v-if="showPinModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl border-3 border-slate-950 shadow-[8px_8px_0px_#0f172a] max-w-md w-full p-6 space-y-4 animate-scale-up text-slate-950">
        <div class="flex items-center justify-between border-b-2 border-slate-950 pb-3">
          <h3 class="text-xl font-black tracking-tight text-slate-950 flex items-center gap-2">
            <PinIcon class="w-5 h-5 text-indigo-600" />
            <span>Pin Favourite Accounts</span>
          </h3>
          <button @click="showPinModal = false" class="w-8 h-8 rounded-full bg-slate-100 border-2 border-slate-950 flex items-center justify-center text-slate-950 font-bold hover:bg-rose-100 transition-colors">
            <XIcon class="w-4 h-4" />
          </button>
        </div>

        <p class="text-xs text-slate-600 font-bold">Select maximum 3 favourite accounts to pin to Quick Hub</p>

        <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
          <div
            v-for="acc in accounts"
            :key="acc.id"
            @click="togglePinAccount(acc)"
            :class="[
              acc.is_pinned ? 'bg-amber-300 border-slate-950' : 'bg-slate-50 border-slate-950',
              'p-3 rounded-2xl border-2 flex items-center justify-between cursor-pointer transition-all shadow-[2px_2px_0px_#0f172a]'
            ]"
          >
            <div class="flex items-center space-x-3">
              <div
                class="w-7 h-7 rounded-xl flex items-center justify-center text-white text-xs font-bold border border-slate-950"
                :style="{ backgroundColor: acc.color || '#0f172a' }"
              >
                <WalletIcon class="w-3.5 h-3.5 text-white" />
              </div>
              <div>
                <span class="font-extrabold text-xs text-slate-950 block">{{ acc.name }}</span>
                <span class="text-[10px] font-mono font-bold text-slate-700">{{ formatCurrency(acc.balance) }}</span>
              </div>
            </div>

            <span
              :class="[
                acc.is_pinned ? 'bg-slate-950 text-white' : 'bg-slate-200 text-slate-800',
                'px-2.5 py-1 rounded-full text-[10px] font-black border border-slate-950'
              ]"
            >
              {{ acc.is_pinned ? 'Pinned' : 'Pin' }}
            </span>
          </div>
        </div>

        <div class="flex justify-end pt-2 border-t-2 border-slate-950">
          <button
            @click="showPinModal = false"
            class="px-6 py-2.5 rounded-2xl bg-slate-950 text-amber-300 font-black text-xs border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a]"
          >
            Done
          </button>
        </div>
      </div>
    </div>

    <!-- Quick Transaction Logging Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl border-3 border-slate-950 shadow-[8px_8px_0px_#0f172a] max-w-lg w-full p-6 space-y-5 animate-scale-up text-slate-950">
        <div class="flex items-center justify-between border-b-2 border-slate-950 pb-3">
          <h3 class="text-xl font-black text-slate-950">
            {{ modalType === 'expense' ? 'Log Expense' : (modalType === 'income' ? 'Log Income' : 'Cross-Account Transfer') }}
          </h3>
          <button @click="closeModal" class="w-8 h-8 rounded-full bg-slate-100 border-2 border-slate-950 flex items-center justify-center text-slate-950 font-bold hover:bg-rose-100 transition-colors">
            <XIcon class="w-4 h-4" />
          </button>
        </div>

        <!-- Transaction Type Switcher Pill -->
        <div class="flex items-center bg-slate-100 p-1 rounded-2xl border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a]">
          <button
            type="button"
            @click="setModalType('expense')"
            :class="[modalType === 'expense' ? 'bg-rose-400 text-slate-950 font-black shadow-[2px_2px_0px_#0f172a]' : 'text-slate-800 font-bold', 'flex-1 py-2 text-xs rounded-xl transition-all text-center']"
          >
            Expense
          </button>
          <button
            type="button"
            @click="setModalType('income')"
            :class="[modalType === 'income' ? 'bg-emerald-400 text-slate-950 font-black shadow-[2px_2px_0px_#0f172a]' : 'text-slate-800 font-bold', 'flex-1 py-2 text-xs rounded-xl transition-all text-center']"
          >
            Income
          </button>
          <button
            type="button"
            @click="setModalType('transfer')"
            :class="[modalType === 'transfer' ? 'bg-sky-400 text-slate-950 font-black shadow-[2px_2px_0px_#0f172a]' : 'text-slate-800 font-bold', 'flex-1 py-2 text-xs rounded-xl transition-all text-center']"
          >
            Transfer
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
                class="w-full pl-12 pr-4 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-black font-mono text-lg focus:outline-none focus:ring-2 focus:ring-amber-400"
              />
            </div>

            <!-- Insufficient Balance Real-Time Warning -->
            <div
              v-if="form.type !== 'income' && selectedOriginAccount && (parseFloat(form.amount) > selectedOriginAccount.balance)"
              class="mt-2 p-2.5 rounded-2xl bg-rose-300 border-2 border-slate-950 text-slate-950 text-xs font-black flex items-center gap-1.5 shadow-[2px_2px_0px_#0f172a]"
            >
              <AlertTriangleIcon class="w-4 h-4 text-slate-950 shrink-0" />
              <span>Insufficient funds in {{ selectedOriginAccount.name }}! Available: {{ formatCurrency(selectedOriginAccount.balance) }}</span>
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
              <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">
                Category
              </label>
              <select
                v-model="form.category_id"
                required
                class="w-full px-3.5 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-sm focus:outline-none"
              >
                <option value="" disabled>Select category</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.name }}
                </option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">Date</label>
            <input
              v-model="form.date"
              type="date"
              required
              class="w-full px-3.5 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-xs focus:outline-none"
            />
          </div>

          <div>
            <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">Notes / Merchant Name</label>
            <input
              v-model="form.notes"
              type="text"
              placeholder="e.g. Grocery shopping at Lotus's"
              class="w-full px-4 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-xs focus:outline-none"
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
              :disabled="form.processing || (form.type !== 'income' && selectedOriginAccount && parseFloat(form.amount) > selectedOriginAccount.balance)"
              class="px-6 py-2.5 rounded-2xl bg-slate-950 text-amber-300 border-2 border-slate-950 font-black text-xs shadow-[3px_3px_0px_#0f172a] hover:bg-slate-900 active:translate-x-0.5 active:translate-y-0.5 disabled:opacity-50"
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
import { ref, computed } from 'vue';
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
  AlertTriangle as AlertTriangleIcon,
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

const selectedOriginAccount = computed(() => {
  return props.accounts.find(a => a.id === form.account_id) || null;
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
