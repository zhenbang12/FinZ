<template>
  <AppLayout>
    <div class="space-y-6 max-w-4xl mx-auto">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 flex items-center gap-2">
            <ShieldCheckIcon class="w-7 h-7 text-emerald-600" />
            <span>Active Devices & Security</span>
          </h2>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 font-normal">
            Manage your logged-in devices, active web sessions, and IP bindings.
          </p>
        </div>

        <button
          v-if="otherSessionsCount > 0"
          @click="logoutOtherDevices"
          class="minimal-btn-secondary text-rose-600 hover:text-rose-700 hover:bg-rose-50 border-rose-200 px-4 py-2.5 text-xs font-bold flex items-center justify-center space-x-2"
        >
          <LogOutIcon class="w-4 h-4 text-rose-600" />
          <span>Log Out Other Devices ({{ otherSessionsCount }})</span>
        </button>
      </div>

      <!-- Security Status Card -->
      <div class="minimal-card p-6 bg-slate-900 text-white space-y-3">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold">
            <LockIcon class="w-5 h-5 text-emerald-400" />
          </div>
          <div>
            <h3 class="font-bold text-base text-white">Database Session Security Active</h3>
            <p class="text-xs text-slate-400">All sessions are strictly bound to unique session tokens, user agents, and IP addresses.</p>
          </div>
        </div>
      </div>

      <!-- Active Sessions List -->
      <div class="minimal-card p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
            <LaptopIcon class="w-5 h-5 text-slate-700" />
            <span>Active Logged-In Sessions ({{ sessions.length }})</span>
          </h3>
        </div>

        <div class="space-y-3">
          <div
            v-for="s in sessions"
            :key="s.id"
            :class="[
              s.is_current_device ? 'bg-indigo-50/50 border-indigo-200 ring-1 ring-indigo-500/20' : 'bg-slate-50 border-slate-200',
              'p-4 rounded-2xl border flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-all'
            ]"
          >
            <div class="flex items-start space-x-3.5">
              <div
                :class="[
                  s.is_current_device ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-700',
                  'w-10 h-10 rounded-full flex items-center justify-center shrink-0 mt-0.5'
                ]"
              >
                <SmartphoneIcon v-if="s.platform === 'iOS' || s.platform === 'Android'" class="w-5 h-5" />
                <MonitorIcon v-else class="w-5 h-5" />
              </div>

              <div class="space-y-0.5">
                <div class="flex items-center space-x-2">
                  <span class="font-bold text-sm text-slate-900">
                    {{ s.device_name }}
                  </span>
                  <span
                    v-if="s.is_current_device"
                    class="px-2.5 py-0.5 rounded-full text-[9px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300 uppercase tracking-wider"
                  >
                    This Device (Current)
                  </span>
                </div>

                <div class="text-xs text-slate-500 flex flex-wrap items-center gap-2 font-medium">
                  <span class="text-slate-700 font-semibold">{{ s.browser }}</span>
                  <span>• {{ s.platform }}</span>
                  <span class="text-slate-400">• IP: {{ s.ip_address }}</span>
                </div>

                <span class="text-[10px] text-slate-400 block font-medium">
                  Last active {{ s.last_activity_human }} ({{ s.last_activity }})
                </span>
              </div>
            </div>

            <div class="flex items-center justify-end shrink-0">
              <span v-if="s.is_current_device" class="text-xs font-bold text-slate-400 italic">
                Active Session
              </span>
              <button
                v-else
                @click="revokeSession(s)"
                class="px-3.5 py-1.5 rounded-full bg-white hover:bg-rose-50 border border-slate-300 hover:border-rose-300 text-rose-600 font-bold text-xs shadow-xs transition-colors"
              >
                Revoke Access
              </button>
            </div>
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
import {
  ShieldCheck as ShieldCheckIcon,
  Lock as LockIcon,
  Laptop as LaptopIcon,
  Smartphone as SmartphoneIcon,
  Monitor as MonitorIcon,
  LogOut as LogOutIcon,
} from 'lucide-vue-next';

const props = defineProps({
  sessions: { type: Array, default: () => [] },
});

const otherSessionsCount = computed(() => {
  return props.sessions.filter(s => !s.is_current_device).length;
});

const revokeSession = (session) => {
  if (confirm(`Revoke session for device "${session.device_name}" (${session.ip_address})?`)) {
    router.delete(`/security/sessions/${session.id}`);
  }
};

const logoutOtherDevices = () => {
  if (confirm('Are you sure you want to log out from all other active devices?')) {
    router.post('/security/sessions/logout-others');
  }
};
</script>
