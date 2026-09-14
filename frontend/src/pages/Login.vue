<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { ArrowRightIcon } from '@heroicons/vue/20/solid'

const router = useRouter()
const auth = useAuthStore()
const email = ref('')
const password = ref('')
const error = ref('')
const isLoading = ref(false)

async function handleLogin() {
  isLoading.value = true
  error.value = ''
  try {
    await auth.login(email.value, password.value)
    router.push('/dashboard')
  } catch (err: any) {
    error.value = err.message || 'Login gagal. Periksa kembali email dan password Anda.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="login-page">
    <section class="login-panel">
      <div class="login-form-wrap">
        <RouterLink to="/" class="login-brand" aria-label="Beranda Rumah Natasy">
          <img src="/icon.svg" alt="" aria-hidden="true" />
          <span>Rumah Natasy</span>
        </RouterLink>

        <div class="login-heading">
          <h1>Selamat datang kembali!</h1>
          <p>Masuk untuk melanjutkan perjalanan kesehatan mental Anda.</p>
        </div>

        <button type="button" class="google-button">
          <span class="google-mark" aria-hidden="true">G</span>
          Masuk dengan Google
        </button>

        <div class="login-divider"><span>ATAU</span></div>
        <div v-if="error" class="login-error">{{ error }}</div>

        <form class="login-form" @submit.prevent="handleLogin">
          <label>
            <span>Email address</span>
            <input v-model="email" type="email" required placeholder="nama@email.com" />
          </label>
          <label>
            <span>Password</span>
            <input v-model="password" type="password" required placeholder="Masukkan password Anda" />
          </label>
          <button type="submit" class="continue-button" :disabled="isLoading">
            <span>{{ isLoading ? 'Memverifikasi...' : 'Lanjutkan' }}</span>
            <ArrowRightIcon class="button-arrow" aria-hidden="true" />
          </button>
        </form>

        <p class="login-switch">
          Belum punya akun?
          <RouterLink to="/register">Daftar sekarang</RouterLink>
        </p>
        <p class="login-terms">
          Dengan masuk, Anda menyetujui<br />
          <a href="#">Kebijakan Privasi</a> dan <a href="#">Ketentuan Layanan</a>
        </p>
      </div>
    </section>

    <section class="login-visual" aria-label="Visual platform Rumah Natasy">
      <img src="https://placehold.co/1000x1200/073b77/ffffff.png?text=Rumah+Natasy+Visual" alt="Placeholder visual platform Rumah Natasy" />
    </section>
  </div>
</template>

<style scoped>
.login-page { height: 100svh; min-height: 0; display: grid; grid-template-columns: minmax(420px, 1fr) minmax(480px, 1fr); overflow: hidden; background: #fff; color: #0d0e12; font-family: var(--font-body); }
.login-panel { display: flex; justify-content: center; min-height: 0; overflow: hidden; padding: 30px 54px 26px; background: #fff; }
.login-form-wrap { width: min(100%, 358px); display: flex; flex-direction: column; }
.login-brand { display: inline-flex; align-items: center; gap: 7px; width: fit-content; color: #16171a; font-size: 21px; font-weight: 800; letter-spacing: -0.06em; text-decoration: none; }
.login-brand img { width: 28px; height: 28px; object-fit: contain; }
.login-heading { margin-top: 58px; }
.login-heading h1 { margin: 0; font-size: 32px; line-height: 1.05; letter-spacing: -0.06em; font-weight: 700; }
.login-heading p { max-width: 320px; margin: 8px 0 0; color: #737b8c; font-size: 14px; line-height: 1.3; }
.google-button, .continue-button { width: 100%; height: 45px; border-radius: 4px; font: inherit; font-size: 14px; font-weight: 600; cursor: pointer; }
.google-button { display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 22px; border: 1px solid #d9dde5; background: #fff; color: #16171a; }
.google-mark { color: #4285f4; font-size: 19px; font-weight: 800; }
.login-divider { display: flex; align-items: center; gap: 17px; margin: 19px 0 18px; color: #747c8b; font-size: 12px; }
.login-divider::before, .login-divider::after { height: 1px; flex: 1; background: #d7dbe2; content: ''; }
.login-form { display: grid; gap: 9px; }
.login-form label { display: grid; gap: 7px; color: #8992a2; font-size: 13px; }
.login-form input { width: 100%; height: 45px; padding: 0 13px; border: 1px solid #d9dde5; border-radius: 4px; outline: none; background: #fff; color: #16171a; font: inherit; font-size: 14px; }
.login-form input::placeholder { color: #aeb5c2; }
.login-form input:focus { border-color: #1688ed; box-shadow: 0 0 0 3px rgb(22 136 237 / 12%); }
.continue-button { display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 14px; border: 0; background: #1688ed; color: #fff; transition: background 160ms ease; }
.continue-button:hover { background: #0876d8; }
.continue-button:disabled { cursor: wait; opacity: 0.65; }
.button-arrow { width: 16px; }
.login-error { margin-bottom: 14px; padding: 10px 12px; border-radius: 4px; background: #fff0f0; color: #c03939; font-size: 12px; }
.login-switch { margin: 12px 0 0; color: #17191e; font-size: 12px; }
.login-switch a, .login-terms a { color: #1688ed; font-weight: 600; text-decoration: none; }
.login-terms { margin-top: auto; padding-top: 28px; color: #7c8492; font-size: 11px; line-height: 1.35; }
.login-terms a { color: #252a32; font-weight: 700; }
.login-visual { min-height: 100svh; overflow: hidden; background: #073b77; }
.login-visual img { display: block; width: 100%; height: 100%; object-fit: cover; }
@media (max-width: 800px) {
  .login-page { display: block; }
  .login-panel { height: 100svh; padding: 24px; }
  .login-heading { margin-top: 48px; }
  .login-visual { display: none; }
  .login-terms { padding-top: 24px; }
}

@media (max-height: 700px) and (min-width: 801px) {
  .login-panel { padding-top: 22px; padding-bottom: 18px; }
  .login-heading { margin-top: 34px; }
  .login-heading h1 { font-size: 29px; }
  .google-button { margin-top: 16px; }
  .login-divider { margin-top: 14px; margin-bottom: 13px; }
  .login-terms { padding-top: 16px; }
}
</style>
