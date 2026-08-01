<template>
  <div class="min-h-screen bg-[#f8fafc] text-[#0f172a] flex flex-col font-sans selection:bg-[#0f172a] selection:text-white pb-24 md:pb-0 md:pl-64">
    <!-- Global Navigation Progress Indicator Bar -->
    <div v-if="isNavigating" class="fixed top-0 left-0 right-0 z-50 h-1 skeleton-shimmer"></div>

    <!-- Desktop Minimalist Sidebar Navigation -->
    <aside class="hidden md:flex flex-col fixed inset-y-0 left-0 w-64 bg-white border-r border-slate-200/80 z-30 p-6 justify-between">
      <div class="space-y-8">
        <!-- Logo Brand Header -->
        <Link href="/" class="flex items-center space-x-3 group">
          <div class="w-10 h-10 rounded-full bg-[#0f172a] text-white flex items-center justify-center font-black text-xl shadow-md group-hover:scale-105 transition-transform">
            Z
          </div>
          <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">
              FinZ
            </h1>
          </div>
        </Link>

        <!-- Minimalist Navigation Items -->
        <nav class="space-y-1.5">
          <Link
            v-for="item in navItems"
            :key="item.name"
            :href="item.href"
            :class="[
              $page.url === item.href || ($page.url.startsWith(item.href) && item.href !== '/')
                ? 'minimal-nav-active'
                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 font-medium',
              'flex items-center space-x-3.5 px-4 py-3 rounded-full text-sm transition-all'
            ]"
          >
            <component
              :is="item.icon"
              :class="[
                $page.url === item.href || ($page.url.startsWith(item.href) && item.href !== '/')
                  ? 'text-white'
                  : 'text-slate-500',
                'w-4 h-4 transition-colors'
              ]"
            />
            <span>{{ item.name }}</span>
          </Link>
        </nav>
      </div>

      <!-- User Badge & Logout -->
      <div class="space-y-4 pt-6 border-t border-slate-100">

        <div class="flex items-center justify-between p-2.5 rounded-full bg-slate-50 border border-slate-200">
          <div class="flex items-center space-x-3 min-w-0">
            <div
              :class="[
                user?.is_admin ? 'bg-amber-500 text-slate-950 font-black' : 'bg-slate-900 text-white font-bold',
                'w-8 h-8 rounded-full flex items-center justify-center text-xs shrink-0'
              ]"
            >
              {{ userInitial }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-bold text-slate-900 truncate">{{ user?.name || 'FinZ User' }}</p>
              <p :class="[user?.is_admin ? 'text-amber-600 font-bold' : 'text-slate-500 font-medium', 'text-[10px] truncate']">
                {{ user?.is_admin ? 'Superuser' : 'Regular User' }}
              </p>
            </div>
          </div>

          <button
            @click="logout"
            class="p-2 rounded-full text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors shrink-0"
            title="Log Out of FinZ"
          >
            <LogOutIcon class="w-4 h-4" />
          </button>
        </div>
      </div>
    </aside>

    <!-- Mobile Top Header -->
    <header class="md:hidden sticky top-0 z-20 bg-white/90 backdrop-blur-md border-b border-slate-200 px-4 py-3 flex items-center justify-between">
      <Link href="/" class="flex items-center space-x-2.5">
        <div class="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center font-black text-sm">
          Z
        </div>
        <span class="font-bold text-lg text-slate-900">FinZ</span>
      </Link>
      <div class="flex items-center space-x-1.5">
        <span
          :class="[
            user?.is_admin ? 'bg-amber-100 text-amber-900 border-amber-300' : 'bg-slate-100 text-slate-700 border-slate-200',
            'px-2.5 py-1 text-[10px] font-bold rounded-full border uppercase tracking-wider'
          ]"
        >
          {{ user?.is_admin ? 'Superuser' : 'MYR Ledger' }}
        </span>
        <Link
          href="/settings"
          class="p-1.5 rounded-full text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors"
          title="Settings"
        >
          <SettingsIcon class="w-4.5 h-4.5" />
        </Link>
        <button
          @click="logout"
          class="p-1.5 rounded-full text-slate-500 hover:text-rose-600 transition-colors"
          title="Log Out"
        >
          <LogOutIcon class="w-4 h-4" />
        </button>
      </div>
    </header>

    <!-- Flash Message Toast Notification -->
    <div v-if="flashSuccess" class="fixed top-4 right-4 z-50 max-w-md w-full px-4 animate-bounce-short">
      <div class="minimal-card p-4 rounded-2xl border border-emerald-500/40 shadow-xl flex items-center justify-between text-emerald-800 bg-emerald-50">
        <div class="flex items-center space-x-3">
          <CheckCircleIcon class="w-5 h-5 text-emerald-600 shrink-0" />
          <span class="text-sm font-medium">{{ flashSuccess }}</span>
        </div>
        <button @click="clearFlash" class="text-emerald-700 hover:text-emerald-900 p-1">
          <XIcon class="w-4 h-4" />
        </button>
      </div>
    </div>

    <div v-if="flashError" class="fixed top-4 right-4 z-50 max-w-md w-full px-4 animate-bounce-short">
      <div class="minimal-card p-4 rounded-2xl border border-rose-500/40 shadow-xl flex items-center justify-between text-rose-800 bg-rose-50">
        <div class="flex items-center space-x-3">
          <XCircleIcon class="w-5 h-5 text-rose-600 shrink-0" />
          <span class="text-sm font-medium">{{ flashError }}</span>
        </div>
        <button @click="clearFlash" class="text-rose-700 hover:text-rose-900 p-1">
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

    <!-- Mobile Bottom Navigation Bar -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200 px-2 py-2 flex items-center justify-around">
      <Link
        href="/"
        :class="[$page.url === '/' ? 'text-slate-900 font-bold' : 'text-slate-400', 'flex flex-col items-center py-1 px-2 text-[10px] tracking-tight']"
      >
        <HomeIcon class="w-5 h-5 mb-0.5" />
        <span>Overview</span>
      </Link>

      <Link
        href="/transactions"
        :class="[$page.url.startsWith('/transactions') ? 'text-slate-900 font-bold' : 'text-slate-400', 'flex flex-col items-center py-1 px-2 text-[10px] tracking-tight']"
      >
        <ReceiptTextIcon class="w-5 h-5 mb-0.5" />
        <span>Ledger</span>
      </Link>

      <!-- Center Action Button -->
      <Link
        href="/receipts"
        class="flex flex-col items-center -mt-6 group"
      >
        <div class="w-13 h-13 rounded-full bg-slate-900 text-white shadow-lg flex items-center justify-center group-active:scale-95 transition-transform">
          <CameraIcon class="w-6 h-6 text-white" />
        </div>
        <span class="text-[10px] font-bold text-slate-900 mt-1">SmartSplit</span>
      </Link>

      <Link
        href="/analytics"
        :class="[$page.url.startsWith('/analytics') ? 'text-slate-900 font-bold' : 'text-slate-400', 'flex flex-col items-center py-1 px-2 text-[10px] tracking-tight']"
      >
        <PieChartIcon class="w-5 h-5 mb-0.5" />
        <span>Analytics</span>
      </Link>

      <Link
        href="/accounts"
        :class="[$page.url.startsWith('/accounts') ? 'text-slate-900 font-bold' : 'text-slate-400', 'flex flex-col items-center py-1 px-2 text-[10px] tracking-tight']"
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
import SkeletonCard from '@/Components/SkeletonCard.vue';
import {
  LayoutDashboard as HomeIcon,
  ReceiptText as ReceiptTextIcon,
  Camera as CameraIcon,
  PieChart as PieChartIcon,
  Wallet as WalletIcon,
  Users as UsersIcon,
  Settings as SettingsIcon,
  ShieldCheck as ShieldCheckIcon,
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
    { name: 'Analytics & Reports', href: '/analytics', icon: PieChartIcon },
    { name: 'Accounts', href: '/accounts', icon: WalletIcon },
    { name: 'Settings', href: '/settings', icon: SettingsIcon },
  ];
});
</script>
