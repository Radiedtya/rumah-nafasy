<template>
  <div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h2 class="font-display font-semibold text-neutral-900">Sesi Saya</h2>
        <p class="text-neutral-500 text-sm mt-0.5">Kelola jadwal dan riwayat konsultasi Anda</p>
      </div>
      <button type="button" class="inline-flex items-center px-4 py-2.5 rounded-xl bg-rose-500 text-white text-sm font-semibold hover:bg-rose-600 transition-colors">
        + Booking Baru
      </button>
    </div>

    <!-- Tabs placeholder -->
    <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden">
      <div class="flex border-b border-neutral-200">
        <button type="button" class="px-6 py-3 text-sm font-semibold text-rose-500 border-b-2 border-rose-500">Mendatang (2)</button>
        <button type="button" class="px-6 py-3 text-sm text-neutral-500 hover:text-neutral-800">Riwayat</button>
      </div>
      <div class="p-6 text-center text-neutral-400 text-sm py-16">
        <p class="text-4xl mb-3">📅</p>
        <p class="font-medium text-neutral-600">Placeholder — Sesi Mendatang</p>
        <p class="text-xs mt-1">Konten sesi akan ditampilkan di sini</p>
      </div>
    </div>
  </div>
</template>
