<template>
  <section id="faq" aria-labelledby="faq-heading" class="py-20 md:py-28">
    <div class="grid md:grid-cols-2 gap-12 md:gap-16 items-start">
      <!-- Kiri: Heading & ilustrasi -->
      <div class="md:sticky md:top-24">
        <p class="text-[var(--accent)] font-semibold tracking-widest text-xs uppercase mb-3">Pertanyaan Umum</p>
        <h2 id="faq-heading" class="font-display text-3xl md:text-4xl font-semibold text-[var(--ink)] leading-tight mb-4">
          Ada pertanyaan<span class="italic text-[var(--accent)]">?</span>
        </h2>
        <p class="text-[var(--copy)] text-sm leading-relaxed mb-8 max-w-xs">
          Temukan jawaban tepercaya seputar layanan kesehatan mental di Rumah Natasy.
        </p>

        <!-- Ilustrasi dekoratif -->
        <div class="relative w-48 h-48 md:w-56 md:h-56">
          <!-- Lingkaran besar -->
          <div class="absolute inset-0 rounded-full bg-[var(--accent)]/8 border border-[var(--accent)]/15" />
          <!-- Lingkaran tengah -->
          <div class="absolute inset-8 rounded-full bg-[var(--accent)]/10 border border-[var(--accent)]/20 flex items-center justify-center">
            <!-- Daun/tanaman abstrak -->
            <svg viewBox="0 0 80 80" class="w-20 h-20" aria-hidden="true" fill="none">
              <path d="M40 70 C40 70 15 55 15 35 C15 20 27 10 40 10 C53 10 65 20 65 35 C65 55 40 70 40 70Z" fill="var(--accent)" opacity="0.15" />
              <path d="M40 70 C40 70 15 55 15 35 C15 20 27 10 40 10" stroke="var(--accent)" stroke-width="1.5" opacity="0.4" />
              <path d="M40 10 L40 70" stroke="var(--accent)" stroke-width="1.5" opacity="0.3" />
              <path d="M25 30 Q40 25 45 45" stroke="var(--accent)" stroke-width="1" opacity="0.4" fill="none" />
              <path d="M55 30 Q40 25 35 45" stroke="var(--accent)" stroke-width="1" opacity="0.4" fill="none" />
              <circle cx="40" cy="68" r="4" fill="var(--accent)" opacity="0.5" />
            </svg>
          </div>
        </div>

        <!-- Kontak bantuan -->
        <div class="mt-8 p-4 rounded-2xl border border-[var(--line)] bg-[var(--background)] inline-flex items-start gap-3 max-w-xs">
          <div class="w-8 h-8 rounded-xl bg-[var(--accent)]/10 flex items-center justify-center shrink-0">
            <ChatBubbleLeftRightIcon class="w-4 h-4 text-[var(--accent)]" aria-hidden="true" />
          </div>
          <div>
            <p class="text-[var(--ink)] text-xs font-semibold">Masih bingung?</p>
            <p class="text-[var(--muted)] text-[10px] mt-0.5">Chat tim kami di WhatsApp, respons &lt;10 menit.</p>
            <a href="https://wa.me/628000000000" target="_blank" rel="noreferrer" class="text-[var(--accent)] text-[10px] font-semibold mt-1 inline-block hover:opacity-70">
              Chat sekarang →
            </a>
          </div>
        </div>
      </div>

      <!-- Kanan: Accordion FAQ menggunakan Reka UI -->
      <div>
        <AccordionRoot type="single" :collapsible="true" class="flex flex-col gap-0 divide-y divide-[var(--line)]">
          <AccordionItem
            v-for="(faq, idx) in faqs"
            :key="faq.question"
            :value="`item-${idx}`"
            class="py-0"
          >
            <AccordionHeader>
              <AccordionTrigger
                class="group flex w-full items-center justify-between gap-4 py-5 text-left text-sm font-semibold text-[var(--ink)] hover:text-[var(--accent)] transition-colors duration-200 cursor-pointer"
              >
                {{ faq.question }}
                <span
                  class="shrink-0 w-5 h-5 rounded-full border border-[var(--line)] flex items-center justify-center text-[var(--muted)] group-data-[state=open]:border-[var(--accent)] group-data-[state=open]:text-[var(--accent)] transition-all duration-200"
                  aria-hidden="true"
                >
                  <svg class="w-3 h-3 group-data-[state=open]:rotate-45 transition-transform duration-200" viewBox="0 0 12 12" fill="none">
                    <path d="M6 1v10M1 6h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                  </svg>
                </span>
              </AccordionTrigger>
            </AccordionHeader>
            <AccordionContent class="overflow-hidden data-[state=open]:animate-accordion-down data-[state=closed]:animate-accordion-up">
              <p class="pb-5 text-sm text-[var(--copy)] leading-relaxed">{{ faq.answer }}</p>
            </AccordionContent>
          </AccordionItem>
        </AccordionRoot>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { AccordionContent, AccordionHeader, AccordionItem, AccordionRoot, AccordionTrigger } from 'reka-ui'
import { ChatBubbleLeftRightIcon } from '@heroicons/vue/24/outline'

const faqs = [
  {
    question: 'Apakah semua psikolog Rumah Natasy berlisensi resmi?',
    answer: 'Ya, seluruh psikolog kami memegang Surat Izin Praktik (SIP) resmi dari Kementerian Kesehatan RI dan terdaftar aktif sebagai anggota Himpunan Psikologi Indonesia (HIMPsi). Semua melewati proses verifikasi latar belakang yang ketat sebelum bergabung.',
  },
  {
    question: 'Berapa biaya konsultasi di Rumah Natasy?',
    answer: 'Harga mulai dari Rp 100.000 per sesi, disesuaikan dengan kategori layanan dan psikolog yang Anda pilih. Tidak ada biaya tersembunyi—semua tarif ditampilkan transparan di profil masing-masing psikolog sebelum Anda melakukan booking.',
  },
  {
    question: 'Apakah data dan percakapan saya terjaga kerahasiaannya?',
    answer: 'Tentu. Seluruh data pribadi dan percakapan Anda dilindungi sepenuhnya sesuai UU Perlindungan Data Pribadi (UU PDP No. 27 Tahun 2022). Tidak ada rekaman sesi, tidak ada penyimpanan isi percakapan, dan tidak ada pihak ketiga yang dapat mengakses data Anda.',
  },
  {
    question: 'Bisa konsultasi lewat apa saja?',
    answer: 'Anda bisa memilih antara sesi video call atau chat teks—sesuai kenyamanan Anda. Semua dilakukan langsung di platform Rumah Natasy tanpa perlu mengunduh aplikasi tambahan.',
  },
  {
    question: 'Bagaimana jika saya perlu reschedule?',
    answer: 'Reschedule dapat dilakukan hingga 2 jam sebelum jadwal sesi tanpa dikenakan biaya apapun. Cukup masuk ke dashboard Anda, pilih sesi yang ingin dijadwal ulang, dan pilih waktu baru yang tersedia.',
  },
  {
    question: 'Apakah konsultasi online sama efektifnya dengan tatap muka?',
    answer: 'Berbagai penelitian klinis internasional menunjukkan bahwa terapi online memiliki efektivitas yang setara dengan sesi tatap muka untuk mayoritas kondisi, termasuk kecemasan, depresi ringan-sedang, dan manajemen stres. Psikolog kami terlatih khusus untuk sesi online.',
  },
]
</script>
