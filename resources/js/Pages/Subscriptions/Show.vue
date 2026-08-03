<template>
  <AppLayout>
    <div class="space-y-6 max-w-5xl mx-auto pb-12">
      <!-- Top Navigation & Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center space-x-2 text-xs font-black text-slate-950 mb-1">
            <Link href="/subscriptions" class="hover:underline uppercase">
              ← Subscriptions
            </Link>
            <span>/</span>
            <span class="text-slate-950 uppercase">Payment Sheet Grid</span>
          </div>

          <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-950 flex items-center gap-2.5">
            <TableIcon class="w-7 h-7 text-slate-950" />
            <span>{{ subscription.name }}</span>
          </h2>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="showAddMemberModal = true"
            class="px-4 py-2.5 rounded-2xl bg-white text-slate-950 border-2 border-slate-950 font-black text-xs shadow-[3px_3px_0px_#0f172a] flex items-center gap-1.5 hover:bg-slate-100 transition-all active:translate-x-0.5 active:translate-y-0.5"
          >
            <UserPlusIcon class="w-4 h-4 text-slate-950" />
            <span>Add Shared Member</span>
          </button>
          <Link
            href="/subscriptions"
            class="px-4 py-2.5 rounded-2xl bg-slate-950 text-amber-300 border-2 border-slate-950 font-black text-xs shadow-[3px_3px_0px_#0f172a] hover:bg-slate-900 transition-all block"
          >
            All Subscriptions
          </Link>
        </div>
      </div>

      <!-- Billing Cycle Rule Banner -->
      <div class="p-5 sm:p-6 rounded-3xl bg-amber-300 border-3 border-slate-950 shadow-[6px_6px_0px_#0f172a] flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-slate-950">
        <div class="space-y-1">
          <div class="flex items-center space-x-2 text-xs font-black uppercase tracking-wider text-slate-900">
            <CalendarCheckIcon class="w-4 h-4 text-slate-950" />
            <span>Billing Date & Cycle Rule</span>
          </div>
          <p class="text-sm sm:text-base font-black text-slate-950">
            {{ subscription.notes || `${subscription.billing_cycle_day}th of Every Month Starts a new cycle` }}
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-xs shrink-0">
          <button
            @click="openMasterExpenseModal"
            class="px-4 py-2.5 rounded-2xl bg-slate-950 text-white font-black text-xs border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a] flex items-center gap-2 hover:bg-slate-900 transition-all"
            title="Deduct full master subscription bill from your bank/wallet"
          >
            <CreditCardIcon class="w-4 h-4 text-amber-400" />
            <span>Log Master Bill (RM{{ formatCurrency(subscription.total_monthly_cost) }})</span>
          </button>
          <div class="px-3.5 py-2 rounded-2xl bg-white text-slate-950 border-2 border-slate-950 font-black shadow-[2px_2px_0px_#0f172a]">
            {{ members.length }} Members
          </div>
        </div>
      </div>

      <!-- Google Sheets Style Member Tabs Navigation -->
      <div class="p-4 sm:p-6 rounded-3xl bg-white border-3 border-slate-950 shadow-[4px_4px_0px_#0f172a] space-y-4">
        <div class="flex items-center space-x-2 border-b-2 border-slate-950/10 pb-3 overflow-x-auto">
          <span class="text-xs font-black text-slate-950 uppercase tracking-wider px-2 shrink-0">Member Tabs:</span>
          
          <button
            v-for="member in members"
            :key="member.id"
            @click="activeMemberId = member.id"
            :class="[
              activeMemberId === member.id
                ? 'bg-slate-950 text-white font-black border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a]'
                : 'bg-slate-100 text-slate-900 border-2 border-slate-950 font-bold hover:bg-slate-200',
              'px-4 py-2 rounded-2xl text-xs transition-all flex items-center space-x-2 whitespace-nowrap shrink-0'
            ]"
          >
            <UserIcon class="w-3.5 h-3.5" />
            <span>{{ member.name }} <template v-if="member.is_owner">(Owner)</template></span>
            <span
              :class="[
                member.is_owner 
                  ? (member.total_due_amount > 0 ? 'bg-amber-400 text-slate-950' : 'bg-slate-800 text-white')
                  : (member.total_due_amount > 0 ? 'bg-rose-500 text-white' : 'bg-emerald-400 text-slate-950'),
                'px-2 py-0.5 rounded-full text-[9px] font-black border border-slate-950'
              ]"
            >
              {{ member.is_owner ? (member.total_due_amount > 0 ? 'Self Share' : 'Self Paid') : (member.total_due_amount > 0 ? 'Due' : 'Clear') }}
            </span>
          </button>

          <button
            @click="showAddMemberModal = true"
            class="px-3.5 py-2 rounded-2xl text-xs font-black text-slate-950 bg-amber-300 hover:bg-amber-400 border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a] transition-all shrink-0 flex items-center gap-1"
          >
            <PlusIcon class="w-3.5 h-3.5" />
            <span>Member</span>
          </button>
        </div>

        <!-- Selected Member Details Header -->
        <div v-if="activeMember" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-2xl bg-slate-50 border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a]">
          <div class="flex items-center space-x-3.5">
            <div class="w-10 h-10 rounded-xl bg-slate-950 text-amber-300 flex items-center justify-center font-black text-base border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a]">
              {{ activeMember.name.charAt(0) }}
            </div>
            <div>
              <div class="flex items-center space-x-2">
                <span class="text-xs font-black text-slate-600 uppercase tracking-wider">Member:</span>
                <span class="text-base font-black text-slate-950">{{ activeMember.name }}</span>
              </div>
              <p class="text-xs text-slate-700 font-bold font-mono">Default Share: RM{{ formatCurrency(activeMember.default_share_amount) }}</p>
            </div>
          </div>

          <div class="flex items-center space-x-3">
            <div
              :class="[
                activeMember.total_due_amount > 0 ? 'bg-rose-400 text-slate-950 border-slate-950' : 'bg-emerald-400 text-slate-950 border-slate-950',
                'px-4 py-2 rounded-2xl border-2 shadow-[2px_2px_0px_#0f172a] text-xs font-black flex items-center gap-2'
              ]"
            >
              <span class="uppercase tracking-wider text-[10px]">Due Status:</span>
              <span class="text-sm font-black">{{ activeMember.due_status }}</span>
            </div>

            <button
              @click="confirmRemoveMember(activeMember)"
              class="w-9 h-9 rounded-xl bg-rose-500 border-2 border-slate-950 text-white flex items-center justify-center font-bold hover:bg-rose-600 transition-colors shadow-[2px_2px_0px_#0f172a]"
              title="Remove Member from Sheet"
            >
              <Trash2Icon class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Google Sheets Style Table Matrix -->
        <div v-if="activeMember" class="overflow-x-auto rounded-2xl border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a]">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-950 text-amber-300 font-black uppercase text-[10px] tracking-wider border-b-2 border-slate-950">
                <th class="py-3.5 px-4 w-40">Month / Cycle</th>
                <th class="py-3.5 px-4 w-32">Billing Date</th>
                <th class="py-3.5 px-4 w-28">Status</th>
                <th class="py-3.5 px-4">Payment Notes & Proof</th>
                <th class="py-3.5 px-4 w-32 text-right">Amount</th>
                <th class="py-3.5 px-4 w-28 text-center">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y-2 divide-slate-950/10 bg-white">
              <tr
                v-for="cycle in activeMember.cycles"
                :key="cycle.cycle_label"
                :class="[
                  cycle.status === 'paid' ? 'bg-emerald-50/50' : (cycle.status === 'pending' ? 'bg-amber-50/50' : 'bg-slate-50'),
                  'transition-colors font-bold'
                ]"
              >
                <!-- Month / Cycle -->
                <td class="py-3.5 px-4 font-black text-slate-950 whitespace-nowrap">
                  {{ cycle.cycle_label }}
                </td>

                <!-- Billing Date -->
                <td class="py-3.5 px-4 text-slate-700 font-mono whitespace-nowrap">
                  {{ cycle.due_date ? formatDate(cycle.due_date) : '-' }}
                </td>

                <!-- Status Badge -->
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span
                    :class="[
                      cycle.status === 'paid' ? 'bg-emerald-400 text-slate-950 border-slate-950' :
                      cycle.status === 'pending' ? 'bg-rose-400 text-slate-950 border-slate-950' :
                      'bg-slate-200 text-slate-800 border-slate-950',
                      'px-2.5 py-1 rounded-full text-[10px] font-black border uppercase tracking-wider inline-flex items-center gap-1'
                    ]"
                  >
                    <CheckCircleIcon v-if="cycle.status === 'paid'" class="w-3 h-3 text-slate-950" />
                    <ClockIcon v-else-if="cycle.status === 'pending'" class="w-3 h-3 text-slate-950" />
                    <span>{{ cycle.status === 'paid' ? 'Success' : (cycle.status === 'pending' ? 'Due' : 'Waived') }}</span>
                  </span>
                </td>

                <!-- Payment Notes & Proof -->
                <td class="py-3.5 px-4 text-slate-950 text-xs">
                  <div class="flex items-center space-x-2.5">
                    <button
                      v-if="cycle.proof_image_path"
                      type="button"
                      @click="openProofModal(cycle.proof_image_path)"
                      class="shrink-0 group focus:outline-none"
                      title="Click to view receipt image modal"
                    >
                      <img :src="cycle.proof_image_path" class="w-9 h-9 rounded-xl object-cover border-2 border-slate-950 shadow-[1px_1px_0px_#0f172a] group-hover:scale-105 transition-all" />
                    </button>
                    <span v-if="cycle.notes" class="font-bold text-slate-950" :title="cycle.notes">{{ cycle.notes }}</span>
                    <span v-else-if="!cycle.proof_image_path" class="text-slate-400 italic">No description</span>
                  </div>
                </td>

                <!-- Amount -->
                <td class="py-3.5 px-4 text-right font-black font-mono text-slate-950 whitespace-nowrap">
                  RM{{ formatCurrency(cycle.amount) }}
                </td>

                <!-- Action Button -->
                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                  <button
                    @click="openLogModal(cycle)"
                    :class="[
                      cycle.status === 'paid' ? 'bg-slate-100 text-slate-950 border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a]' : 'bg-slate-950 text-amber-300 border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a]',
                      'font-black text-[11px] px-3 py-1.5 rounded-xl transition-all'
                    ]"
                  >
                    {{ cycle.status === 'paid' ? 'Edit Log' : 'Log Payment' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Add Shared Member Modal -->
      <div v-if="showAddMemberModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl border-3 border-slate-950 shadow-[8px_8px_0px_#0f172a] max-w-md w-full p-6 space-y-4 animate-scale-up text-slate-950">
          <div class="flex items-center justify-between border-b-2 border-slate-950 pb-3">
            <h3 class="text-xl font-black text-slate-950 flex items-center gap-2">
              <UserPlusIcon class="w-5 h-5 text-slate-950" />
              <span>Add Member to Sheet</span>
            </h3>
            <button @click="showAddMemberModal = false" class="w-8 h-8 rounded-full bg-slate-100 border-2 border-slate-950 flex items-center justify-center text-slate-950 font-bold hover:bg-rose-100 transition-colors">
              <XIcon class="w-4 h-4" />
            </button>
          </div>

          <form @submit.prevent="submitAddMember" class="space-y-4 text-xs">
            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="block font-black text-slate-900 uppercase tracking-wider">Member Name</label>
                <button
                  type="button"
                  @click="fillMyselfName"
                  class="px-2.5 py-0.5 rounded-lg bg-amber-300 border border-slate-950 text-slate-950 font-black text-[11px] flex items-center gap-1 shadow-[1px_1px_0px_#0f172a]"
                >
                  <UserIcon class="w-3 h-3 text-slate-950" />
                  <span>+ Add Myself</span>
                </button>
              </div>
              <input
                v-model="memberForm.name"
                type="text"
                required
                placeholder="Member Name"
                class="w-full px-4 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-sm focus:outline-none"
              />
            </div>

            <div>
              <label class="block font-black text-slate-900 uppercase tracking-wider mb-1">Default Share Amount (MYR)</label>
              <input
                v-model.number="memberForm.default_share_amount"
                type="number"
                step="0.01"
                min="0"
                required
                placeholder="7.00"
                class="w-full px-4 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-black font-mono text-sm focus:outline-none"
              />
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t-2 border-slate-950">
              <button
                type="button"
                @click="showAddMemberModal = false"
                class="px-4 py-2.5 rounded-2xl text-slate-800 hover:bg-slate-100 text-xs font-bold"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="memberForm.processing"
                class="px-6 py-2.5 rounded-2xl bg-slate-950 text-amber-300 font-black text-xs border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a]"
              >
                Add Member
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Log / Edit Payment Sheet Entry Modal -->
      <div v-if="showLogModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl border-3 border-slate-950 shadow-[8px_8px_0px_#0f172a] max-w-lg w-full p-6 space-y-5 animate-scale-up text-slate-950">
          <div class="flex items-center justify-between border-b-2 border-slate-950 pb-3">
            <div>
              <span class="text-[10px] font-black uppercase text-slate-950 tracking-wider block">
                {{ activeMember?.name }} — {{ logForm.billing_cycle_label }}
              </span>
              <h3 class="text-xl font-black text-slate-950">Log Member Payment</h3>
            </div>
            <button @click="showLogModal = false" class="w-8 h-8 rounded-full bg-slate-100 border-2 border-slate-950 flex items-center justify-center text-slate-950 font-bold hover:bg-rose-100 transition-colors">
              <XIcon class="w-4 h-4" />
            </button>
          </div>

          <form @submit.prevent="submitLogPayment" class="space-y-4 text-xs">
            <div class="grid grid-cols-2 gap-3.5">
              <div>
                <label class="block font-black text-slate-900 uppercase tracking-wider mb-1">Status</label>
                <select
                  v-model="logForm.status"
                  class="w-full px-3.5 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-black text-xs focus:outline-none capitalize"
                >
                  <option value="paid">Success (Paid)</option>
                  <option value="pending">Due (Pending)</option>
                  <option value="waived">Waived</option>
                </select>
              </div>

              <div>
                <label class="block font-black text-slate-900 uppercase tracking-wider mb-1">Amount (MYR)</label>
                <input
                  v-model.number="logForm.amount"
                  type="number"
                  step="0.01"
                  min="0"
                  required
                  class="w-full px-3.5 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-black font-mono text-sm focus:outline-none"
                />
              </div>
            </div>

            <div>
              <label class="block font-black text-slate-900 uppercase tracking-wider mb-1">Payment Date</label>
              <input
                v-model="logForm.payment_date"
                type="date"
                class="w-full px-3.5 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-xs focus:outline-none"
              />
            </div>

            <div>
              <label class="block font-black text-slate-900 uppercase tracking-wider mb-1">Receipt Proof Screenshot</label>
              <div class="flex items-center space-x-2">
                <input
                  type="file"
                  accept="image/*"
                  @change="onProofFileChange"
                  class="block w-full text-xs text-slate-600 file:mr-2 file:py-2.5 file:px-3.5 file:rounded-xl file:border-2 file:border-slate-950 file:text-xs file:font-black file:bg-amber-300 file:text-slate-950 cursor-pointer"
                />
                <div v-if="proofPreview" class="relative shrink-0 flex items-center gap-1 bg-slate-100 p-1 rounded-xl border border-slate-950">
                  <img :src="proofPreview" class="w-9 h-9 rounded-lg object-cover border border-slate-950" />
                  <button
                    type="button"
                    @click="removeProofImage"
                    class="text-rose-600 hover:bg-rose-100 p-1 rounded-md transition-colors"
                    title="Remove Proof Image"
                  >
                    <Trash2Icon class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>

            <div>
              <label class="block font-black text-slate-900 uppercase tracking-wider mb-1">Payment Notes</label>
              <textarea
                v-model="logForm.notes"
                rows="2"
                placeholder="e.g. Received via Touch 'n Go eWallet / DuitNow transfer"
                class="w-full px-4 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-xs focus:outline-none"
              ></textarea>
            </div>

            <!-- Auto-post as Income Transaction Section -->
            <div class="p-3.5 rounded-2xl bg-amber-300 border-2 border-slate-950 shadow-[2px_2px_0px_#0f172a] space-y-3">
              <label class="flex items-center space-x-2.5 cursor-pointer">
                <input
                  v-model="logForm.auto_post_income"
                  type="checkbox"
                  class="w-4 h-4 text-slate-950 rounded border-2 border-slate-950 focus:ring-amber-400"
                />
                <span class="font-black text-slate-950 text-xs">
                  Auto-post as Income transaction to FinZ account ledger
                </span>
              </label>

              <div v-if="logForm.auto_post_income" class="space-y-2 pt-2 border-t-2 border-slate-950/20">
                <label class="block font-black text-slate-950 text-[11px] uppercase">Target Account (e.g. Touch 'n Go eWallet)</label>
                <select
                  v-model="logForm.account_id"
                  required
                  class="w-full px-3.5 py-3 rounded-2xl bg-white border-2 border-slate-950 text-slate-950 font-bold text-xs focus:outline-none"
                >
                  <option value="" disabled>Choose account...</option>
                  <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                    {{ acc.name }} (RM{{ formatCurrency(acc.balance) }})
                  </option>
                </select>
              </div>
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t-2 border-slate-950">
              <button
                type="button"
                @click="showLogModal = false"
                class="px-4 py-2.5 rounded-2xl text-slate-800 hover:bg-slate-100 text-xs font-bold"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="logForm.processing"
                class="px-6 py-2.5 rounded-2xl bg-slate-950 text-amber-300 font-black text-xs border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a]"
              >
                Save Payment Entry
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Log Master Subscription Expense Modal -->
      <div v-if="showMasterExpenseModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl border-3 border-slate-950 shadow-[8px_8px_0px_#0f172a] max-w-md w-full p-6 space-y-4 animate-scale-up text-slate-950">
          <div class="flex items-center justify-between border-b-2 border-slate-950 pb-3">
            <h3 class="text-xl font-black text-slate-950 flex items-center gap-2">
              <CreditCardIcon class="w-5 h-5 text-slate-950" />
              <span>Deduct Master Subscription Bill</span>
            </h3>
            <button @click="showMasterExpenseModal = false" class="w-8 h-8 rounded-full bg-slate-100 border-2 border-slate-950 flex items-center justify-center text-slate-950 font-bold hover:bg-rose-100 transition-colors">
              <XIcon class="w-4 h-4" />
            </button>
          </div>

          <form @submit.prevent="submitMasterExpense" class="space-y-4 text-xs">
            <div>
              <label class="block font-black text-slate-900 uppercase tracking-wider mb-1">Select Account Paid From</label>
              <select
                v-model="masterExpenseForm.account_id"
                required
                class="w-full px-3.5 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-xs focus:outline-none"
              >
                <option value="" disabled>Choose account...</option>
                <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                  {{ acc.name }} (RM{{ formatCurrency(acc.balance) }})
                </option>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-3.5">
              <div>
                <label class="block font-black text-slate-900 uppercase tracking-wider mb-1">Bill Amount (MYR)</label>
                <input
                  v-model.number="masterExpenseForm.amount"
                  type="number"
                  step="0.01"
                  min="0"
                  required
                  class="w-full px-3.5 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-black font-mono text-sm focus:outline-none"
                />
              </div>

              <div>
                <label class="block font-black text-slate-900 uppercase tracking-wider mb-1">Transaction Date</label>
                <input
                  v-model="masterExpenseForm.transaction_date"
                  type="date"
                  required
                  class="w-full px-3.5 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-xs focus:outline-none"
                />
              </div>
            </div>

            <div>
              <label class="block font-black text-slate-900 uppercase tracking-wider mb-1">Notes / Description</label>
              <input
                v-model="masterExpenseForm.notes"
                type="text"
                placeholder="e.g. YouTube Premium August Master Bill"
                class="w-full px-4 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-xs focus:outline-none"
              />
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t-2 border-slate-950">
              <button
                type="button"
                @click="showMasterExpenseModal = false"
                class="px-4 py-2.5 rounded-2xl text-slate-800 hover:bg-slate-100 text-xs font-bold"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="masterExpenseForm.processing"
                class="px-6 py-2.5 rounded-2xl bg-slate-950 text-amber-300 font-black text-xs border-2 border-slate-950 shadow-[3px_3px_0px_#0f172a]"
              >
                Post Master Expense
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Proof Image Viewer Modal -->
      <div
        v-if="showProofModal"
        class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
        @click.self="showProofModal = false"
      >
        <div class="bg-white rounded-3xl border-3 border-slate-950 shadow-[8px_8px_0px_#0f172a] max-w-xl w-full p-5 space-y-3 animate-scale-up text-slate-950">
          <div class="flex items-center justify-between border-b-2 border-slate-950 pb-2">
            <h4 class="font-black text-sm text-slate-950 flex items-center gap-2">
              <ImageIcon class="w-4 h-4 text-slate-950" />
              <span>Receipt Proof Screenshot</span>
            </h4>
            <button @click="showProofModal = false" class="w-8 h-8 rounded-full bg-slate-100 border-2 border-slate-950 flex items-center justify-center text-slate-950 font-bold hover:bg-rose-100 transition-colors">
              <XIcon class="w-4 h-4" />
            </button>
          </div>
          <div class="rounded-2xl overflow-hidden border-2 border-slate-950 bg-slate-50 flex items-center justify-center max-h-[75vh]">
            <img :src="selectedProofUrl" class="max-w-full max-h-[75vh] object-contain rounded-xl" />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, usePage, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  Table as TableIcon,
  User as UserIcon,
  UserPlus as UserPlusIcon,
  CalendarCheck as CalendarCheckIcon,
  CreditCard as CreditCardIcon,
  Image as ImageIcon,
  Plus as PlusIcon,
  CheckCircle as CheckCircleIcon,
  Clock as ClockIcon,
  Trash2 as Trash2Icon,
  X as XIcon,
} from 'lucide-vue-next';

const props = defineProps({
  subscription: {
    type: Object,
    required: true,
  },
  members: {
    type: Array,
    default: () => [],
  },
  accounts: {
    type: Array,
    default: () => [],
  },
});

const activeMemberId = ref(props.members.length > 0 ? props.members[0].id : null);

watch(() => props.members, (newMembers) => {
  if (newMembers.length > 0 && (!activeMemberId.value || !newMembers.some(m => m.id === activeMemberId.value))) {
    activeMemberId.value = newMembers[0].id;
  }
}, { immediate: true });

const activeMember = computed(() => {
  return props.members.find((m) => m.id === activeMemberId.value) || props.members[0] || null;
});

const showAddMemberModal = ref(false);
const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const memberForm = useForm({
  name: '',
  default_share_amount: 7.00,
});

const fillMyselfName = () => {
  memberForm.name = currentUser.value?.name || 'Me';
};

const submitAddMember = () => {
  memberForm.post(`/subscriptions/${props.subscription.id}/members`, {
    onSuccess: () => {
      showAddMemberModal.value = false;
      memberForm.reset();
    },
  });
};

const confirmRemoveMember = (member) => {
  if (confirm(`Remove member "${member.name}" from this subscription sheet?`)) {
    router.delete(`/subscription-members/${member.id}`, {
      onSuccess: () => {
        if (props.members.length > 0) {
          activeMemberId.value = props.members[0].id;
        }
      },
    });
  }
};

const showLogModal = ref(false);
const proofPreview = ref(null);

const logForm = useForm({
  subscription_member_id: null,
  billing_year: 2026,
  billing_month: 1,
  billing_cycle_label: '',
  status: 'paid',
  amount: 0,
  payment_date: new Date().toISOString().split('T')[0],
  reference_no: '',
  notes: '',
  proof_image: null,
  remove_proof: false,
  account_id: props.accounts.length > 0 ? props.accounts[0].id : null,
  auto_post_income: false,
});

const removeProofImage = () => {
  logForm.proof_image = null;
  logForm.remove_proof = true;
  proofPreview.value = null;
};

const showProofModal = ref(false);
const selectedProofUrl = ref(null);

const openProofModal = (url) => {
  if (url) {
    selectedProofUrl.value = url;
    showProofModal.value = true;
  }
};

const compressImage = (file, maxWidth = 1200, quality = 0.75) => {
  return new Promise((resolve) => {
    if (!file || !file.type.startsWith('image/') || file.size < 150 * 1024) {
      resolve(file);
      return;
    }

    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = (event) => {
      const img = new Image();
      img.src = event.target.result;
      img.onload = () => {
        let width = img.width;
        let height = img.height;

        if (width > maxWidth) {
          height = Math.round((height * maxWidth) / width);
          width = maxWidth;
        }

        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;

        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, width, height);

        canvas.toBlob(
          (blob) => {
            if (!blob) {
              resolve(file);
              return;
            }
            const compressedFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".webp", {
              type: 'image/webp',
              lastModified: Date.now(),
            });
            resolve(compressedFile);
          },
          'image/webp',
          quality
        );
      };
      img.onerror = () => resolve(file);
    };
    reader.onerror = () => resolve(file);
  });
};

