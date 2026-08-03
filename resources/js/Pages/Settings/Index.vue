<template>
  <AppLayout>
    <div class="space-y-6 max-w-5xl mx-auto pb-12">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-950 flex items-center gap-2">
            <span>Settings & Control Panel</span>
          </h2>
          <p class="text-xs sm:text-sm text-slate-600 mt-1 font-bold">
            Manage device security, active sessions, and system user management.
          </p>
        </div>

        <!-- Sub-Tab Selection (Preferences / Security / Users) -->
        <div class="flex items-center bg-white p-1 rounded-2xl border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a] overflow-x-auto">
          <button
            @click="activeTab = 'preferences'"
            :class="[
              activeTab === 'preferences' ? 'bg-slate-950 text-amber-300 font-black shadow-[2px_2px_0px_#0f172a]' : 'text-slate-950 font-bold',
              'px-3 sm:px-4 py-2 rounded-xl text-xs transition-all flex items-center gap-1.5 whitespace-nowrap shrink-0 border border-transparent'
            ]"
          >
            <SlidersIcon class="w-3.5 h-3.5" />
            <span>Preferences</span>
          </button>

          <button
            @click="activeTab = 'security'"
            :class="[
              activeTab === 'security' ? 'bg-slate-950 text-amber-300 font-black shadow-[2px_2px_0px_#0f172a]' : 'text-slate-950 font-bold',
              'px-3 sm:px-4 py-2 rounded-xl text-xs transition-all flex items-center gap-1.5 whitespace-nowrap shrink-0 border border-transparent'
            ]"
          >
            <ShieldCheckIcon class="w-3.5 h-3.5" />
            <span>Security</span>
          </button>

          <button
            v-if="user?.is_admin"
            @click="activeTab = 'users'"
            :class="[
              activeTab === 'users' ? 'bg-slate-950 text-amber-300 font-black shadow-[2px_2px_0px_#0f172a]' : 'text-slate-950 font-bold',
              'px-3 sm:px-4 py-2 rounded-xl text-xs transition-all flex items-center gap-1.5 whitespace-nowrap shrink-0 border border-transparent'
            ]"
          >
            <UsersIcon class="w-3.5 h-3.5" />
            <span>Users</span>
          </button>
        </div>
      </div>

      <!-- SECTION 0: General Preferences Tab -->
      <div v-if="activeTab === 'preferences'" class="space-y-6 max-w-2xl">
        <div class="p-6 rounded-3xl bg-white border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] space-y-5">
          <div class="flex items-center space-x-3 pb-3 border-b-2 border-slate-950/10">
            <div class="w-10 h-10 rounded-2xl bg-amber-300 text-slate-950 flex items-center justify-center font-black border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a]">
              <GlobeIcon class="w-5 h-5 text-slate-950" />
            </div>
            <div>
              <h3 class="font-black text-base text-slate-950">Regional & Display Preferences</h3>
              <p class="text-xs text-slate-600 font-bold">Configure your time zone and default accounting currency.</p>
            </div>
          </div>

          <form @submit.prevent="submitPreferences" class="space-y-4">
            <div>
              <label class="block text-xs font-black text-slate-950 uppercase tracking-wider mb-1">Application Time Zone</label>
              <select
                v-model="prefForm.timezone"
                class="w-full px-3.5 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 text-xs font-bold focus:outline-none"
              >
                <option value="Asia/Kuala_Lumpur">Asia/Kuala_Lumpur (GMT+8 - Malaysia / SG / HK)</option>
                <option value="Asia/Singapore">Asia/Singapore (GMT+8)</option>
                <option value="Asia/Hong_Kong">Asia/Hong_Kong (GMT+8)</option>
                <option value="Asia/Tokyo">Asia/Tokyo (GMT+9 - Japan)</option>
                <option value="Asia/Jakarta">Asia/Jakarta (GMT+7 - Indonesia)</option>
                <option value="Europe/London">Europe/London (GMT/BST)</option>
                <option value="America/New_York">America/New_York (EST/EDT)</option>
                <option value="UTC">UTC (Coordinated Universal Time)</option>
              </select>
              <p class="text-[11px] text-slate-600 mt-1 font-bold">System default timezone is set to GMT+8 (Asia/Kuala_Lumpur).</p>
            </div>

            <div>
              <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">Default Base Currency</label>
              <select
                v-model="prefForm.currency"
                class="w-full px-3.5 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 text-xs font-bold focus:outline-none"
              >
                <option value="MYR">MYR - Malaysian Ringgit (RM)</option>
                <option value="USD">USD - United States Dollar ($)</option>
                <option value="SGD">SGD - Singapore Dollar (S$)</option>
                <option value="EUR">EUR - Euro (€)</option>
                <option value="GBP">GBP - British Pound (£)</option>
                <option value="JPY">JPY - Japanese Yen (¥)</option>
                <option value="AUD">AUD - Australian Dollar (A$)</option>
              </select>
            </div>

            <div class="pt-2 flex justify-end">
              <button
                type="submit"
                :disabled="prefForm.processing"
                class="px-6 py-2.5 rounded-2xl bg-slate-950 text-amber-300 font-black text-xs border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a] disabled:opacity-50"
              >
                Save Preferences
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- SECTION 1: Device Security Tab -->
      <div v-if="activeTab === 'security'" class="space-y-6">
        <!-- Security Status Banner -->
        <div class="p-6 rounded-3xl bg-amber-300 border-3 border-slate-950 shadow-[6px_6px_0px_#0f172a] space-y-2 text-slate-950">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-2xl bg-slate-950 text-amber-300 flex items-center justify-center font-black shrink-0 border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a]">
              <LockIcon class="w-5 h-5 text-amber-400" />
            </div>
            <div>
              <h3 class="font-black text-base text-slate-950">Database Session Security Active</h3>
              <p class="text-xs text-slate-900 font-bold">
                All logged-in sessions are strictly bound to unique session tokens, user agents, and IP addresses.
              </p>
            </div>
          </div>
        </div>

        <!-- Passkeys & Biometrics Section -->
        <div class="p-6 rounded-3xl bg-white border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h3 class="text-base sm:text-lg font-black text-slate-950 flex items-center gap-2">
                <ShieldCheckIcon class="w-5 h-5 text-slate-950 shrink-0" />
                <span>Google Passkeys & Biometric Security</span>
              </h3>
              <p class="text-xs text-slate-600 mt-0.5 font-bold">Use your fingerprint, Face ID, or Google Password Manager passkeys for fast, passwordless login.</p>
            </div>

            <button
              @click="addPasskey"
              :disabled="passkeyRegistering"
              class="px-4 py-2.5 rounded-2xl bg-slate-950 text-amber-300 font-black text-xs border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a] flex items-center justify-center gap-2 shrink-0 hover:bg-slate-900"
            >
              <PlusIcon class="w-4 h-4 text-amber-400" />
              <span>{{ passkeyRegistering ? 'Prompting Device...' : 'Add Google Passkey' }}</span>
            </button>
          </div>

          <div v-if="passkeyError" class="p-3.5 rounded-2xl bg-rose-300 border-2 border-slate-950 text-slate-950 text-xs font-black">
            {{ passkeyError }}
          </div>

          <!-- Registered Passkeys List -->
          <div v-if="passkeys.length === 0" class="p-5 text-center border-2 border-dashed border-slate-950 rounded-2xl bg-slate-50">
            <p class="text-xs text-slate-600 font-bold">No passkeys registered yet. Click "Add Google Passkey" above to enable biometric login.</p>
          </div>

          <div v-else class="space-y-2.5">
            <div
              v-for="p in passkeys"
              :key="p.id"
              class="p-3.5 rounded-2xl border-2 border-slate-950 bg-slate-50 shadow-[2px_2px_0px_#0f172a] flex items-center justify-between"
            >
              <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-amber-300 text-slate-950 border border-slate-950 flex items-center justify-center font-bold">
                  <FingerprintIcon class="w-5 h-5" />
                </div>
                <div>
                  <h4 class="font-black text-xs sm:text-sm text-slate-950">{{ p.name }}</h4>
                  <span class="text-[10px] text-slate-600 font-bold block">Added {{ p.created_at_human }}</span>
                </div>
              </div>

              <button
                @click="deletePasskey(p)"
                class="w-8 h-8 rounded-xl bg-rose-500 border border-slate-950 text-white flex items-center justify-center font-bold hover:bg-rose-600 transition-colors"
                title="Remove Passkey"
              >
                <Trash2Icon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <!-- Data Backup & Recovery Section -->
        <div class="p-6 rounded-3xl bg-white border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h3 class="text-base sm:text-lg font-black text-slate-950 flex items-center gap-2">
                <DatabaseIcon class="w-5 h-5 text-slate-950 shrink-0" />
                <span>Data Backup & Disaster Recovery</span>
              </h3>
              <p class="text-xs text-slate-600 mt-0.5 font-bold">Export a full JSON copy of your accounts, transactions, receipts, and subscriptions, or restore from a previous backup file.</p>
            </div>

            <div class="flex items-center gap-2">
              <button
                @click="exportBackup"
                class="px-4 py-2.5 rounded-2xl bg-white text-slate-950 border-2 border-slate-950 font-black text-xs shadow-[3px_3px_0px_#0f172a] flex items-center justify-center gap-2 shrink-0 hover:bg-slate-100"
              >
                <DownloadIcon class="w-4 h-4 text-slate-950" />
                <span>Export Backup (.json)</span>
              </button>

              <button
                @click="triggerRestoreFile"
                :disabled="backupUploading"
                class="px-4 py-2.5 rounded-2xl bg-slate-950 text-amber-300 border-2 border-slate-950 font-black text-xs shadow-[3px_3px_0px_#0f172a] flex items-center justify-center gap-2 shrink-0 hover:bg-slate-900"
              >
                <UploadIcon class="w-4 h-4 text-amber-400" />
                <span>{{ backupUploading ? 'Restoring Data...' : 'Restore Backup' }}</span>
              </button>

              <input
                ref="restoreFileInput"
                type="file"
                accept=".json"
                class="hidden"
                @change="handleRestoreFile"
              />
            </div>
          </div>

          <div v-if="backupError" class="p-3.5 rounded-2xl bg-rose-300 border-2 border-slate-950 text-slate-950 text-xs font-black">
            {{ backupError }}
          </div>

          <div class="flex items-center gap-4 text-xs font-bold text-slate-800 pt-1">
            <span class="uppercase font-black text-slate-950">Restore Mode:</span>
            <label class="inline-flex items-center gap-1.5 cursor-pointer text-slate-950 font-black">
              <input type="radio" v-model="restoreMode" value="replace" class="text-slate-950 focus:ring-amber-400" />
              <span>Replace Existing Data</span>
            </label>
            <label class="inline-flex items-center gap-1.5 cursor-pointer text-slate-950 font-black">
              <input type="radio" v-model="restoreMode" value="merge" class="text-slate-950 focus:ring-amber-400" />
              <span>Merge with Existing Data</span>
            </label>
          </div>
        </div>

        <!-- Active Sessions List -->
        <div class="p-6 rounded-3xl bg-white border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-black text-slate-950 flex items-center gap-2">
              <LaptopIcon class="w-5 h-5 text-slate-950" />
              <span>Active Logged-In Sessions ({{ sessions.length }})</span>
            </h3>

            <button
              v-if="otherSessionsCount > 0"
              @click="logoutOtherDevices"
              class="px-3.5 py-1.5 rounded-2xl bg-rose-500 text-white font-black text-xs border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a] hover:bg-rose-600 flex items-center gap-1.5"
            >
              <LogOutIcon class="w-3.5 h-3.5 text-white" />
              <span>Log Out Other Devices ({{ otherSessionsCount }})</span>
            </button>
          </div>

          <div class="space-y-3">
            <div
              v-for="s in sessions"
              :key="s.id"
              :class="[
                s.is_current_device ? 'bg-amber-300 border-slate-950' : 'bg-slate-50 border-slate-950',
                'p-4 rounded-2xl border-2 shadow-[3px_3px_0px_#0f172a] flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-all'
              ]"
            >
              <div class="flex items-start space-x-3.5">
                <div
                  :class="[
                    s.is_current_device ? 'bg-slate-950 text-amber-300' : 'bg-slate-200 text-slate-950',
                    'w-10 h-10 rounded-2xl border-2 border-slate-950 flex items-center justify-center shrink-0 mt-0.5 shadow-[1px_1px_0px_#0f172a]'
                  ]"
                >
                  <SmartphoneIcon v-if="s.platform === 'iOS' || s.platform === 'Android'" class="w-5 h-5" />
                  <MonitorIcon v-else class="w-5 h-5" />
                </div>

                <div class="space-y-1">
                  <div class="flex items-center space-x-2">
                    <span class="font-black text-sm text-slate-950">
                      {{ s.device_name }}
                    </span>
                    <span
                      v-if="s.is_current_device"
                      class="px-2.5 py-0.5 rounded-full text-[9px] font-black bg-slate-950 text-white border border-slate-950 uppercase tracking-wider"
                    >
                      This Device
                    </span>
                  </div>

                  <div class="text-xs text-slate-950 font-extrabold flex flex-wrap items-center gap-2">
                    <span class="font-black">{{ s.browser }}</span>
                    <span>• {{ s.platform }}</span>
                    <span>• IP: {{ s.ip_address }}</span>
                  </div>

                  <span class="text-[11px] text-slate-800 block font-bold">
                    Last active {{ s.last_activity_human }} ({{ s.last_activity }})
                  </span>
                </div>
              </div>

              <div class="flex items-center justify-end shrink-0">
                <span v-if="s.is_current_device" class="text-xs font-black uppercase text-slate-950 tracking-wider">
                  Active Session
                </span>
                <button
                  v-else
                  @click="revokeSession(s)"
                  class="px-3.5 py-1.5 rounded-2xl bg-white hover:bg-rose-100 border-2 border-slate-950 text-rose-600 font-black text-xs shadow-[2px_2px_0px_#0f172a] transition-colors"
                >
                  Revoke Access
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SECTION 2: User Management Tab (Superuser Only) -->
      <div v-if="activeTab === 'users' && user?.is_admin" class="space-y-6">
        <div class="p-6 rounded-3xl bg-white border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-black text-slate-950 flex items-center gap-2">
              <UsersIcon class="w-5 h-5 text-slate-950" />
              <span>Registered System Users ({{ users.length }})</span>
            </h3>

            <button
              @click="openUserModal('create')"
              class="px-4 py-2 rounded-2xl bg-slate-950 text-amber-300 font-black text-xs border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a] flex items-center space-x-2"
            >
              <UserPlusIcon class="w-4 h-4 text-amber-400" />
              <span>Create New User</span>
            </button>
          </div>

          <div class="overflow-x-auto rounded-2xl border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a]">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-950 text-amber-300 font-black uppercase tracking-wider text-[10px]">
                  <th class="py-3.5 px-4">User</th>
                  <th class="py-3.5 px-4">Role</th>
                  <th class="py-3.5 px-4">Financial Accounts</th>
                  <th class="py-3.5 px-4">Transactions</th>
                  <th class="py-3.5 px-4">Joined Date</th>
                  <th class="py-3.5 px-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y-2 divide-slate-950/10 bg-white">
                <tr
                  v-for="u in users"
                  :key="u.id"
                  class="hover:bg-slate-50 font-bold"
                >
                  <td class="py-3.5 px-4">
                    <div class="flex items-center space-x-3">
                      <div
                        :class="[
                          u.is_admin ? 'bg-amber-300 text-slate-950 border-slate-950' : 'bg-slate-950 text-white border-slate-950',
                          'w-9 h-9 rounded-xl flex items-center justify-center font-black text-xs border-2 shrink-0'
                        ]"
                      >
                        {{ u.name.charAt(0).toUpperCase() }}
                      </div>
                      <div>
                        <div class="font-black text-slate-950 text-sm flex items-center gap-1.5">
                          {{ u.name }}
                          <span v-if="u.id === user?.id" class="text-[9px] bg-slate-950 text-amber-300 px-2 py-0.5 rounded-full font-black border border-slate-950">
                            You
                          </span>
                        </div>
                        <div class="text-slate-600 text-xs font-bold">{{ u.email }}</div>
                      </div>
                    </div>
                  </td>

                  <td class="py-3.5 px-4">
                    <span
                      :class="[
                        u.is_admin ? 'bg-amber-300 text-slate-950 border-slate-950 font-black' : 'bg-slate-100 text-slate-900 border-slate-950 font-bold',
                        'px-2.5 py-0.5 rounded-full text-[10px] uppercase border tracking-wider'
                      ]"
                    >
                      {{ u.is_admin ? 'Superuser' : 'User' }}
                    </span>
                  </td>

                  <td class="py-3.5 px-4 font-black text-slate-950">
                    {{ u.accounts_count }} Accounts
                  </td>

                  <td class="py-3.5 px-4 font-black text-slate-950">
                    {{ u.transactions_count }} Logged
                  </td>

                  <td class="py-3.5 px-4 text-slate-700 font-bold">
                    {{ formatDate(u.created_at) }}
                  </td>

                  <td class="py-3.5 px-4 text-right space-x-2">
                    <button
                      v-if="u.id !== user?.id"
                      @click="switchUserSession(u)"
                      class="px-3 py-1 rounded-2xl bg-amber-300 border border-slate-950 text-slate-950 font-black text-[11px] shadow-[2px_2px_0px_#0f172a]"
                      title="Switch session to this user"
                    >
                      Switch Session
                    </button>

                    <button
                      @click="openUserModal('edit', u)"
                      class="p-1.5 rounded-xl bg-slate-100 border border-slate-950 text-slate-950 hover:bg-amber-300 transition-colors"
                      title="Edit User"
                    >
                      <PencilIcon class="w-3.5 h-3.5" />
                    </button>

                    <button
                      v-if="u.id !== user?.id"
                      @click="deleteUser(u)"
                      class="p-1.5 rounded-xl bg-rose-500 border border-slate-950 text-white hover:bg-rose-600 transition-colors"
                      title="Delete User"
                    >
                      <Trash2Icon class="w-3.5 h-3.5" />
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
    <div v-if="showUserModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl border-3 border-slate-950 shadow-[8px_8px_0px_#0f172a] max-w-md w-full p-6 space-y-4 animate-scale-up text-slate-950">
        <div class="flex items-center justify-between border-b-2 border-slate-950 pb-3">
          <h3 class="text-xl font-black text-slate-950">
            {{ userModalMode === 'create' ? 'Create New System User' : 'Edit User Profile' }}
          </h3>
          <button @click="showUserModal = false" class="w-8 h-8 rounded-full bg-slate-100 border-2 border-slate-950 flex items-center justify-center text-slate-950 font-bold hover:bg-rose-100 transition-colors">
            <XIcon class="w-4 h-4" />
          </button>
        </div>

        <form @submit.prevent="submitUser" class="space-y-4">
          <div>
            <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">Full Name</label>
            <input
              v-model="userForm.name"
              type="text"
              required
              placeholder="e.g. John Doe"
              class="w-full px-4 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-sm focus:outline-none"
            />
          </div>

          <div>
            <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">Email Address</label>
            <input
              v-model="userForm.email"
              type="email"
              required
              placeholder="e.g. john@example.com"
              class="w-full px-4 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-sm focus:outline-none"
            />
          </div>

          <div>
            <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">
              {{ userModalMode === 'create' ? 'Password' : 'New Password' }}
            </label>
            <input
              v-model="userForm.password"
              type="password"
              :required="userModalMode === 'create'"
              placeholder="••••••••"
              class="w-full px-4 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-sm focus:outline-none"
            />
          </div>

          <div class="flex items-center space-x-2.5 pt-1">
            <input
              v-model="userForm.is_admin"
              type="checkbox"
              id="is_admin_check"
              class="rounded border-2 border-slate-950 text-slate-950 focus:ring-amber-400 w-4 h-4"
            />
            <label for="is_admin_check" class="text-xs font-black text-slate-950 cursor-pointer">
              Grant Superuser Admin Privileges
            </label>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-3 border-t-2 border-slate-950">
            <button
              type="button"
              @click="showUserModal = false"
              class="px-4 py-2.5 rounded-2xl text-slate-800 hover:bg-slate-100 text-xs font-bold"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="userForm.processing"
              class="px-6 py-2.5 rounded-2xl bg-slate-950 text-amber-300 font-black text-xs border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a]"
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
  Sliders as SlidersIcon,
  Globe as GlobeIcon,
  Plus as PlusIcon,
  Fingerprint as FingerprintIcon,
  Database as DatabaseIcon,
  Download as DownloadIcon,
  Upload as UploadIcon,
  X as XIcon,
} from 'lucide-vue-next';

