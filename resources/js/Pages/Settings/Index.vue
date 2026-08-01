<template>
  <AppLayout>
    <div class="space-y-6 max-w-5xl mx-auto">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 flex items-center gap-2">
            <SettingsIcon class="w-7 h-7 text-slate-800" />
            <span>Settings</span>
          </h2>
          <p class="text-xs sm:text-sm text-slate-600 mt-1 font-medium">
            Manage device security, active sessions, and system user management.
          </p>
        </div>

        <!-- Sub-Tab Selection (Device Security / User Management) -->
        <div class="flex items-center bg-slate-200/70 p-1 rounded-full border border-slate-300">
          <button
            @click="activeTab = 'security'"
            :class="[
              activeTab === 'security' ? 'bg-slate-900 text-white font-bold shadow-xs' : 'text-slate-700 hover:text-slate-900 font-bold',
              'px-4 py-2 rounded-full text-xs transition-all flex items-center gap-1.5'
            ]"
          >
            <ShieldCheckIcon class="w-3.5 h-3.5" />
            <span>Device Security</span>
          </button>

          <button
            v-if="user?.is_admin"
            @click="activeTab = 'users'"
            :class="[
              activeTab === 'users' ? 'bg-slate-900 text-white font-bold shadow-xs' : 'text-slate-700 hover:text-slate-900 font-bold',
              'px-4 py-2 rounded-full text-xs transition-all flex items-center gap-1.5'
            ]"
          >
            <UsersIcon class="w-3.5 h-3.5" />
            <span>User Management</span>
          </button>
        </div>
      </div>

      <!-- SECTION 1: Device Security Tab -->
      <div v-if="activeTab === 'security'" class="space-y-6 animate-fade-in">
        <!-- Security Status Card with High Contrast Black/Dark Text -->
        <div class="minimal-card p-6 bg-slate-50 border border-slate-200 space-y-2">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold shrink-0 border border-emerald-300">
              <LockIcon class="w-5 h-5 text-emerald-800" />
            </div>
            <div>
              <h3 class="font-extrabold text-base text-slate-900">Database Session Security Active</h3>
              <p class="text-xs text-slate-700 font-medium">
                All logged-in sessions are strictly bound to unique session tokens, user agents, and IP addresses.
              </p>
            </div>
          </div>
        </div>

        <!-- Active Sessions List -->
        <div class="minimal-card p-6 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-extrabold text-slate-900 flex items-center gap-2">
              <LaptopIcon class="w-5 h-5 text-slate-800" />
              <span>Active Logged-In Sessions ({{ sessions.length }})</span>
            </h3>

            <button
              v-if="otherSessionsCount > 0"
              @click="logoutOtherDevices"
              class="minimal-btn-secondary text-rose-600 hover:text-rose-700 hover:bg-rose-50 border-rose-200 px-3.5 py-1.5 text-xs font-bold flex items-center gap-1.5 rounded-full"
            >
              <LogOutIcon class="w-3.5 h-3.5 text-rose-600" />
              <span>Log Out Other Devices ({{ otherSessionsCount }})</span>
            </button>
          </div>

          <div class="space-y-3">
            <div
              v-for="s in sessions"
              :key="s.id"
              :class="[
                s.is_current_device ? 'bg-indigo-50/70 border-indigo-300 ring-1 ring-indigo-500/20' : 'bg-slate-50 border-slate-200',
                'p-4 rounded-2xl border flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-all'
              ]"
            >
              <div class="flex items-start space-x-3.5">
                <div
                  :class="[
                    s.is_current_device ? 'bg-slate-900 text-white' : 'bg-slate-200 text-slate-800',
                    'w-10 h-10 rounded-full flex items-center justify-center shrink-0 mt-0.5'
                  ]"
                >
                  <SmartphoneIcon v-if="s.platform === 'iOS' || s.platform === 'Android'" class="w-5 h-5" />
                  <MonitorIcon v-else class="w-5 h-5" />
                </div>

                <div class="space-y-1">
                  <div class="flex items-center space-x-2">
                    <span class="font-extrabold text-sm text-slate-900">
                      {{ s.device_name }}
                    </span>
                    <span
                      v-if="s.is_current_device"
                      class="px-2.5 py-0.5 rounded-full text-[9px] font-extrabold bg-emerald-100 text-emerald-900 border border-emerald-300 uppercase tracking-wider"
                    >
                      This Device (Current)
                    </span>
                  </div>

                  <div class="text-xs text-slate-800 font-bold flex flex-wrap items-center gap-2">
                    <span class="text-slate-900 font-extrabold">{{ s.browser }}</span>
                    <span class="text-slate-700">• {{ s.platform }}</span>
                    <span class="text-slate-600">• IP: {{ s.ip_address }}</span>
                  </div>

                  <span class="text-[11px] text-slate-600 block font-semibold">
                    Last active {{ s.last_activity_human }} ({{ s.last_activity }})
                  </span>
                </div>
              </div>

              <div class="flex items-center justify-end shrink-0">
                <span v-if="s.is_current_device" class="text-xs font-extrabold text-slate-600 italic">
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

      <!-- SECTION 2: User Management Tab (Superuser Only) -->
      <div v-if="activeTab === 'users' && user?.is_admin" class="space-y-6 animate-fade-in">
        <div class="minimal-card p-6 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-extrabold text-slate-900 flex items-center gap-2">
              <UsersIcon class="w-5 h-5 text-slate-800" />
              <span>Registered System Users ({{ users.length }})</span>
            </h3>

            <button
              @click="openUserModal('create')"
              class="minimal-btn-primary flex items-center justify-center space-x-2 px-4 py-2 text-xs font-bold rounded-full"
            >
              <UserPlusIcon class="w-4 h-4 text-white" />
              <span>Create New User</span>
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="border-b border-slate-200 text-slate-700 font-extrabold uppercase tracking-wider">
                  <th class="py-3 px-4">User</th>
                  <th class="py-3 px-4">Role</th>
                  <th class="py-3 px-4">Financial Accounts</th>
                  <th class="py-3 px-4">Transactions</th>
                  <th class="py-3 px-4">Joined Date</th>
                  <th class="py-3 px-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr
                  v-for="u in users"
                  :key="u.id"
                  class="hover:bg-slate-50/80 transition-colors"
                >
                  <td class="py-3.5 px-4">
                    <div class="flex items-center space-x-3">
                      <div
                        :class="[
                          u.is_admin ? 'bg-amber-100 text-amber-900 border-amber-300' : 'bg-slate-900 text-white border-slate-900',
                          'w-9 h-9 rounded-full flex items-center justify-center font-extrabold text-xs border shrink-0'
                        ]"
                      >
                        {{ u.name.charAt(0).toUpperCase() }}
                      </div>
                      <div>
                        <div class="font-bold text-slate-900 text-sm flex items-center gap-1.5">
                          {{ u.name }}
                          <span v-if="u.id === user?.id" class="text-[9px] bg-slate-900 text-white px-2 py-0.5 rounded-full font-bold">
                            You
                          </span>
                        </div>
                        <div class="text-slate-600 text-xs font-medium">{{ u.email }}</div>
                      </div>
                    </div>
                  </td>

                  <td class="py-3.5 px-4">
                    <span
                      :class="[
                        u.is_admin ? 'bg-amber-100 text-amber-900 border-amber-300 font-extrabold' : 'bg-slate-100 text-slate-800 border-slate-200 font-bold',
                        'px-2.5 py-0.5 rounded-full text-[10px] uppercase border tracking-wider'
                      ]"
                    >
                      {{ u.is_admin ? 'Superuser' : 'User' }}
                    </span>
                  </td>

                  <td class="py-3.5 px-4 font-bold text-slate-800">
                    {{ u.accounts_count }} Accounts
                  </td>

                  <td class="py-3.5 px-4 font-bold text-slate-800">
                    {{ u.transactions_count }} Logged
                  </td>

                  <td class="py-3.5 px-4 text-slate-600 font-medium">
                    {{ formatDate(u.created_at) }}
                  </td>

                  <td class="py-3.5 px-4 text-right space-x-2">
                    <button
                      v-if="u.id !== user?.id"
                      @click="switchUserSession(u)"
                      class="px-3 py-1 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-900 font-bold text-[11px] transition-colors"
                      title="Switch session to this user"
                    >
                      Switch Session
                    </button>

                    <button
                      @click="openUserModal('edit', u)"
                      class="p-1.5 rounded-lg text-slate-400 hover:text-slate-900 hover:bg-slate-100 transition-colors"
                      title="Edit User"
                    >
                      <PencilIcon class="w-4 h-4" />
                    </button>

                    <button
                      v-if="u.id !== user?.id"
                      @click="deleteUser(u)"
                      class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                      title="Delete User"
                    >
                      <Trash2Icon class="w-4 h-4" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Create / Edit User Modal -->
    <div v-if="showUserModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="minimal-card max-w-md w-full p-6 space-y-4 animate-scale-up">
        <div class="flex items-center justify-between">
          <h3 class="text-xl font-bold text-slate-900">
            {{ userModalMode === 'create' ? 'Create New System User' : 'Edit User Profile' }}
          </h3>
          <button @click="showUserModal = false" class="text-slate-400 hover:text-slate-600 p-1">
            <XIcon class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitUser" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Full Name</label>
            <input
              v-model="userForm.name"
              type="text"
              required
              placeholder="e.g. John Doe"
              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-semibold focus:outline-none focus:border-slate-900"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Email Address</label>
            <input
              v-model="userForm.email"
              type="email"
              required
              placeholder="e.g. john@example.com"
              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm focus:outline-none focus:border-slate-900"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
              {{ userModalMode === 'create' ? 'Password' : 'New Password (Leave blank to keep existing)' }}
            </label>
            <input
              v-model="userForm.password"
              type="password"
              :required="userModalMode === 'create'"
              placeholder="••••••••"
              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-semibold focus:outline-none focus:border-slate-900"
            />
          </div>

          <div class="flex items-center space-x-2 pt-1">
            <input
              v-model="userForm.is_admin"
              type="checkbox"
              id="is_admin_check"
              class="rounded border-slate-300 text-slate-900 focus:ring-slate-900 w-4 h-4"
            />
            <label for="is_admin_check" class="text-xs font-bold text-slate-800 cursor-pointer">
              Grant Superuser Admin Privileges
            </label>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-3">
            <button
              type="button"
              @click="showUserModal = false"
              class="px-4 py-2 rounded-full text-slate-600 hover:text-slate-900 text-xs font-semibold"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="userForm.processing"
              class="minimal-btn-primary px-6 py-2.5 text-xs disabled:opacity-50"
            >
              {{ userModalMode === 'create' ? 'Create User' : 'Update User' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatDate } from '@/Utils/formatters';
import {
  Settings as SettingsIcon,
  ShieldCheck as ShieldCheckIcon,
  Lock as LockIcon,
  Laptop as LaptopIcon,
  Smartphone as SmartphoneIcon,
  Monitor as MonitorIcon,
  LogOut as LogOutIcon,
  Users as UsersIcon,
  UserPlus as UserPlusIcon,
  Pencil as PencilIcon,
  Trash2 as Trash2Icon,
  X as XIcon,
} from 'lucide-vue-next';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const props = defineProps({
  sessions: { type: Array, default: () => [] },
  users: { type: Array, default: () => [] },
});

const activeTab = ref('security');
const showUserModal = ref(false);
const userModalMode = ref('create');
const selectedUserId = ref(null);

const userForm = useForm({
  name: '',
  email: '',
  password: '',
  is_admin: false,
});

const otherSessionsCount = computed(() => {
  return props.sessions.filter(s => !s.is_current_device).length;
});

const revokeSession = (session) => {
  if (confirm(`Revoke session for device "${session.device_name}" (${session.ip_address})?`)) {
    router.delete(`/settings/sessions/${session.id}`);
  }
};

const logoutOtherDevices = () => {
  if (confirm('Are you sure you want to log out from all other active devices?')) {
    router.post('/settings/sessions/logout-others');
  }
};

const openUserModal = (mode, u = null) => {
  userModalMode.value = mode;
  if (mode === 'edit' && u) {
    selectedUserId.value = u.id;
    userForm.name = u.name;
    userForm.email = u.email;
    userForm.password = '';
    userForm.is_admin = !!u.is_admin;
  } else {
    selectedUserId.value = null;
    userForm.name = '';
    userForm.email = '';
    userForm.password = '';
    userForm.is_admin = false;
  }
  showUserModal.value = true;
};

const submitUser = () => {
  if (userModalMode.value === 'create') {
    userForm.post('/admin/users', {
      onSuccess: () => { showUserModal.value = false; },
    });
  } else {
    userForm.put(`/admin/users/${selectedUserId.value}`, {
      onSuccess: () => { showUserModal.value = false; },
    });
  }
};

const deleteUser = (u) => {
  if (confirm(`Are you sure you want to delete user "${u.name}" (${u.email})?`)) {
    router.delete(`/admin/users/${u.id}`);
  }
};

const switchUserSession = (u) => {
  if (confirm(`Switch session to user "${u.name}" (${u.email})?`)) {
    router.post(`/admin/users/${u.id}/switch`);
  }
};
</script>