const onProofFileChange = async (e) => {
  const file = e.target.files[0];
  if (file) {
    const compressed = await compressImage(file);
    logForm.proof_image = compressed;
    logForm.remove_proof = false;
    proofPreview.value = URL.createObjectURL(compressed);
  }
};

const openLogModal = (cycle) => {
  logForm.subscription_member_id = activeMember.value.id;
  logForm.billing_year = cycle.billing_year;
  logForm.billing_month = cycle.billing_month;
  logForm.billing_cycle_label = cycle.cycle_label;
  logForm.status = cycle.status === 'pending' ? 'paid' : cycle.status;
  logForm.amount = cycle.amount;
  logForm.payment_date = cycle.payment_date || new Date().toISOString().split('T')[0];
  logForm.reference_no = cycle.reference_no || '';
  logForm.notes = cycle.notes || '';
  logForm.proof_image = null;
  logForm.remove_proof = false;
  proofPreview.value = cycle.proof_image_path || null;
  logForm.auto_post_income = !!cycle.transaction_id;

  showLogModal.value = true;
};

const submitLogPayment = () => {
  logForm.post('/subscriptions-log-payment', {
    forceFormData: true,
    onSuccess: () => {
      showLogModal.value = false;
      proofPreview.value = null;
    },
  });
};

const showMasterExpenseModal = ref(false);
const masterExpenseForm = useForm({
  account_id: props.accounts.length > 0 ? props.accounts[0].id : '',
  amount: props.subscription.total_monthly_cost,
  transaction_date: new Date().toISOString().split('T')[0],
  notes: '',
});

const openMasterExpenseModal = () => {
  masterExpenseForm.amount = props.subscription.total_monthly_cost;
  masterExpenseForm.transaction_date = new Date().toISOString().split('T')[0];
  masterExpenseForm.notes = `${props.subscription.name} Master Bill`;
  showMasterExpenseModal.value = true;
};

const submitMasterExpense = () => {
  masterExpenseForm.post(`/subscriptions/${props.subscription.id}/log-expense`, {
    onSuccess: () => {
      showMasterExpenseModal.value = false;
    },
  });
};

const formatCurrency = (val) => {
  const num = parseFloat(val) || 0;
  return num.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  const parts = dateStr.split('-');
  if (parts.length === 3) {
    return `${parseInt(parts[2])}/${parseInt(parts[1])}`;
  }
  return dateStr;
};
</script>
