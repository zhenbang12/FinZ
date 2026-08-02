<template>
  <div class="min-h-screen bg-[#f8fafc] text-[#0f172a] flex items-center justify-center p-4 font-sans selection:bg-[#0f172a] selection:text-white">
    <div class="minimal-card max-w-md w-full p-6 sm:p-8 space-y-6 shadow-xl animate-scale-up">
      <!-- Header -->
      <div class="text-center space-y-2">
        <div class="w-12 h-12 rounded-full bg-[#0f172a] text-white flex items-center justify-center font-black text-2xl mx-auto shadow-md">
          Z
        </div>
        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">FinZ Login</h2>
        <p class="text-xs text-slate-500 font-medium">Enter your credentials or use Google Passkey / Biometrics to sign in.</p>
      </div>

      <!-- Passkey Sign In Button -->
      <div class="space-y-3">
        <button
          @click="loginWithPasskey"
          :disabled="passkeyAuthenticating"
          type="button"
          class="w-full py-3 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs flex items-center justify-center gap-2 shadow-md transition-all disabled:opacity-50"
        >
          <FingerprintIcon class="w-4 h-4 text-indigo-400" />
          <span>{{ passkeyAuthenticating ? 'Authenticating Passkey...' : 'Sign in with Passkey / Biometrics' }}</span>
        </button>

        <div v-if="passkeyError" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-700 text-center">
          {{ passkeyError }}
        </div>

        <div class="relative flex items-center justify-center my-4">
          <div class="border-t border-slate-200 w-full"></div>
          <span class="bg-[#f8fafc] px-3 text-[10px] uppercase font-bold text-slate-400 tracking-wider absolute">or login with password</span>
        </div>
      </div>

      <!-- Password Login Form -->
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
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Fingerprint as FingerprintIcon } from 'lucide-vue-next';

// Base64URL helpers for WebAuthn
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

    // Convert for native WebAuthn API
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

    const verifyRes = await fetch('/passkeys/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify(credentialData),
    });

    const verifyData = await verifyRes.json();
    if (verifyRes.ok && verifyData.redirect) {
      window.location.href = verifyData.redirect;
    } else {
      passkeyError.value = verifyData.message || 'Passkey authentication failed.';
    }
  } catch (err) {
    if (err.name !== 'NotAllowedError') {
      passkeyError.value = err.message || 'Passkey sign in failed or was cancelled.';
    }
  } finally {
    passkeyAuthenticating.value = false;
  }
};
</script>