function base64urlToBuffer(base64url) {
  const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
  const pad = base64.length % 4 === 0 ? '' : '='.repeat(4 - (base64.length % 4));
  const binary = atob(base64 + pad);
  const bytes = new Uint8Array(binary.length);
  for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
  return bytes.buffer;
}

function bufferToBase64url(buffer) {
  const bytes = new Uint8Array(buffer);
  let binary = '';
  for (const byte of bytes) binary += String.fromCharCode(byte);
  return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

const page = usePage();
const user = computed(() => page.props.auth?.user);

const props = defineProps({
  sessions: { type: Array, default: () => [] },
  users: { type: Array, default: () => [] },
  passkeys: { type: Array, default: () => [] },
  preferences: { type: Object, default: () => ({ timezone: 'Asia/Kuala_Lumpur', currency: 'MYR' }) },
});

const activeTab = ref('preferences');
const showUserModal = ref(false);
const userModalMode = ref('create');
const selectedUserId = ref(null);

const passkeyRegistering = ref(false);
const passkeyError = ref(null);

const backupUploading = ref(false);
const backupError = ref(null);
const restoreFileInput = ref(null);
const restoreMode = ref('replace');

const exportBackup = () => {
  window.location.href = '/settings/backup/export';
};

const triggerRestoreFile = () => {
  restoreFileInput.value?.click();
};

const handleRestoreFile = async (event) => {
  const file = event.target.files?.[0];
  if (!file) return;

  const confirmMsg = restoreMode.value === 'replace'
    ? `REPLACE your existing data with backup "${file.name}"? Current records will be overwritten.`
    : `MERGE backup "${file.name}" into your existing data?`;

  if (!confirm(confirmMsg)) {
    event.target.value = '';
    return;
  }

  backupUploading.value = true;
  backupError.value = null;

  const formData = new FormData();
  formData.append('backup_file', file);
  formData.append('mode', restoreMode.value);

  router.post('/settings/backup/restore', formData, {
    forceFormData: true,
    onSuccess: () => {
      backupUploading.value = false;
      event.target.value = '';
    },
    onError: (errors) => {
      backupUploading.value = false;
      backupError.value = errors.backup_file || errors.error || 'Failed to restore backup.';
      event.target.value = '';
    },
  });
};

const addPasskey = async () => {
  passkeyRegistering.value = true;
  passkeyError.value = null;

  try {
    const res = await fetch('/passkeys/register/options', {
      headers: { 'Accept': 'application/json' },
    });
    const options = await res.json();
    if (!res.ok) {
      passkeyError.value = options.message || 'Failed to fetch options.';
      return;
    }

    const publicKeyOptions = {
      challenge: base64urlToBuffer(options.challenge),
      rp: options.rp,
      user: {
        id: base64urlToBuffer(options.user.id),
        name: options.user.name,
        displayName: options.user.displayName,
      },
      pubKeyCredParams: options.pubKeyCredParams,
      timeout: options.timeout,
      attestation: options.attestation || 'none',
      authenticatorSelection: options.authenticatorSelection,
      excludeCredentials: (options.excludeCredentials || []).map(c => ({
        id: base64urlToBuffer(c.id),
        type: c.type,
      })),
    };

    const credential = await navigator.credentials.create({ publicKey: publicKeyOptions });

    const credentialData = {
      id: credential.id,
      rawId: bufferToBase64url(credential.rawId),
      type: credential.type,
      response: {
        clientDataJSON: bufferToBase64url(credential.response.clientDataJSON),
        attestationObject: bufferToBase64url(credential.response.attestationObject),
      },
    };

    router.post('/passkeys/register', credentialData, {
      preserveScroll: true,
      onFinish: () => {
        passkeyRegistering.value = false;
      },
      onError: (errors) => {
        passkeyError.value = errors.message || 'Failed to register passkey.';
      },
    });
  } catch (err) {
    if (err.name !== 'NotAllowedError') {
      passkeyError.value = err.message || 'Passkey creation failed or was cancelled.';
    }
    passkeyRegistering.value = false;
  }
};

const deletePasskey = (p) => {
  if (confirm(`Remove passkey "${p.name}"?`)) {
    router.delete(`/passkeys/${p.id}`);
  }
};

const prefForm = useForm({
  timezone: props.preferences?.timezone || 'Asia/Kuala_Lumpur',
  currency: props.preferences?.currency || 'MYR',
});

const submitPreferences = () => {
  prefForm.put('/settings/preferences');
};

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
