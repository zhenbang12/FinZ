<template>
  <div class="min-h-screen bg-[#f8fafc] text-[#0f172a] flex flex-col font-sans selection:bg-[#0f172a] selection:text-white pb-24 md:pb-0 md:pl-64">
    <!-- Global Navigation Progress Indicator Bar -->
    <div v-if="isNavigating" class="fixed top-0 left-0 right-0 z-50 h-1.5 skeleton-shimmer bg-amber-400"></div>

    <!-- Desktop Neo-Brutalist Sidebar Navigation -->
    <aside class="hidden md:flex flex-col fixed inset-y-0 left-0 w-64 bg-white border-r-3 border-slate-950 z-30 p-6 justify-between">
      <div class="space-y-8">
        <!-- Logo Brand Header -->
        <Link href="/" class="flex items-center space-x-3 group">
          <div class="w-10 h-10 rounded-2xl bg-slate-950 text-amber-300 flex items-center justify-center font-black text-xl border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a] group-hover:scale-105 transition-transform">
            Z
          </div>
          <div>
            <h1 class="text-2xl font-black tracking-tight text-slate-950 font-mono">
              FinZ
            </h1>
          </div>
        </Link>

        <!-- Neo-Brutalist Navigation Items -->
        <nav class="space-y-2">
          <Link
            v-for="item in navItems"
            :key="item.name"
            :href="item.href"
            :class="[
              $page.url === item.href || ($page.url.startsWith(item.href) && item.href !== '/')
                ? 'bg-slate-950 text-white font-extrabold border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a]'
                : 'text-slate-800 hover:text-slate-950 hover:bg-slate-100 font-bold border-2 border-transparent',
              'flex items-center space-x-3 px-4 py-2.5 rounded-2xl text-xs transition-all'
            ]"
          >
            <component
              :is="item.icon"
              :class="[
                $page.url === item.href || ($page.url.startsWith(item.href) && item.href !== '/')
                  ? 'text-amber-400'
                  : 'text-slate-700',
                'w-4 h-4 transition-colors'
              ]"
            />
            <span>{{ item.name }}</span>
          </Link>
        </nav>
      </div>

      <!-- User Badge & Logout -->
      <div class="space-y-4 pt-6 border-t-2 border-slate-950">
        <div class="flex items-center justify-between p-2.5 rounded-2xl bg-amber-300 border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a]">
          <div class="flex items-center space-x-3 min-w-0">
            <div
              :class="[
                user?.is_admin ? 'bg-slate-950 text-amber-300 font-black' : 'bg-slate-950 text-white font-bold',
                'w-8 h-8 rounded-xl flex items-center justify-center text-xs shrink-0 border border-slate-950'
              ]"
            >
              {{ userInitial }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-black text-slate-950 truncate">{{ user?.name || 'FinZ User' }}</p>
              <p :class="[user?.is_admin ? 'text-slate-950 font-black' : 'text-slate-800 font-bold', 'text-[10px] uppercase truncate tracking-wider']">
                {{ user?.is_admin ? 'Superuser' : 'Regular User' }}
              </p>
            </div>
          </div>

          <button
            @click="logout"
            class="p-1.5 rounded-xl bg-slate-950 text-white hover:bg-rose-600 transition-colors shrink-0 border border-slate-950"
            title="Log Out of FinZ"
          >
            <LogOutIcon class="w-4 h-4" />
          </button>
        </div>
      </div>
    </aside>

    <!-- Mobile Top Header -->
    <header class="md:hidden sticky top-0 z-20 bg-white border-b-3 border-slate-950 px-4 py-3 flex items-center justify-between shadow-[0_2px_0px_#0f172a]">
      <Link href="/" class="flex items-center space-x-2.5">
        <div class="w-8 h-8 rounded-xl bg-slate-950 text-amber-300 flex items-center justify-center font-black text-sm border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a]">
          Z
        </div>
        <span class="font-black text-lg text-slate-950 font-mono">FinZ</span>
      </Link>

      <div class="flex items-center space-x-2">
        <span
          :class="[
            user?.is_admin ? 'bg-amber-300 text-slate-950 border-2 border-slate-950' : 'bg-slate-100 text-slate-900 border-2 border-slate-950',
            'px-2.5 py-1 text-[10px] font-black rounded-xl uppercase tracking-wider shadow-[2px_2px_0px_#0f172a]'
          ]"
        >
          {{ user?.is_admin ? 'Superuser' : 'MYR Ledger' }}
        </span>

        <Link
          href="/settings"
          class="p-1.5 rounded-xl bg-slate-100 border-2 border-slate-950 text-slate-950 hover:bg-amber-300 transition-colors"
          title="Settings"
        >
          <SettingsIcon class="w-4 h-4" />
        </Link>

        <button
          @click="logout"
          class="p-1.5 rounded-xl bg-rose-500 border-2 border-slate-950 text-white transition-colors"
          title="Log Out"
        >
          <LogOutIcon class="w-4 h-4" />
        </button>
      </div>
    </header>

    <!-- Flash Message Toast Notification -->
    <div v-if="flashSuccess" class="fixed top-4 right-4 z-50 max-w-md w-full px-4 animate-bounce-short">
      <div class="bg-emerald-300 rounded-2xl border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] p-4 flex items-center justify-between text-slate-950">
        <div class="flex items-center space-x-3 font-extrabold text-xs">
          <CheckCircleIcon class="w-5 h-5 text-slate-950 shrink-0" />
          <span>{{ flashSuccess }}</span>
        </div>
        <button @click="clearFlash" class="text-slate-950 hover:bg-emerald-400 p-1 rounded-lg">
          <XIcon class="w-4 h-4" />
        </button>
      </div>
    </div>

    <div v-if="flashError" class="fixed top-4 right-4 z-50 max-w-md w-full px-4 animate-bounce-short">
      <div class="bg-rose-300 rounded-2xl border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] p-4 flex items-center justify-between text-slate-950">
        <div class="flex items-center space-x-3 font-extrabold text-xs">
          <XCircleIcon class="w-5 h-5 text-slate-950 shrink-0" />
          <span>{{ flashError }}</span>
        </div>
        <button @click="clearFlash" class="text-slate-950 hover:bg-rose-400 p-1 rounded-lg">
          <XIcon class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Main Content Container -->
    <main class="flex-1 p-4 sm:p-6 md:p-8 max-w-7xl w-full mx-auto">
      <Transition name="page-fade" mode="out-in">
        <div :key="$page.url">
          <slot />
        </div>
      </Transition>
    </main>

    <!-- Mobile Neo-Brutalist Bottom Navigation Bar -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white border-t-3 border-slate-950 px-2 py-2 flex items-center justify-around shadow-[0_-4px_0px_#0f172a]">
      <Link
        href="/"
        :class="[$page.url === '/' ? 'text-slate-950 font-black' : 'text-slate-500 font-bold', 'flex flex-col items-center py-1 px-2 text-[10px] tracking-tight']"
      >
        <HomeIcon class="w-5 h-5 mb-0.5" />
        <span>Overview</span>
      </Link>

      <Link
        href="/transactions"
        :class="[$page.url.startsWith('/transactions') ? 'text-slate-950 font-black' : 'text-slate-500 font-bold', 'flex flex-col items-center py-1 px-2 text-[10px] tracking-tight']"
      >
        <ReceiptTextIcon class="w-5 h-5 mb-0.5" />
        <span>Ledger</span>
      </Link>

      <!-- Center Action Button -->
      <Link
        href="/receipts"
        class="flex flex-col items-center -mt-6 group"
      >
        <div class="w-13 h-13 rounded-2xl bg-amber-400 text-slate-950 border-3 border-slate-950 shadow-[3px_3px_0px_#0f172a] flex items-center justify-center group-active:translate-y-0.5 transition-all">
          <CameraIcon class="w-6 h-6 text-slate-950" />
        </div>
        <span class="text-[10px] font-black text-slate-950 mt-1 uppercase">SmartSplit</span>
      </Link>

      <Link
        href="/analytics"
        :class="[$page.url.startsWith('/analytics') ? 'text-slate-950 font-black' : 'text-slate-500 font-bold', 'flex flex-col items-center py-1 px-2 text-[10px] tracking-tight']"
      >
        <PieChartIcon class="w-5 h-5 mb-0.5" />
        <span>Analytics</span>
      </Link>

      <Link
        href="/accounts"
        :class="[$page.url.startsWith('/accounts') ? 'text-slate-950 font-black' : 'text-slate-500 font-bold', 'flex flex-col items-center py-1 px-2 text-[10px] tracking-tight']"
      >
        <WalletIcon class="w-5 h-5 mb-0.5" />
        <span>Accounts</span>
      </Link>
    </nav>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import {
  LayoutDashboard as HomeIcon,
  ReceiptText as ReceiptTextIcon,
  Camera as CameraIcon,
  PieChart as PieChartIcon,
  Wallet as WalletIcon,
  Repeat as RepeatIcon,
  Settings as SettingsIcon,
  LogOut as LogOutIcon,
  CheckCircle as CheckCircleIcon,
  XCircle as XCircleIcon,
  X as XIcon,
} from 'lucide-vue-next';

const page = usePage();

const user = computed(() => page.props.auth?.user);
const userInitial = computed(() => (user.value?.name ? user.value.name.charAt(0).toUpperCase() : 'F'));
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const isNavigating = ref(false);
let unbindStart = null;
let unbindFinish = null;

onMounted(() => {
  unbindStart = router.on('start', () => {
    isNavigating.value = true;
  });
  unbindFinish = router.on('finish', () => {
    isNavigating.value = false;
  });
});

onUnmounted(() => {
  if (unbindStart) unbindStart();
  if (unbindFinish) unbindFinish();
});

const clearFlash = () => {
  page.props.flash.success = null;
  page.props.flash.error = null;
};

const logout = () => {
  router.post('/logout');
};

const navItems = computed(() => {
  return [
    { name: 'Overview', href: '/', icon: HomeIcon },
    { name: 'Financial Ledger', href: '/transactions', icon: ReceiptTextIcon },
    { name: 'SmartSplit', href: '/receipts', icon: CameraIcon },
    { name: 'Subscriptions', href: '/subscriptions', icon: RepeatIcon },
    { name: 'Analytics & Reports', href: '/analytics', icon: PieChartIcon },
    { name: 'Accounts', href: '/accounts', icon: WalletIcon },
    { name: 'Settings', href: '/settings', icon: SettingsIcon },
  ];
});
</script>
