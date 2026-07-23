<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 flex items-center gap-2">
            <span>User Account Management</span>
            <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-300 text-[10px] uppercase font-bold tracking-wider">
              Superuser Panel
            </span>
          </h2>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 font-normal">
            Create, edit, manage, and delete user accounts across the system.
          </p>
        </div>

        <button
          @click="openModal('create')"
          class="minimal-btn-primary flex items-center justify-center space-x-2 px-5 py-2.5 text-xs font-semibold"
        >
          <UserPlusIcon class="w-4 h-4 text-white" />
          <span>Create New User</span>
        </button>
      </div>

      <!-- Users Table Card -->
      <div class="minimal-card p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
            <UsersIcon class="w-5 h-5 text-slate-700" />
            <span>Registered Users ({{ users.length }})</span>
          </h3>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="border-b border-slate-200 text-slate-400 font-bold uppercase tracking-wider">
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
                        <span v-if="u.id === currentUser?.id" class="text-[9px] bg-slate-900 text-white px-2 py-0.5 rounded-full font-bold">
                          You
                        </span>
                      </div>
                      <div class="text-slate-500 text-xs font-medium">{{ u.email }}</div>
                    </div>
                  </div>
                </td>

                <td class="py-3.5 px-4">
                  <span
                    :class="[
                      u.is_admin ? 'bg-amber-100 text-amber-800 border-amber-300 font-extrabold' : 'bg-slate-100 text-slate-700 border-slate-200 font-semibold',
                      'px-2.5 py-0.5 rounded-full text-[10px] uppercase border tracking-wider'
                    ]"
                  >
                    {{ u.is_admin ? '⚡ Superuser' : 'User' }}
                  </span>
                </td>

                <td class="py-3.5 px-4 font-bold text-slate-700">
                  {{ u.accounts_count }} Accounts
                </td>

                <td class="py-3.5 px-4 font-bold text-slate-700">
                  {{ u.transactions_count }} Logged
                </td>

                <td class="py-3.5 px-4 text-slate-500 font-medium">
                  {{ formatDate(u.created_at) }}
                </td>

                <td class="py-3.5 px-4 text-right">
                  <div class="flex items-center justify-end space-x-2">
                    <button
                      @click="switchUser(u)"
                      v-if="u.id !== currentUser?.id"
                      class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] transition-colors border border-slate-200"
                      title="Switch active session to this user"
                    >
                      Switch Session
                    </button>
                    <button
                      @click="openModal('edit', u)"
                      class="p-1.5 rounded-full text-slate-400 hover:text-slate-900 hover:bg-slate-100 transition-colors"
                      title="Edit User Account"
                    >
                      <PencilIcon class="w-4 h-4" />
                    </button>
                    <button
                      @click="deleteUser(u)"
                      :disabled="u.id === currentUser?.id"
                      :class="[
                        u.id === currentUser?.id ? 'opacity-30 cursor-not-allowed text-slate-300' : 'text-slate-400 hover:text-rose-600 hover:bg-rose-50',
                        'p-1.5 rounded-full transition-colors'
                      ]"
                      title="Delete User Account"
                    >
                      <Trash2Icon class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Create / Edit User Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="minimal-card max-w-lg w-full p-6 space-y-5 animate-scale-up">
        <div class="flex items-center justify-between">
          <h3 class="text-xl font-bold text-slate-900">
            {{ isEditing ? 'Edit User Account' : 'Create New User Account' }}
          </h3>
          <button @click="closeModal" class="text-slate-400 hover:text-slate-600 p-1">
            <XIcon class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="saveUser" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
              Full Name
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              placeholder="e.g. John Doe"
              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm focus:outline-none focus:border-slate-900"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
              Email Address
            </label>
            <input
              v-model="form.email"
              type="email"
              required
              placeholder="e.g. john@example.com"
              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm focus:outline-none focus:border-slate-900"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
              Password {{ isEditing ? '(Leave blank to keep existing password)' : '' }}
            </label>
            <input
              v-model="form.password"
              type="password"
              :required="!isEditing"
              placeholder="••••••••"
              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm focus:outline-none focus:border-slate-900"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
              Account Role / Type
            </label>
            <select
              v-model="form.is_admin"
              required
              class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm focus:outline-none focus:border-slate-900"
            >
              <option :value="false">Regular User</option>
              <option :value="true">⚡ Superuser (Admin)</option>
            </select>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-3">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 rounded-full text-slate-600 hover:text-slate-900 text-xs font-semibold"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="form.processing"
              class="minimal-btn-primary px-6 py-2.5 text-xs disabled:opacity-50"
            >
              {{ isEditing ? 'Update User' : 'Create User' }}
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
  UserPlus as UserPlusIcon,
  Users as UsersIcon,
  Pencil as PencilIcon,
  Trash2 as Trash2Icon,
  X as XIcon,
} from 'lucide-vue-next';

const props = defineProps({
  users: { type: Array, default: () => [] },
  allUsersList: { type: Array, default: () => [] },
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const showModal = ref(false);
const isEditing = ref(false);
const editingUserId = ref(null);

const form = useForm({
  name: '',
  email: '',
  password: '',
  is_admin: false,
});

const openModal = (mode, u = null) => {
  if (mode === 'edit' && u) {
    isEditing.value = true;
    editingUserId.value = u.id;
    form.name = u.name;
    form.email = u.email;
    form.password = '';
    form.is_admin = !!u.is_admin;
  } else {
    isEditing.value = false;
    editingUserId.value = null;
    form.name = '';
    form.email = '';
    form.password = '';
    form.is_admin = false;
  }
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
};

const saveUser = () => {
  if (isEditing.value && editingUserId.value) {
    form.put(`/admin/users/${editingUserId.value}`, {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post('/admin/users', {
      onSuccess: () => closeModal(),
    });
  }
};

const deleteUser = (u) => {
  if (confirm(`Are you sure you want to delete user account "${u.name}" (${u.email})? All associated data will be removed.`)) {
    router.delete(`/admin/users/${u.id}`);
  }
};

const switchUser = (u) => {
  router.post(`/admin/users/${u.id}/switch`);
};
</script>
