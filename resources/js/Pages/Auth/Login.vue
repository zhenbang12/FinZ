<template>
  <div class="min-h-screen bg-[#f8fafc] text-[#0f172a] flex items-center justify-center p-4 font-sans selection:bg-[#0f172a] selection:text-white">
    <div class="bg-white rounded-3xl border-3 border-slate-950 shadow-[8px_8px_0px_#0f172a] max-w-md w-full p-6 sm:p-8 space-y-6 animate-scale-up text-slate-950">
      <!-- Header -->
      <div class="text-center space-y-2">
        <div class="w-14 h-14 rounded-2xl bg-amber-300 border-2 border-slate-950 text-slate-950 flex items-center justify-center font-black text-2xl mx-auto shadow-[3px_3px_0px_#0f172a]">
          Z
        </div>
        <h2 class="text-2xl sm:text-3xl font-black text-slate-950 tracking-tight font-mono">FinZ Sign In</h2>
        <p class="text-xs text-slate-600 font-bold">Enter your credentials or use Google Passkey / Biometrics to sign in.</p>
      </div>

      <!-- Passkey Sign In Button -->
      <div class="space-y-3">
        <button
          @click="loginWithPasskey"
          :disabled="passkeyAuthenticating"
          type="button"
          class="w-full py-3.5 px-4 rounded-2xl bg-slate-950 hover:bg-slate-900 text-white font-black text-xs flex items-center justify-center gap-2 border-2 border-slate-950 shadow-[4px_4px_0px_#0f172a] active:translate-x-0.5 active:translate-y-0.5 transition-all disabled:opacity-50"
        >
          <FingerprintIcon class="w-4 h-4 text-amber-400" />
          <span>{{ passkeyAuthenticating ? 'Authenticating Passkey...' : 'Sign in with Passkey / Biometrics' }}</span>
        </button>

        <div v-if="passkeyError" class="p-3.5 rounded-2xl bg-rose-300 border-2 border-slate-950 text-xs font-black text-slate-950 text-center">
          {{ passkeyError }}
        </div>

        <div class="relative flex items-center justify-center my-5">
          <div class="border-t-2 border-slate-950 w-full"></div>
          <span class="bg-amber-300 px-3 text-[10px] uppercase font-black text-slate-950 tracking-wider absolute border border-slate-950 rounded-full shadow-[1px_1px_0px_#0f172a]">or login with password</span>
        </div>
      </div>

      <!-- Password Login Form -->
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">Email Address</label>
          <input
            v-model="form.email"
            type="email"
            required
            autocomplete="email"
            placeholder="Enter your email"
            class="w-full px-4 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-sm focus:outline-none"
          />
          <span v-if="form.errors.email" class="text-xs font-black text-rose-600 mt-1 block">{{ form.errors.email }}</span>
        </div>

        <div>
          <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">Password</label>
          <input
            v-model="form.password"
            type="password"
            required
            autocomplete="current-password"
            placeholder="Enter your password"
            class="w-full px-4 py-3 rounded-2xl bg-slate-50 border-2 border-slate-950 text-slate-950 font-bold text-sm focus:outline-none"
          />
          <span v-if="form.errors.password" class="text-xs font-black text-rose-600 mt-1 block">{{ form.errors.password }}</span>
        </div>

        <div class="flex items-center justify-between text-xs pt-1">
          <label class="flex items-center space-x-2 cursor-pointer text-slate-950 font-black">
            <input
              v-model="form.remember"
              type="checkbox"
              class="rounded border-2 border-slate-950 text-slate-950 focus:ring-amber-400"
            />
            <span>Remember Me</span>
          </label>
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="w-full py-3.5 rounded-2xl bg-slate-950 text-amber-300 font-black text-xs border-2 border-slate-950 shadow-[4px_4px_0px_#0f172a] hover:bg-slate-900 active:translate-x-0.5 active:translate-y-0.5 transition-all disabled:opacity-50 mt-2"
        >
          Sign In
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Fingerprint as FingerprintIcon } from 'lucide-vue-next';

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

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const passkeyAuthenticating = ref(false);
const passkeyError = ref(null);

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
};

const loginWithPasskey = async () => {
  passkeyAuthenticating.value = true;
  passkeyError.value = null;

  try {
    const res = await fetch('/passkeys/login/options', {
      headers: { 'Accept': 'application/json' },
    });
    const options = await res.json();
    if (!res.ok) {
      passkeyError.value = options.message || 'Failed to fetch options.';
      return;
    }

    const publicKeyOptions = {
      challenge: base64urlToBuffer(options.challenge),
      timeout: options.timeout,
      rpId: options.rpId,
      userVerification: options.userVerification || 'preferred',
      allowCredentials: (options.allowCredentials || []).map(c => ({
        id: base64urlToBuffer(c.id),
        type: c.type,
      })),
    };

    const credential = await navigator.credentials.get({ publicKey: publicKeyOptions });

    const credentialData = {
      id: credential.id,
      rawId: bufferToBase64url(credential.rawId),
      type: credential.type,
      response: {
        clientDataJSON: bufferToBase64url(credential.response.clientDataJSON),
        authenticatorData: bufferToBase64url(credential.response.authenticatorData),
        signature: bufferToBase64url(credential.response.signature),
        userHandle: credential.response.userHandle ? bufferToBase64url(credential.response.userHandle) : null,
      },
    };

    router.post('/passkeys/login', credentialData, {
      preserveScroll: true,
      onFinish: () => {
        passkeyAuthenticating.value = false;
      },
      onError: (errors) => {
        passkeyError.value = errors.message || errors.id || 'Passkey authentication failed.';
      },
    });
  } catch (err) {
    if (err.name !== 'NotAllowedError') {
      passkeyError.value = err.message || 'Passkey sign in failed or was cancelled.';
    }
    passkeyAuthenticating.value = false;
  }
};
</script>
