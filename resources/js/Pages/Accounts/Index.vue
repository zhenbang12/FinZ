<template>
  <AppLayout>
    <div class="space-y-5 sm:space-y-6 max-w-5xl mx-auto pb-16">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h2 class="text-xl sm:text-3xl font-black tracking-tight text-slate-900 flex items-center gap-2">
            <span>Account Management</span>
          </h2>
          <p class="text-[11px] sm:text-sm text-slate-600 mt-0.5 font-medium">
            Manage your bank accounts, savings, e-wallets, and credit cards.
          </p>
        </div>

        <div class="flex items-center gap-2 sm:gap-3 w-full sm:w-auto justify-between sm:justify-end">
          <!-- View Toggle (Stacked Deck vs Standard Grid) -->
          <div class="flex items-center bg-slate-200/80 p-0.5 sm:p-1 rounded-2xl border-2 border-slate-900 shadow-[2px_2px_0px_#0f172a]">
            <button
              @click="viewMode = 'stacked'"
              :class="[
                viewMode === 'stacked' ? 'bg-amber-400 text-slate-950 font-black shadow-[2px_2px_0px_#0f172a]' : 'text-slate-700 hover:text-slate-950 font-bold',
                'px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-xl text-[11px] sm:text-xs transition-all flex items-center gap-1'
              ]"
              title="Neo-Brutalist Stacked Card View"
            >
              <LayersIcon class="w-3.5 h-3.5" />
              <span>Stacked</span>
            </button>

            <button
              @click="viewMode = 'grid'"
              :class="[
                viewMode === 'grid' ? 'bg-slate-900 text-white font-black shadow-[2px_2px_0px_#0f172a]' : 'text-slate-700 hover:text-slate-950 font-bold',
                'px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-xl text-[11px] sm:text-xs transition-all flex items-center gap-1'
              ]"
              title="Standard Category Grid View"
            >
              <LayoutGridIcon class="w-3.5 h-3.5" />
              <span>Grid</span>
            </button>
          </div>

          <!-- Add Account Button -->
          <button
            @click="openModal('create')"
            class="px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs flex items-center justify-center gap-1.5 border-2 border-slate-900 shadow-[3px_3px_0px_#0f172a] active:translate-x-0.5 active:translate-y-0.5 transition-all shrink-0"
          >
            <PlusIcon class="w-4 h-4 text-amber-400" />
            <span>Add Account</span>
          </button>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- VIEW MODE 1: NEO-BRUTALIST STACKED CARD DECK (IMAGE REFERENCE DESIGN)    -->
      <!-- ========================================================================= -->
      <div v-if="viewMode === 'stacked'" class="space-y-5 sm:space-y-6 animate-fade-in">
        <!-- Main Yellow Hero Summary Card with Scalloped Wavy Edge -->
        <div class="relative bg-amber-300 rounded-3xl border-3 border-slate-950 shadow-[5px_5px_0px_#0f172a] p-5 sm:p-7 space-y-3 sm:space-y-4 text-slate-950 overflow-hidden">
          <!-- Top Row: Label & Plus Action -->
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-1.5 font-black text-xs sm:text-sm uppercase tracking-wider text-slate-900">
              <span>TOTAL NET WORTH</span>
              <button @click="showSummaryDetails = !showSummaryDetails" class="p-1 hover:bg-amber-400 rounded-lg transition-colors">
                <ChevronDownIcon :class="['w-4 h-4 transition-transform duration-200', showSummaryDetails ? 'rotate-180' : '']" />
              </button>
            </div>

            <button
              @click="openModal('create')"
              class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-slate-950 text-amber-300 hover:bg-slate-900 flex items-center justify-center font-black text-base sm:text-lg border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a] transition-all"
              title="Add New Account"
            >
              +
            </button>
          </div>

          <!-- Big Balance Display -->
          <div>
            <div class="text-2xl sm:text-5xl font-black tracking-tight text-slate-950 font-mono">
              {{ formatCurrency(totalNetWorth) }}
            </div>
            <div class="flex items-center gap-2 text-[11px] sm:text-xs font-black text-slate-800 mt-1.5 sm:mt-2">
              <span class="inline-flex items-center justify-center bg-slate-950 text-amber-300 w-4 h-4 rounded-full text-[10px] font-bold">i</span>
              <span>ACCOUNTS ACTIVE: {{ accounts.length }}</span>
            </div>
          </div>

          <!-- Expanded Breakdown -->
          <div v-if="showSummaryDetails" class="pt-3.5 border-t-2 border-dashed border-slate-950/30 grid grid-cols-3 gap-2 sm:gap-3 text-xs">
            <div>
              <span class="font-bold text-slate-700 text-[9px] sm:text-[10px] block uppercase">Current</span>
              <span class="font-black text-slate-950 font-mono text-xs sm:text-sm">{{ formatCurrency(categoryTotals.current || 0) }}</span>
            </div>
            <div>
              <span class="font-bold text-slate-700 text-[9px] sm:text-[10px] block uppercase">Savings</span>
              <span class="font-black text-slate-950 font-mono text-xs sm:text-sm">{{ formatCurrency(categoryTotals.savings || 0) }}</span>
            </div>
            <div>
              <span class="font-bold text-slate-700 text-[9px] sm:text-[10px] block uppercase">E-Wallets</span>
              <span class="font-black text-slate-950 font-mono text-xs sm:text-sm">{{ formatCurrency(categoryTotals.other || 0) }}</span>
            </div>
          </div>

          <!-- Bottom Scalloped Decorative Wave SVG -->
          <div class="absolute -bottom-1 left-0 right-0 h-3 overflow-hidden pointer-events-none opacity-20">
            <svg class="w-full h-full fill-slate-950" viewBox="0 0 1200 120" preserveAspectRatio="none">
              <path d="M0,0 C150,90 350,-40 500,40 C650,120 900,10 1200,40 L1200,120 L0,120 Z"></path>
            </svg>
          </div>
        </div>

        <!-- Stacked Cards Container Deck -->
        <div class="space-y-0.5">
          <div class="flex items-center justify-between px-1 sm:px-2 mb-2 sm:mb-3">
            <span class="text-[11px] sm:text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
              <CreditCardIcon class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
              <span>YOUR ACCOUNTS DECK ({{ sortedAccounts.length }})</span>
            </span>
            <span class="text-[10px] sm:text-[11px] font-bold text-slate-500">Tap card to inspect</span>
          </div>

          <!-- Empty State -->
          <div v-if="sortedAccounts.length === 0" class="p-6 sm:p-8 text-center bg-white rounded-3xl border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] space-y-3">
            <WalletIcon class="w-10 h-10 text-slate-400 mx-auto" />
            <h4 class="font-black text-base text-slate-900">No accounts registered yet</h4>
            <p class="text-xs text-slate-500 max-w-sm mx-auto">Add your Maybank, GXBank, Touch 'n Go or credit cards to see them in this stacked card view.</p>
            <button @click="openModal('create')" class="px-4 py-2 rounded-xl bg-slate-950 text-white font-bold text-xs">
              + Add First Account
            </button>
          </div>

          <!-- Tapered Stack Deck Cards (Mobile Optimized Layout) -->
          <div v-else class="flex flex-col items-center w-full space-y-[-14px]">
            <div
              v-for="(acc, index) in sortedAccounts"
              :key="acc.id"
              :style="{
                backgroundColor: getAccountColor(acc),
                width: expandedCardId === acc.id ? '100%' : `${Math.max(92, 100 - (index * 1.0))}%`,
                zIndex: expandedCardId === acc.id ? 40 : (sortedAccounts.length - index),
              }"
              :class="[
                'transition-all duration-200 rounded-3xl border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] overflow-hidden cursor-pointer',
                getContrastTextColor(getAccountColor(acc)),
                expandedCardId === acc.id ? 'ring-4 ring-slate-950 scale-[1.01] my-3' : 'hover:-translate-y-1'
              ]"
              @click="toggleCardExpand(acc.id)"
            >
              <!-- Card Header Row (Always Visible) -->
              <div class="p-3.5 sm:p-5 flex items-center justify-between gap-2 sm:gap-3 select-none">
                <!-- Left: Big Balance -->
                <div class="min-w-0 flex-1">
                  <div class="text-lg sm:text-2xl font-black font-mono tracking-tight truncate">
                    {{ formatCurrency(acc.balance) }}
                  </div>
                  <div class="text-[9px] sm:text-[10px] font-black uppercase tracking-wider opacity-90 mt-0.5 truncate">
                    {{ acc.currency }} • {{ getCategoryLabel(acc.category) }}
                  </div>
                </div>

                <!-- Right: Bank Name Pill with Logo/Icon (Responsive Sizing) -->
                <div class="flex items-center gap-1.5 sm:gap-2 shrink-0 max-w-[55%] sm:max-w-none">
                  <div class="px-2.5 sm:px-3.5 py-1 sm:py-1.5 rounded-xl bg-slate-950 text-white font-black text-[10px] sm:text-xs flex items-center gap-1.5 shadow-[2px_2px_0px_rgba(0,0,0,0.3)] min-w-0">
                    <component :is="getAccountTypeIcon(acc.type)" class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-amber-400 shrink-0" />
                    <span class="uppercase tracking-wide font-mono text-[10px] sm:text-[11px] truncate max-w-[100px] sm:max-w-none">{{ acc.name }}</span>
                  </div>

                  <!-- Expand Arrow -->
                  <div
                    :class="[
                      getContrastTextColor(getAccountColor(acc)) === 'text-white' ? 'bg-white/20' : 'bg-slate-950/10',
                      'w-6 h-6 sm:w-7 sm:h-7 rounded-full flex items-center justify-center shrink-0'
                    ]"
                  >
                    <ChevronDownIcon :class="['w-3.5 h-3.5 sm:w-4 sm:h-4 transition-transform duration-200', expandedCardId === acc.id ? 'rotate-180' : '']" />
                  </div>
                </div>
              </div>

              <!-- Expanded Details Section (Removed Custom Accent Box, Mobile 3-Box Grid) -->
              <div
                v-if="expandedCardId === acc.id"
                :class="[
                  getContrastTextColor(getAccountColor(acc)) === 'text-white' ? 'bg-black/20 text-white border-white/20' : 'bg-slate-950/10 text-slate-950 border-slate-950/20',
                  'px-3.5 sm:px-5 pb-4 sm:pb-5 pt-2 border-t-2 space-y-3 sm:space-y-4 animate-fade-in'
                ]"
                @click.stop
              >
                <div class="grid grid-cols-3 gap-2 sm:gap-3 text-xs font-bold pt-2">
                  <div class="p-2 sm:p-3 bg-white/90 text-slate-950 rounded-2xl border-2 border-slate-950">
                    <span class="text-[9px] sm:text-[10px] text-slate-600 uppercase block font-black">Starting</span>
                    <span class="font-mono text-xs sm:text-sm font-black block truncate">{{ formatCurrency(acc.initial_balance) }}</span>
                  </div>

                  <div class="p-2 sm:p-3 bg-white/90 text-slate-950 rounded-2xl border-2 border-slate-950">
                    <span class="text-[9px] sm:text-[10px] text-slate-600 uppercase block font-black">Type</span>
                    <span class="capitalize text-xs sm:text-sm font-black block truncate">{{ acc.type.replace('_', ' ') }}</span>
                  </div>

                  <div class="p-2 sm:p-3 bg-white/90 text-slate-950 rounded-2xl border-2 border-slate-950">
                    <span class="text-[9px] sm:text-[10px] text-slate-600 uppercase block font-black">Category</span>
                    <span class="text-xs sm:text-sm font-black block truncate">{{ getCategoryLabel(acc.category) }}</span>
                  </div>
                </div>

                <!-- Responsive Action Controls for Mobile -->
                <div class="flex items-center justify-between gap-2 pt-1">
                  <div class="flex items-center gap-1 sm:gap-1.5">
                    <button
                      @click="reorderItem(sortedAccounts, index, -1)"
                      :disabled="index === 0"
                      class="px-2 sm:px-3 py-1.5 rounded-xl bg-white border-2 border-slate-950 text-slate-950 font-black text-xs hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed shadow-[2px_2px_0px_#0f172a] flex items-center gap-1"
                      title="Move Up"
                    >
                      <ArrowUpIcon class="w-3.5 h-3.5" />
                      <span class="hidden sm:inline">Move Up</span>
                    </button>

                    <button
                      @click="reorderItem(sortedAccounts, index, 1)"
                      :disabled="index === sortedAccounts.length - 1"
                      class="px-2 sm:px-3 py-1.5 rounded-xl bg-white border-2 border-slate-950 text-slate-950 font-black text-xs hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed shadow-[2px_2px_0px_#0f172a] flex items-center gap-1"
                      title="Move Down"
                    >
                      <ArrowDownIcon class="w-3.5 h-3.5" />
                      <span class="hidden sm:inline">Move Down</span>
                    </button>
                  </div>

                  <div class="flex items-center gap-1.5">
                    <button
                      @click="openModal('edit', acc)"
                      class="px-3 sm:px-4 py-1.5 rounded-xl bg-slate-950 text-white font-black text-xs hover:bg-slate-800 shadow-[2px_2px_0px_#0f172a] flex items-center gap-1.5"
                    >
                      <PencilIcon class="w-3.5 h-3.5 text-amber-400" />
                      <span>Edit Account</span>
                    </button>

                    <button
                      @click="deleteAccount(acc)"
                      class="px-2.5 sm:px-3.5 py-1.5 rounded-xl bg-rose-500 text-white border-2 border-slate-950 font-black text-xs hover:bg-rose-600 shadow-[2px_2px_0px_#0f172a] flex items-center gap-1"
                      title="Delete Account"
                    >
                      <Trash2Icon class="w-3.5 h-3.5" />
                      <span class="hidden sm:inline">Delete</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- VIEW MODE 2: STANDARD CATEGORY GRID                                      -->
      <!-- ========================================================================= -->
      <div v-else class="space-y-5 sm:space-y-6 animate-fade-in">
        <!-- Combined Balance Banner & Category Totals Summary -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
          <!-- Net Worth Card -->
          <div class="minimal-card p-4 col-span-2 lg:col-span-1 flex flex-col justify-between border-l-4 border-l-slate-900 rounded-2xl">
            <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider block">Total Net Worth</span>
            <div class="text-lg sm:text-xl font-black text-slate-900 mt-1.5">{{ formatCurrency(totalNetWorth) }}</div>
            <span class="text-[10px] text-slate-500 font-semibold mt-1 block">{{ accounts.length }} Total Accounts</span>
          </div>

          <!-- Current Accounts Card -->
          <div class="minimal-card p-4 flex flex-col justify-between border-l-4 border-l-blue-600 rounded-2xl">
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Current/ Spending</span>
              <span class="p-1.5 bg-blue-50 rounded-lg text-blue-600">
                <CreditCardIcon class="w-4 h-4" />
              </span>
            </div>
            <div class="text-lg sm:text-xl font-black text-slate-900 mt-1.5">{{ formatCurrency(categoryTotals.current || 0) }}</div>
            <span class="text-[10px] text-slate-500 mt-1">Daily spending</span>
          </div>

          <!-- Savings Accounts Card -->
          <div class="minimal-card p-4 flex flex-col justify-between border-l-4 border-l-emerald-600 rounded-2xl">
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Savings/ Investment</span>
              <span class="p-1.5 bg-emerald-50 rounded-lg text-emerald-600">
                <PiggyBankIcon class="w-4 h-4" />
              </span>
            </div>
            <div class="text-lg sm:text-xl font-black text-slate-900 mt-1.5">{{ formatCurrency(categoryTotals.savings || 0) }}</div>
            <span class="text-[10px] text-slate-500 mt-1">Emergency & yield</span>
          </div>

          <!-- E-Wallets & Other Card -->
          <div class="minimal-card p-4 flex flex-col justify-between border-l-4 border-l-indigo-600 rounded-2xl">
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">E-Wallets & Credit</span>
              <span class="p-1.5 bg-indigo-50 rounded-lg text-indigo-600">
                <WalletIcon class="w-4 h-4" />
              </span>
            </div>
            <div class="text-lg sm:text-xl font-black text-slate-900 mt-1.5">{{ formatCurrency(categoryTotals.other || 0) }}</div>
            <span class="text-[10px] text-slate-500 mt-1">TnG, Credit Cards, Cash</span>
          </div>
        </div>

        <!-- Categorized Account Lists -->
        <div v-for="section in categorizedSections" :key="section.key" class="space-y-3">
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

          <div v-if="section.items.length === 0" class="p-5 text-center border border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
            <p class="text-xs text-slate-400 font-medium">No {{ section.title.toLowerCase() }} configured.</p>
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
            <div
              v-for="(acc, index) in section.items"
              :key="acc.id"
              class="minimal-card p-4 flex flex-col justify-between space-y-3 relative"
            >
              <div class="flex items-start justify-between gap-2">
                <div class="flex items-center space-x-3 min-w-0">
                  <div
                    class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold text-xs shadow-xs shrink-0 border border-slate-900"
                    :style="{ backgroundColor: getAccountColor(acc) }"
                  >
                    {{ acc.name.charAt(0) }}
                  </div>
                  <div class="min-w-0">
                    <h4 class="font-bold text-sm text-slate-900 truncate">{{ acc.name }}</h4>
                    <div class="flex items-center gap-1.5 mt-0.5">
                      <span class="inline-block px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[9px] uppercase font-bold tracking-wider">
                        {{ getCategoryLabel(acc.category) }}
                      </span>
                    </div>
                  </div>
                </div>

                <div class="flex items-center space-x-1 shrink-0">
                  <button
                    @click="reorderItem(section.items, index, -1)"
                    :disabled="index === 0"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-900 hover:bg-slate-100 disabled:opacity-20 disabled:cursor-not-allowed transition-colors"
                  >
                    <ArrowUpIcon class="w-3.5 h-3.5" />
                  </button>
                  <button
                    @click="reorderItem(section.items, index, 1)"
                    :disabled="index === section.items.length - 1"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-900 hover:bg-slate-100 disabled:opacity-20 disabled:cursor-not-allowed transition-colors"
                  >
                    <ArrowDownIcon class="w-3.5 h-3.5" />
                  </button>
                  <button
                    @click="openModal('edit', acc)"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-900 hover:bg-slate-100 transition-colors"
                  >
                    <PencilIcon class="w-3.5 h-3.5" />
                  </button>
                  <button
                    @click="deleteAccount(acc)"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
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
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Account Create / Edit Modal -->
      <div v-if="showModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl border-3 border-slate-950 shadow-[8px_8px_0px_#0f172a] max-w-lg w-full p-6 space-y-5 animate-scale-up text-slate-950">
          <div class="flex items-center justify-between border-b-2 border-slate-950 pb-3">
            <h3 class="text-xl font-black tracking-tight text-slate-950">
              {{ isEditing ? 'Edit Account Details' : 'Create New Account' }}
            </h3>
            <button @click="closeModal" class="w-8 h-8 rounded-full bg-slate-100 border-2 border-slate-950 flex items-center justify-center text-slate-950 font-bold hover:bg-rose-100 transition-colors">
              <XIcon class="w-4 h-4" />
            </button>
          </div>

          <form @submit.prevent="saveAccount" class="space-y-4">
            <div>
              <label class="block text-xs font-extrabold text-slate-900 mb-1 uppercase tracking-wider">
                Account / Bank Name
              </label>
              <input
                v-model="form.name"
                type="text"
                required
                placeholder="e.g. Maybank Savings, GX Bank, Touch 'n Go, Boost PayFlex"
                class="w-full px-4 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"
              />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
              <div>
                <label class="block text-xs font-extrabold text-slate-900 mb-1 uppercase tracking-wider">
                  Category
                </label>
                <select
                  v-model="form.category"
                  required
                  class="w-full px-3.5 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-sm focus:outline-none"
                >
                  <option value="current">Current / Spending</option>
                  <option value="savings">Savings / Investment</option>
                  <option value="other">E-Wallets, Cash & Credit</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-extrabold text-slate-900 mb-1 uppercase tracking-wider">
                  Type
                </label>
                <select
                  v-model="form.type"
                  required
                  class="w-full px-3.5 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-sm focus:outline-none"
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
                <label class="block text-xs font-extrabold text-slate-900 mb-1 uppercase tracking-wider">
                  Currency
                </label>
                <input
                  v-model="form.currency"
                  type="text"
                  readonly
                  class="w-full px-4 py-3 rounded-2xl bg-slate-100 border-2 border-slate-300 text-slate-500 font-bold text-sm cursor-not-allowed"
                />
              </div>

              <div v-if="!isEditing">
                <label class="block text-xs font-extrabold text-slate-900 mb-1 uppercase tracking-wider">
                  Starting Balance
                </label>
                <input
                  v-model="form.initial_balance"
                  type="number"
                  step="0.01"
                  required
                  placeholder="0.00"
                  class="w-full px-4 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-mono font-bold text-sm focus:outline-none"
                />
              </div>
            </div>

            <div>
              <label class="block text-xs font-extrabold text-slate-900 mb-1 uppercase tracking-wider">
                Custom Accent Color
              </label>
              <div class="flex items-center gap-3">
                <input
                  v-model="form.color"
                  type="color"
                  class="w-12 h-12 rounded-2xl bg-slate-50 border-2 border-slate-950 cursor-pointer p-1"
                />
                <span class="text-xs font-mono font-black text-slate-950 uppercase">{{ form.color }}</span>

                <!-- Quick Color Palette Pickers -->
                <div class="flex items-center gap-1.5 ml-auto">
                  <button type="button" @click="form.color = '#FBBF24'" class="w-6 h-6 rounded-full bg-[#FBBF24] border border-slate-950" title="Maybank Yellow"></button>
                  <button type="button" @click="form.color = '#06B6D4'" class="w-6 h-6 rounded-full bg-[#06B6D4] border border-slate-950" title="GXBank Cyan"></button>
                  <button type="button" @click="form.color = '#348AF5'" class="w-6 h-6 rounded-full bg-[#348AF5] border border-slate-950" title="Sky Blue"></button>
                  <button type="button" @click="form.color = '#84CC16'" class="w-6 h-6 rounded-full bg-[#84CC16] border border-slate-950" title="Lime Green"></button>
                  <button type="button" @click="form.color = '#2563EB'" class="w-6 h-6 rounded-full bg-[#2563EB] border border-slate-950" title="Boost / Payflex Blue"></button>
                  <button type="button" @click="form.color = '#F97316'" class="w-6 h-6 rounded-full bg-[#F97316] border border-slate-950" title="Touch 'n Go Orange"></button>
                  <button type="button" @click="form.color = '#EC4899'" class="w-6 h-6 rounded-full bg-[#EC4899] border border-slate-950" title="UOB Pink"></button>
                </div>
              </div>
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
                :disabled="form.processing"
                class="px-6 py-2.5 rounded-2xl bg-slate-950 text-amber-300 border-2 border-slate-950 font-black text-xs shadow-[3px_3px_0px_#0f172a] hover:bg-slate-900 active:translate-x-0.5 active:translate-y-0.5 disabled:opacity-50"
              >
                {{ isEditing ? 'Update Account' : 'Save Account' }}
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
  Layers as LayersIcon,
  LayoutGrid as LayoutGridIcon,
  ChevronDown as ChevronDownIcon,
  Building2 as BankIcon,
  Smartphone as EWalletIcon,
  Banknote as CashIcon,
} from 'lucide-vue-next';

