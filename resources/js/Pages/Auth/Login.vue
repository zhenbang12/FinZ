<template>
  <div class="min-h-screen bg-[#f8fafc] text-[#0f172a] flex items-center justify-center p-4 font-sans selection:bg-[#0f172a] selection:text-white">
    <div class="minimal-card max-w-md w-full p-6 sm:p-8 space-y-6 shadow-xl animate-scale-up">
      <!-- Header -->
      <div class="text-center space-y-2">
        <div class="w-12 h-12 rounded-full bg-[#0f172a] text-white flex items-center justify-center font-black text-2xl mx-auto shadow-md">
          Z
        </div>
        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">FinZ Login</h2>
        <p class="text-xs text-slate-500 font-medium">Enter your credentials to access your financial ledger.</p>
      </div>

      <!-- Clean Login Form -->
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Email Address</label>
          <input
            v-model="form.email"
            type="email"
            required
            autocomplete="email"
            placeholder="Enter your email"
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
            autocomplete="current-password"
            placeholder="Enter your password"
            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm focus:outline-none focus:border-slate-900"
          />
          <span v-if="form.errors.password" class="text-xs text-rose-600 mt-1 block">{{ form.errors.password }}</span>
        </div>

        <div class="flex items-center justify-between text-xs pt-1">
          <label class="flex items-center space-x-2 cursor-pointer text-slate-600">
            <input
              v-model="form.remember"
              type="checkbox"
              class="rounded border-slate-300 text-slate-900 focus:ring-slate-900"
            />
            <span class="font-medium">Remember Me</span>
          </label>
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="minimal-btn-primary w-full py-3 text-sm font-bold shadow-md disabled:opacity-50 mt-2"
        >
          Sign In
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
};
</script>
