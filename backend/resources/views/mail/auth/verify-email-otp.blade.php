{{--
  Email OTP Verifikasi — gaya korporat minimal.
  Table-based + inline CSS (kompatibel Gmail, Outlook, Apple Mail).
  Logo di-embed sebagai CID attachment (Mailable) — selalu tampil.
--}}
@php($appName = config('app.name', 'Rumah Nafasy'))
@php($digits = str_split($otp))
@php($accent = '#0F766E')
@php($ink = '#111827')
@php($muted = '#6B7280')
@php($hair = '#E5E7EB')
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title>{{ $appName }} — Kode Verifikasi</title>
</head>
<body style="margin:0; padding:0; background:#F5F6F8; -webkit-text-size-adjust:100%;">

{{-- Preheader: teks kecil tersembunyi untuk preview inbox --}}
<div style="display:none; max-height:0; overflow:hidden; mso-hide:all;">
  Kode verifikasi {{ $appName }} Anda: {{ $otp }} (berlaku {{ $expiresMinutes }} menit).
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F5F6F8;">
  <tr>
    <td align="center" style="padding:40px 16px;">

      <table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0" style="max-width:560px; width:100%;">
        <tr>
          <td style="background:#FFFFFF; border:1px solid {{ $hair }}; border-radius:12px; overflow:hidden;">

            {{-- Aksen tipis di atas kartu --}}
            <div style="height:3px; background:{{ $accent }}; font-size:0; line-height:0;">&nbsp;</div>

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td align="center" style="padding:36px 48px 0 48px; font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">

                  {{-- Logo (embedded CID saat kirim nyata) + nama brand --}}
                  @if(! empty($logoPath) && isset($message))
                    <img src="{{ $message->embed($logoPath) }}" width="40" height="40" alt="{{ $appName }}"
                         style="display:inline-block; width:40px; height:40px; border-radius:10px; border:0;">
                  @endif
                  <div style="margin-top:10px; font-size:12px; font-weight:600; letter-spacing:2px; text-transform:uppercase; color:{{ $muted }};">
                    {{ $appName }}
                  </div>

                  {{-- Judul --}}
                  <h1 style="margin:22px 0 0 0; font-size:20px; line-height:28px; font-weight:600; color:{{ $ink }};">
                    Verifikasi Email Anda
                  </h1>

                  <p style="margin:10px 0 0 0; font-size:14px; line-height:22px; color:{{ $muted }};">
                    Halo <span style="color:{{ $ink }}; font-weight:600;">{{ $name }}</span>,<br>
                    Masukkan kode berikut untuk menyelesaikan pendaftaran
                    <span style="color:{{ $ink }}; font-weight:600;">{{ $maskedEmail }}</span>.
                  </p>

                  {{-- Kode OTP: 6 kotak terpisah --}}
                  <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:26px 0 0 0;">
                    <tr>
                      @foreach($digits as $d)
                        <td align="center" style="padding:0 4px;">
                          <div style="width:44px; height:56px; line-height:56px; background:#FFFFFF; border:1px solid {{ $hair }}; border-radius:8px; font-family:'SF Mono',SFMono-Regular,Consolas,'Liberation Mono',Menlo,monospace; font-size:24px; font-weight:600; color:{{ $ink }};">
                            {{ $d }}
                          </div>
                        </td>
                      @endforeach
                    </tr>
                  </table>

                  <p style="margin:18px 0 0 0; font-size:12px; color:{{ $muted }};">
                    Berlaku {{ $expiresMinutes }} menit &nbsp;·&nbsp; Jangan bagikan kode ini
                  </p>
                </td>
              </tr>

              {{-- Pembatas --}}
              <tr>
                <td style="padding:32px 48px 0 48px;">
                  <div style="height:1px; background:{{ $hair }}; font-size:0; line-height:0;">&nbsp;</div>
                </td>
              </tr>

              <tr>
                <td style="padding:20px 48px 40px 48px; font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
                  <p style="margin:0; font-size:13px; line-height:21px; color:{{ $muted }};">
                    Jika Anda tidak merasa mendaftar, abaikan email ini — tidak ada perubahan pada akun Anda.
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Footer --}}
        <tr>
          <td align="center" style="padding:20px 16px 0 16px; font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
            <p style="margin:0; font-size:11px; color:#9CA3AF;">
              &copy; {{ date('Y') }} {{ $appName }} · Email dikirim otomatis, mohon tidak dibalas.
            </p>
          </td>
        </tr>
      </table>

    </td>
  </tr>
</table>
</body>
</html>