const props = defineProps({
  accounts: { type: Array, default: () => [] },
  totalNetWorth: { type: Number, default: 0 },
  categoryTotals: { type: Object, default: () => ({ current: 0, savings: 0, other: 0 }) },
});

const viewMode = ref('stacked'); // 'stacked' or 'grid'
const expandedCardId = ref(null);
const showSummaryDetails = ref(false);

const showModal = ref(false);
const isEditing = ref(false);
const editingAccountId = ref(null);

const form = useForm({
  name: '',
  category: 'current',
  type: 'bank',
  currency: 'MYR',
  initial_balance: 0,
  color: '#FBBF24',
  icon: 'wallet',
});

const sortedAccounts = computed(() => {
  return [...props.accounts].sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0));
});

const categorizedSections = computed(() => {
  const current = props.accounts.filter(a => (a.category || 'current') === 'current');
  const savings = props.accounts.filter(a => ['savings', 'investment'].includes(a.category));
  const other = props.accounts.filter(a => !['current', 'savings', 'investment'].includes(a.category));

  return [
    {
      key: 'current',
      title: 'Current/ Spending Accounts',
      iconComponent: CreditCardIcon,
      items: current,
      subtotal: current.reduce((sum, a) => sum + parseFloat(a.balance || 0), 0),
    },
    {
      key: 'savings',
      title: 'Savings/ Investment Accounts',
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
    case 'current': return 'Current/ Spending';
    case 'savings':
    case 'investment': return 'Savings/ Investment';
    default: return 'E-Wallet / Credit Card';
  }
};

const getAccountTypeIcon = (type) => {
  switch (type) {
    case 'bank': return BankIcon;
    case 'e-wallet': return EWalletIcon;
    case 'cash': return CashIcon;
    default: return CreditCardIcon;
  }
};

const getAccountColor = (acc) => {
  if (acc.color && acc.color.trim() !== '') {
    return acc.color;
  }
  const nameLower = (acc.name || '').toLowerCase();
  if (nameLower.includes('maybank')) return '#FBBF24';
  if (nameLower.includes('gx')) return '#06B6D4';
  if (nameLower.includes('tng') || nameLower.includes('touch')) return '#F97316';
  if (nameLower.includes('uob')) return '#EC4899';
  if (nameLower.includes('boost') || nameLower.includes('payflex')) return '#2563EB';
  if (nameLower.includes('shopee') || nameLower.includes('rhb')) return '#8B5CF6';
  if (nameLower.includes('stashaway') || nameLower.includes('savings')) return '#10B981';

  const defaultPalette = ['#FBBF24', '#06B6D4', '#84CC16', '#2563EB', '#F97316', '#EC4899', '#10B981', '#8B5CF6'];
  return defaultPalette[(acc.id || 0) % defaultPalette.length];
};

const getContrastTextColor = (hexColor) => {
  if (!hexColor) return 'text-slate-950';
  let hex = hexColor.replace('#', '');
  if (hex.length === 3) {
    hex = hex.split('').map(c => c + c).join('');
  }
  if (hex.length !== 6) return 'text-slate-950';

  const r = parseInt(hex.substring(0, 2), 16);
  const g = parseInt(hex.substring(2, 4), 16);
  const b = parseInt(hex.substring(4, 6), 16);
  const yiq = (r * 299 + g * 587 + b * 114) / 1000;
  return yiq >= 135 ? 'text-slate-950' : 'text-white';
};

const toggleCardExpand = (accId) => {
  if (expandedCardId.value === accId) {
    expandedCardId.value = null;
  } else {
    expandedCardId.value = accId;
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
    form.color = getAccountColor(account);
  } else {
    isEditing.value = false;
    editingAccountId.value = null;
    form.name = '';
    form.category = 'current';
    form.type = 'bank';
    form.currency = 'MYR';
    form.initial_balance = 0;
    form.color = '#FBBF24';
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

  const newList = [...sortedAccounts.value];
  const itemA = list[index];
  const itemB = list[targetIndex];

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
