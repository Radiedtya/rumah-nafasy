<template>
  <section id="ahli" class="consultation-cta" aria-labelledby="consultation-heading">
    <div class="cta-inner">
      <p class="eyebrow">Ruang aman untuk bercerita</p>
      <h2 id="consultation-heading">Anda tidak harus melewati semuanya sendirian.</h2>
      <p class="description">
        Mulai konsultasi dengan psikolog Rumah Nafasy. Ceritakan kebutuhan Anda dan pilih waktu yang nyaman.
      </p>
      <div class="actions">
        <RouterLink to="/form" class="primary-action">Ayo Memulai 🤗 <span aria-hidden="true">→</span></RouterLink>
        <RouterLink to="/about" class="secondary-action">Kenali Rumah Nafasy</RouterLink>
      </div>
      <p class="note">Privat, fleksibel, dan tanpa pembayaran melalui aplikasi.</p>
    </div>
  </section>
</template>

<style scoped>
.consultation-cta { width: 100vw; margin: 0 0 0 calc(50% - 50vw); padding: 88px 24px; background: var(--surface); border-block: 1px solid var(--line); }
.cta-inner { max-width: 780px; margin: auto; text-align: center; }
.eyebrow { color: var(--accent); font-size: 12px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
h2 { max-width: 700px; margin: 14px auto; color: var(--ink); font-family: var(--font-display); font-size: clamp(2rem, 5vw, 3.5rem); line-height: 1.05; letter-spacing: -.055em; }
.description { max-width: 570px; margin: 0 auto; color: var(--muted); font-size: 16px; line-height: 1.7; }
.actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; margin-top: 28px; }
.primary-action, .secondary-action { display: inline-flex; min-height: 48px; align-items: center; justify-content: center; gap: 10px; padding: 0 22px; border-radius: 999px; text-decoration: none; font-size: 14px; font-weight: 700; }
.primary-action { background: var(--accent); color: white; }
.secondary-action { border: 1px solid var(--line); color: var(--text); }
.note { margin: 18px 0 0; color: var(--muted); font-size: 12px; }
@media (max-width: 640px) { .consultation-cta { padding: 64px 20px; } .description { font-size: 14px; } }
</style>
