<template>
  <div class="min-h-screen bg-[#f8fafc] text-[#0f172a] flex items-center justify-center p-4 font-sans selection:bg-[#0f172a] selection:text-white">
    <div class="minimal-card max-w-md w-full p-6 sm:p-8 space-y-6 shadow-xl animate-scale-up">
      <!-- Header -->
      <div class="text-center space-y-2">
        <div class="w-12 h-12 rounded-full bg-[#0f172a] text-white flex items-center justify-center font-black text-2xl mx-auto shadow-md">
          Z
        </div>
        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Welcome to FinZ</h2>
        <p class="text-xs text-slate-500 font-medium">Log in to manage your financial accounts & SmartSplit receipts.</p>
      </div>

      <!-- Quick One-Click Logins for Demo Accounts -->
      <div v-if="users.length > 0" class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2.5">
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">
          ⚡ Quick Demo Login
        </span>
        <div class="flex flex-col gap-2">
          <button
            v-for="u in users"
            :key="u.id"
            type="button"
            @click="quickLogin(u)"
            class="flex items-center justify-between p-3 rounded-xl bg-white hover:bg-slate-100/80 border border-slate-200 text-left transition-all group"
          >
            <div class="flex items-center space-x-3">
              <div
                :class="[
                  u.is_admin ? 'bg-amber-100 text-amber-900 border-amber-300' : 'bg-slate-900 text-white',
                  'w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs shrink-0'
                ]"
              >
                {{ u.name.charAt(0).toUpperCase() }}
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">{{ u.name }}</p>
                <p class="text-[10px] text-slate-500 font-medium truncate">{{ u.email }}</p>
              </div>
            </div>
            <span
              :class="[
                u.is_admin ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-slate-100 text-slate-700 border-slate-200',
                'px-2.5 py-0.5 rounded-full text-[9px] font-bold border uppercase tracking-wider'
              ]"
            >
              {{ u.is_admin ? 'Superuser' : 'User' }}
            </span>
          </button>
        </div>
      </div>

      <div class="relative flex items-center justify-center">
        <div class="border-t border-slate-200 w-full"></div>
        <span class="bg-white px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest absolute">Or Manual Login</span>
      </div>

      <!-- Manual Login Form -->
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Email Address</label>
          <input
            v-model="form.email"
            type="email"
            required
            placeholder="admin@finz.app or demo@finz.app"
            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm focus:outline-none focus:border-slate-900"
          />
          <span v-if="form.errors.email" class="text-xs text-rose-600 mt-1 block">{{ form.errors.email }}</span>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Password</label>
          <input
            v-model="form.password"
            type="password"
            required
            placeholder="••••••••"
            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm focus:outline-none focus:border-slate-900"
          />
          <span v-if="form.errors.password" class="text-xs text-rose-600 mt-1 block">{{ form.errors.password }}</span>
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="minimal-btn-primary w-full py-3 text-sm font-bold shadow-md disabled:opacity-50 mt-2"
        >
          Sign In to FinZ
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  users: { type: Array, default: () => [] },
});

const form = useForm({
  email: 'admin@finz.app',
  password: 'adminpassword',
  remember: true,
});

const quickLogin = (u) => {
  form.email = u.email;
  form.password = u.is_admin ? 'adminpassword' : 'password';
  submit();
};

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
};
</script>
