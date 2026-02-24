{{-- resources/views/Unit/ArsipPBJ.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Arsip PBJ - SIAPABAJA</title>

  {{-- Font Nunito --}}
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">

  {{-- Bootstrap Icons --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  {{-- CSRF for fetch --}}
  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- CSS dashboard unit --}}
  <link rel="stylesheet" href="{{ asset('css/Unit.css') }}">
</head>

<body class="dash-body page-arsip">
@php
  $unitName = auth()->user()->name ?? "Unit Kerja";

  if (!isset($arsips) || !($arsips instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)) {
    throw new \RuntimeException('Variable $arsips (paginator) tidak dikirim dari controller. Pastikan UnitController@arsipIndex mengirim compact("arsips").');
  }

  // ✅ ambil query filter (server-side)
  $selectedStatus = request()->query('status', 'Semua');
  $selectedYear   = request()->query('tahun', 'Semua');
  $selectedQ      = request()->query('q', '');

  // ✅ sort nilai (server-side)
  $initialSortNilai = strtolower((string) request()->query('sort_nilai', ''));
  if (!in_array($initialSortNilai, ['asc', 'desc'], true)) $initialSortNilai = '';

  // Bentuk rows sesuai struktur yang dipakai UI
  $rows = collect($arsips->items())->map(function($item) use ($unitName){
    $r = is_array($item) ? $item : (array) $item;

    $rawE = $r["dokumen_tidak_dipersyaratkan"] ?? ($r["kolom_e"] ?? ($r["doc_note"] ?? null));
    $docNote = null;

    if (is_array($rawE) && count($rawE) > 0) {
      $docNote = implode(', ', array_map(fn($x) => is_string($x) ? $x : json_encode($x), $rawE));
    } else {
      $eVal = is_string($rawE) ? trim($rawE) : $rawE;

      if ($eVal === true || $eVal === 1 || $eVal === "1" || (is_string($eVal) && in_array(strtolower($eVal), ["ya","iya","true","yes"], true))) {
        $docNote = "Dokumen pada Kolom E bersifat opsional (tidak dipersyaratkan).";
      } elseif (is_string($eVal) && $eVal !== "") {
        $docNote = $eVal;
      }
    }

    return [
      "id" => $r["id"] ?? null,
      "tahun" => (string)($r["tahun"] ?? ""),
      "unit" => $r["unit"] ?? $unitName,
      "pekerjaan" => $r["pekerjaan"] ?? ($r["judul"] ?? "-"),
      "jenis_pbj" => $r["jenis_pbj"] ?? "Pengadaan Pekerjaan Konstruksi",
      "metode_pbj" => $r["metode_pbj"] ?? ($r["metode"] ?? "-"),
      "nilai_kontrak" => $r["nilai_kontrak"] ?? ($r["kontrak"] ?? ($r["nilai"] ?? "-")),
      "status_arsip" => $r["status_arsip"] ?? "-",
      "status_pekerjaan" => $r["status_pekerjaan"] ?? ($r["status"] ?? "-"),

      "id_rup" => $r["id_rup"] ?? ($r["idrup"] ?? null),
      "nama_rekanan" => $r["nama_rekanan"] ?? ($r["rekanan"] ?? null),
      "jenis_pengadaan" => $r["jenis_pengadaan"] ?? ($r["jenis"] ?? null),
      "pagu_anggaran" => $r["pagu_anggaran"] ?? ($r["pagu"] ?? null),
      "hps" => $r["hps"] ?? null,

      "dokumen" => $r["dokumen"] ?? [],
      "doc_note" => $docNote,
    ];
  })->values()->all();

  $years = [];
  if (isset($tahunOptions) && is_array($tahunOptions) && count($tahunOptions)) {
    $years = $tahunOptions;
  } else {
    $years = array_values(array_unique(array_map(fn($x) => $x['tahun'], $rows)));
    rsort($years);
  }

  $unitOptions = array_values(array_unique(array_map(fn($x) => $x['unit'], $rows)));
  sort($unitOptions);

  $lockedUnit = $unitOptions[0] ?? $unitName;

  $bulkDeleteUrl = url('/unit/arsip');

  // ✅ query string untuk pagination (biar page 2 ikut filter)
  $qs = request()->except('page');

  // ✅ URL export (route)
  $exportUrl = route('unit.arsip.export');

  /**
   * ✅ NOTIF: samakan PPK (tambah/edit)
   * - default: session('success')
   * - fallback: session('updated') / session('edited')
   * - fallback terakhir: query ?edited=1
   */
  $toastMessage = session('success')
    ?? session('updated')
    ?? session('edited')
    ?? (request()->query('edited') ? 'Arsip berhasil diedit.' : null);
@endphp

<div class="dash-wrap">
  {{-- SIDEBAR --}}
  <aside class="dash-sidebar">
    <div class="dash-brand">
      <div class="dash-logo">
        <img src="{{ asset('image/Logo_Unsoed.png') }}" alt="Logo Unsoed">
      </div>

      <div class="dash-text">
        <div class="dash-app">SIAPABAJA</div>
        <div class="dash-role">PIC (Unit)</div>
      </div>
    </div>

    <div class="dash-unitbox">
      <div class="dash-unit-label">Unit Kerja :</div>
      <div class="dash-unit-name">{{ $unitName }}</div>
    </div>

    <nav class="dash-nav">
      <a class="dash-link" href="{{ url('/unit/dashboard') }}">
        <span class="ic"><i class="bi bi-grid-fill"></i></span>
        Dashboard
      </a>

      <a class="dash-link active" href="{{ url('/unit/arsip') }}">
        <span class="ic"><i class="bi bi-archive"></i></span>
        Arsip PBJ
      </a>

      <a class="dash-link" href="{{ url('/unit/pengadaan/tambah') }}">
        <span class="ic"><i class="bi bi-plus-square"></i></span>
        Tambah Pengadaan
      </a>

      <a class="dash-link {{ request()->routeIs('unit.kelola.akun') ? 'active' : '' }}" href="{{ route('unit.kelola.akun') }}">
        <span class="ic"><i class="bi bi-person-gear"></i></span>
        Kelola Akun
      </a>
    </nav>

    <div class="dash-side-actions">
      <a class="dash-side-btn" href="{{ route('home') }}">
        <i class="bi bi-house-door"></i>
        Kembali
      </a>

      <a class="dash-side-btn" href="{{ url('/logout') }}">
        <i class="bi bi-box-arrow-right"></i>
        Keluar
      </a>
    </div>
  </aside>

  {{-- MAIN --}}
  <main class="dash-main">
    <header class="dash-header ap-header">
      <div class="ap-header-left">
        <h1>Arsip PBJ</h1>
        <p>Kelola arsip pengadaan barang dan jasa Universitas Jenderal Soedirman</p>
      </div>

      <div class="ap-header-right">
        {{-- ✅ EXPORT (SAMAKAN PPK: icon printer) --}}
        <button type="button" id="apPrintBtn" class="ap-print-btn" title="Cetak Arsip">
          <i class="bi bi-printer"></i>
          Export Arsip
        </button>
      </div>
    </header>

    {{-- ✅ TOAST NOTIFIKASI (SAMAKAN PPK) --}}
    @if(!empty($toastMessage))
      <div class="nt-wrap" id="ntWrap" aria-live="polite" aria-atomic="true">
        <div class="nt-toast nt-success" id="ntToast" role="status" data-autohide="true">
          <div class="nt-ic">
            <i class="bi bi-check2-circle"></i>
          </div>

          <div class="nt-content">
            <div class="nt-title">Berhasil</div>
            <div class="nt-desc">{{ $toastMessage }}</div>
          </div>

          <button type="button" class="nt-close" id="ntCloseBtn" aria-label="Tutup notifikasi">
            <i class="bi bi-x-lg"></i>
          </button>

          <div class="nt-bar" aria-hidden="true"></div>
        </div>
      </div>
    @endif

    {{-- FILTER BAR --}}
    <section class="dash-filter ap-filter">
      <div class="ap-filter-row">
        <div class="ap-search">
          <i class="bi bi-search"></i>
          <input id="apSearchInput" type="text" placeholder="Cari..." value="{{ $selectedQ }}" />
        </div>

        <div class="ap-select" style="display:none;">
          <select id="apUnitFilter">
            <option value="{{ $lockedUnit }}" selected>{{ $lockedUnit }}</option>
          </select>
          <i class="bi bi-chevron-down"></i>
        </div>

        <div class="ap-select">
          <select id="apStatusFilter">
            <option value="Semua"  {{ $selectedStatus === 'Semua' ? 'selected' : '' }}>Semua Status</option>
            <option value="Publik" {{ $selectedStatus === 'Publik' ? 'selected' : '' }}>Publik</option>
            <option value="Privat" {{ $selectedStatus === 'Privat' ? 'selected' : '' }}>Privat</option>
          </select>
          <i class="bi bi-chevron-down"></i>
        </div>

        <div class="ap-select">
          <select id="apYearFilter">
            <option value="Semua" {{ (string)$selectedYear === 'Semua' ? 'selected' : '' }}>Semua Tahun</option>
            @foreach($years as $y)
              <option value="{{ $y }}" {{ (string)$selectedYear === (string)$y ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
          </select>
          <i class="bi bi-chevron-down"></i>
        </div>

        <div class="ap-tools">
          <button type="button" id="apRefreshBtn" class="ap-icbtn" title="Refresh">
            <i class="bi bi-arrow-clockwise"></i>
          </button>

          <a href="#" id="apEditLink" class="ap-icbtn is-disabled" title="Edit" aria-disabled="true">
            <i class="bi bi-pencil"></i>
          </a>

          <button type="button" id="apDeleteBtn" class="ap-icbtn is-disabled" title="Hapus" aria-disabled="true"
                  data-bulk-delete-url="{{ $bulkDeleteUrl }}">
            <i class="bi bi-trash3"></i>
          </button>
        </div>
      </div>
    </section>

    {{-- TABLE ARSIP --}}
    <section class="dash-table ap-table">
      <div class="ap-head">
        <div class="ap-check">
          <input id="apSelectAll" type="checkbox" aria-label="Pilih semua" />
        </div>

        <div class="ap-col-center">Tahun</div>
        <div class="ap-col-left">Unit Kerja</div>
        <div class="ap-col-left">Nama Pekerjaan</div>

        <div class="ap-col-center ap-nilai-sort">
          <span>Nilai Kontrak</span>
          <button type="button" id="sortNilaiBtn" class="ap-sort-btn" title="Urutkan Nilai Kontrak">
            <i id="sortNilaiIcon" class="bi
              {{ $initialSortNilai === 'asc' ? 'bi-sort-up' : ($initialSortNilai === 'desc' ? 'bi-sort-down-alt' : 'bi-arrow-down-up') }}">
            </i>
          </button>
        </div>

        <div class="ap-col-center">Status Arsip</div>
        <div class="ap-col-center">Status Pekerjaan</div>
        <div class="ap-col-center" style="text-align:center;">Aksi</div>
      </div>

      @if(empty($rows))
        <div class="ap-empty" style="padding:24px;text-align:center;opacity:.85;">
          <i class="bi bi-inbox" style="font-size:28px;"></i>
          <p style="margin:8px 0 0;">Belum ada data arsip untuk unit ini.</p>
        </div>
      @else
        @foreach($rows as $r)
          @php
            $sp = strtolower(trim($r['status_pekerjaan'] ?? ''));
            $spClass = match ($sp) {
              'perencanaan' => 'ap-sp ap-sp-plan',
              'pemilihan'   => 'ap-sp ap-sp-select',
              'pelaksanaan' => 'ap-sp ap-sp-do',
              'selesai'     => 'ap-sp ap-sp-done',
              default       => 'ap-sp',
            };

            $pekerjaanRaw = (string)($r['pekerjaan'] ?? '');
            $parts = array_map('trim', explode('|', $pekerjaanRaw, 2));
            $namaPekerjaan = $parts[0] ?: ($r['nama_pekerjaan'] ?? '-');
            $idrupValue    = $r['id_rup'] ?? ($parts[1] ?? '-');

            $rekananValue  = $r['nama_rekanan'] ?? '-';
            $docsValue     = $r['dokumen'] ?? [];

            $nilaiRaw = preg_replace('/[^\d]/', '', (string)($r['nilai_kontrak'] ?? ''));
            $nilaiRaw = $nilaiRaw === '' ? '0' : $nilaiRaw;
          @endphp

          <div class="ap-row"
              data-status="{{ $r['status_arsip'] }}"
              data-year="{{ $r['tahun'] }}"
              data-unit="{{ $r['unit'] }}"
              data-search="{{ strtolower(($r['tahun'] ?? '').' '.($r['unit'] ?? '').' '.$namaPekerjaan.' '.$idrupValue.' '.($r['metode_pbj'] ?? '').' '.($r['nilai_kontrak'] ?? '').' '.$nilaiRaw.' '.($r['status_pekerjaan'] ?? '')) }}">

            <div class="ap-check">
              <input class="ap-row-check" type="checkbox" value="{{ $r['id'] }}" aria-label="Pilih baris" />
            </div>

            <div class="ap-year ap-col-center">{{ $r['tahun'] }}</div>
            <div class="ap-unit ap-col-left">{{ $r['unit'] }}</div>

            <div class="ap-job ap-col-left">
              {{ $namaPekerjaan }}
            </div>

            <div class="ap-col-center">
              <span class="ap-money">{{ $r['nilai_kontrak'] }}</span>
            </div>

            <div class="ap-arsip ap-col-center ap-arsip-center">
              @if(($r['status_arsip'] ?? '') === 'Publik')
                <span class="ap-eye ap-eye-pub"><i class="bi bi-eye"></i> Publik</span>
              @else
                <span class="ap-eye ap-eye-pri"><i class="bi bi-eye-slash"></i> Privat</span>
              @endif
            </div>

            <div class="ap-col-center">
              <span class="{{ $spClass }}">{{ $r['status_pekerjaan'] }}</span>
            </div>

            <div class="ap-aksi ap-col-center">
              <a href="#"
                class="ap-detail js-open-detail"
                data-id="{{ $r['id'] }}"
                data-title="{{ $namaPekerjaan }}"
                data-unit="{{ $r['unit'] }}"
                data-tahun="{{ $r['tahun'] }}"
                data-idrup="{{ $idrupValue }}"
                data-status="{{ $r['status_pekerjaan'] }}"
                data-rekanan="{{ $rekananValue }}"
                data-jenis="{{ $r['jenis_pengadaan'] ?? '-' }}"
                data-pagu="{{ $r['pagu_anggaran'] ?? '-' }}"
                data-hps="{{ $r['hps'] ?? '-' }}"
                data-kontrak="{{ $r['nilai_kontrak'] }}"
                data-docnote="{{ $r['doc_note'] ?? '' }}"
                data-docs='@json($docsValue)'
              >Detail</a>
            </div>
          </div>
        @endforeach
      @endif

      {{-- PAGINATION --}}
      <div class="ap-pagination-wrap">
        <div class="ap-page-info">
          Halaman {{ $arsips->currentPage() }} dari {{ $arsips->lastPage() }}
          • Menampilkan {{ $arsips->count() }} dari {{ $arsips->total() }} data
        </div>

        <div class="ap-pagination">
          <a class="ap-page-btn {{ $arsips->onFirstPage() ? 'is-disabled' : '' }}"
            href="{{ $arsips->onFirstPage() ? '#' : $arsips->appends($qs)->previousPageUrl() }}"
            aria-disabled="{{ $arsips->onFirstPage() ? 'true' : 'false' }}">
            <i class="bi bi-chevron-left"></i>
          </a>

          @php
            $current = $arsips->currentPage();
            $last = $arsips->lastPage();
            $start = max(1, $current - 2);
            $end   = min($last, $current + 2);
          @endphp

          @if($start > 1)
            <a class="ap-page-btn" href="{{ $arsips->appends($qs)->url(1) }}">1</a>
            @if($start > 2)
              <span class="ap-page-btn is-ellipsis" aria-hidden="true">…</span>
            @endif
          @endif

          @for($i = $start; $i <= $end; $i++)
            <a class="ap-page-btn {{ $i === $current ? 'is-active' : '' }}"
              href="{{ $arsips->appends($qs)->url($i) }}">
              {{ $i }}
            </a>
          @endfor

          @if($end < $last)
            @if($end < $last - 1)
              <span class="ap-page-btn is-ellipsis" aria-hidden="true">…</span>
            @endif
            <a class="ap-page-btn" href="{{ $arsips->appends($qs)->url($last) }}">{{ $last }}</a>
          @endif

          <a class="ap-page-btn {{ $arsips->hasMorePages() ? '' : 'is-disabled' }}"
            href="{{ $arsips->hasMorePages() ? $arsips->appends($qs)->nextPageUrl() : '#' }}"
            aria-disabled="{{ $arsips->hasMorePages() ? 'false' : 'true' }}">
            <i class="bi bi-chevron-right"></i>
          </a>
        </div>
      </div>

    </section>
  </main>
</div>

<!-- ====== MODAL DETAIL (POPUP) ====== -->
<div class="dt-modal" id="dtModal" aria-hidden="true">
  <div class="dt-backdrop" data-close="true"></div>

  <div class="dt-panel" role="dialog" aria-modal="true" aria-labelledby="dtTitle">
    <div class="dt-card">

      <div class="dt-topbar">
        <div class="dt-title" id="dtTitle">-</div>

        <button type="button" class="dt-close-inside" id="dtCloseBtn" aria-label="Tutup">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <div class="dt-body">

        <div class="dt-info-grid">
          <div class="dt-info">
            <div class="dt-ic"><i class="bi bi-envelope"></i></div>
            <div class="dt-info-txt">
              <div class="dt-label">Unit Kerja</div>
              <div class="dt-val" id="dtUnit">-</div>
            </div>
          </div>

          <div class="dt-info">
            <div class="dt-ic"><i class="bi bi-calendar-event"></i></div>
            <div class="dt-info-txt">
              <div class="dt-label">Tahun Anggaran</div>
              <div class="dt-val" id="dtTahun">-</div>
            </div>
          </div>

          <div class="dt-info">
            <div class="dt-ic"><i class="bi bi-person-badge"></i></div>
            <div class="dt-info-txt">
              <div class="dt-label">ID RUP</div>
              <div class="dt-val" id="dtIdRup">-</div>
            </div>
          </div>

          <div class="dt-info">
            <div class="dt-ic"><i class="bi bi-folder2"></i></div>
            <div class="dt-info-txt">
              <div class="dt-label">Status Pekerjaan</div>
              <div class="dt-val" id="dtStatus">-</div>
            </div>
          </div>

          <div class="dt-info">
            <div class="dt-ic"><i class="bi bi-person"></i></div>
            <div class="dt-info-txt">
              <div class="dt-label">Nama Rekanan</div>
              <div class="dt-val" id="dtRekanan">-</div>
            </div>
          </div>

          <div class="dt-info">
            <div class="dt-ic"><i class="bi bi-box"></i></div>
            <div class="dt-info-txt">
              <div class="dt-label">Jenis Pengadaan</div>
              <div class="dt-val" id="dtJenis">-</div>
            </div>
          </div>
        </div>

        <div class="dt-divider"></div>

        <div class="dt-section-title">Informasi Anggaran</div>

        <div class="dt-budget-grid">
          <div class="dt-budget">
            <div class="dt-label">Pagu Anggaran</div>
            <div class="dt-money" id="dtPagu">-</div>
          </div>

          <div class="dt-budget">
            <div class="dt-label">HPs</div>
            <div class="dt-money" id="dtHps">-</div>
          </div>

          <div class="dt-budget">
            <div class="dt-label">Nilai Kontrak</div>
            <div class="dt-money" id="dtKontrak">-</div>
          </div>
        </div>

        <div class="dt-divider"></div>

        <div class="dt-section-title">Dokumen Pengadaan</div>

        <div class="dt-doc-grid" id="dtDocList"></div>
        <div class="dt-doc-empty" id="dtDocEmpty" hidden>Tidak ada dokumen yang diupload.</div>

        {{-- KOLOM E --}}
        <div class="dt-doc-note" id="dtDocNoteWrap" hidden>
          <div class="dt-doc-note-ic"><i class="bi bi-info-circle"></i></div>
          <div class="dt-doc-note-txt">
            <div class="dt-doc-note-title">Dokumen tidak dipersyaratkan</div>
            <div class="dt-doc-note-desc" id="dtDocNote">-</div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- ====== MODAL KONFIRMASI HAPUS (SAMAKAN PPK) ====== -->
<div class="cf-modal" id="cfModal" aria-hidden="true">
  <div class="cf-backdrop" data-close="true"></div>

  <div class="cf-panel" role="dialog" aria-modal="true" aria-labelledby="cfTitle" aria-describedby="cfDesc">
    <div class="cf-card">
      <div class="cf-top">
        <div class="cf-badge">
          <i class="bi bi-shield-exclamation"></i>
        </div>

        <button type="button" class="cf-close" id="cfCloseBtn" aria-label="Tutup">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <div class="cf-body">
        <div class="cf-title" id="cfTitle">Konfirmasi Hapus</div>
        <div class="cf-desc" id="cfDesc">
          Apakah Anda yakin ingin menghapus arsip ini?
        </div>

        <div class="cf-meta" id="cfMeta" hidden>
          <div class="cf-pill"><i class="bi bi-archive"></i> <span id="cfCount">1</span> dipilih</div>
        </div>

        <div class="cf-actions">
          <button type="button" class="cf-btn cf-btn-ghost" id="cfCancelBtn">
            Batal
          </button>
          <button type="button" class="cf-btn cf-btn-danger" id="cfConfirmBtn">
            <i class="bi bi-trash3"></i>
            Ya, Hapus
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  body.page-arsip.dash-body{ font-size: 18px; line-height: 1.6; }
  .page-arsip{
    --ap-field-h: 46px; --ap-field-r: 12px; --ap-field-px: 12px;
    --ap-sp-h: 34px; --ap-sp-w: 124px; --ap-sp-r: 8px;
    --ap-row-divider: 2px;
    --unsoed-yellow: #f6c100;
    --unsoed-yellow-dark: #d9aa00;
  }
  .page-arsip .ap-header{ display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
  .page-arsip .ap-header-left{ min-width: 0; }
  .page-arsip .ap-header-right{ flex: 0 0 auto; display:flex; align-items:center; justify-content:flex-end; }

  .page-arsip .ap-print-btn{
    height: 42px; padding: 0 14px; border-radius: 12px;
    border: 1px solid rgba(0,0,0,.08);
    background: var(--unsoed-yellow); color: #1b1b1b;
    font-size: 15px; font-weight: 400;
    display:inline-flex; align-items:center; gap:8px;
    cursor:pointer; box-shadow: 0 6px 16px rgba(0,0,0,.10);
    transition: .15s ease; user-select:none; white-space: nowrap; letter-spacing: 0.8px;
  }
  .page-arsip .ap-print-btn:hover{ background: var(--unsoed-yellow-dark); transform: translateY(-1px); }
  .page-arsip .ap-print-btn:active{ transform: translateY(0); }
  .page-arsip .ap-print-btn i{ font-size: 16px; line-height: 1; display:block; }

  .page-arsip .ap-filter-row{ display:flex; gap:12px; align-items:center; flex-wrap:nowrap; }
  .page-arsip .ap-search{ position: relative; flex: 1 1 auto; min-width: 220px; height: var(--ap-field-h); display:flex; align-items:center; }
  .page-arsip .ap-search i{ position:absolute; left: var(--ap-field-px); top: 50%; transform: translateY(-50%); font-size: 18px; opacity: .75; pointer-events:none; }
  .page-arsip .ap-search input{ width:100%; height: 100%; font-size: 16px; padding: 0 calc(var(--ap-field-px) + 6px) 0 44px; border-radius: var(--ap-field-r); box-sizing: border-box; }
  .page-arsip .ap-select{ position: relative; flex: 0 0 200px; min-width: 200px; height: var(--ap-field-h); display:flex; align-items:center; }
  .page-arsip .ap-select select{ width:100%; height: 100%; font-size: 16px; padding: 0 42px 0 var(--ap-field-px); border-radius: var(--ap-field-r); box-sizing: border-box; }
  .page-arsip .ap-select i{ position:absolute; right: 12px; top: 50%; transform: translateY(-50%); pointer-events:none; }
  .page-arsip .ap-tools{ display:flex; gap:10px; align-items:center; flex: 0 0 auto; }
  .page-arsip .ap-icbtn{ width: 40px; height: 40px; padding: 0; display:inline-flex; align-items:center; justify-content:center; line-height: 1; }
  .page-arsip .ap-icbtn i{ font-size: 18px; line-height: 1; display:block; }

  .page-arsip .ap-head,
  .page-arsip .ap-row{
    display:grid;
    grid-template-columns: 44px 86px 1.25fr 2.45fr 1.55fr 1.10fr 1.25fr 90px;
    column-gap: 18px;
    padding-left: 18px; padding-right: 18px;
    font-size: 16px;
    align-items:center;
  }
  .page-arsip .ap-head > div,
  .page-arsip .ap-row > div{ text-align:left; justify-self:start; min-width: 0; }
  .page-arsip .ap-col-left{ text-align:left !important; justify-self:start !important; }
  .page-arsip .ap-col-center{ text-align:center !important; justify-self:center !important; }
  .page-arsip .ap-nilai-sort{ display:inline-flex; align-items:center; justify-content:center; gap:2px; }
  .page-arsip .ap-sort-btn{ width: 32px; height: 32px; border-radius: 10px; border: none; background: transparent; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition: .15s ease; padding: 0; line-height: 1; }
  .page-arsip .ap-sort-btn:hover{ background: transparent; }
  .page-arsip .ap-sort-btn i{ font-size: 20px; line-height: 1; display:block; color:#fff !important; }
  .page-arsip .ap-arsip-center{ display:flex; justify-content:center; align-items:center; }
  .page-arsip .ap-job{ line-height: 1.35; overflow-wrap: anywhere; }
  .page-arsip .ap-money{ display:inline-block; color: var(--navy2); font-weight: 400; white-space: nowrap; line-height: 1.2; }
  .page-arsip .ap-row{ border-top: var(--ap-row-divider) solid #eef3f6; }
  .page-arsip .ap-sp{ display:inline-flex; align-items:center; justify-content:center; height: var(--ap-sp-h); width: var(--ap-sp-w); padding: 0 14px; border-radius: var(--ap-sp-r); font-size: 15px; white-space: nowrap; text-align:center; }
  .page-arsip .ap-sp-plan{ background:#FDF0A8; }
  .page-arsip .ap-sp-select{ background:#E8C9FF; }
  .page-arsip .ap-sp-do{ background:#F8B8B8; }
  .page-arsip .ap-sp-done{ background:#BFE9BF; }
  .page-arsip .ap-eye,
  .page-arsip .ap-detail{ font-size: 15.5px; }
  .page-arsip .dt-title{ font-size: 20px; }
  .page-arsip .dt-label{ font-size: 15px; }
  .page-arsip .dt-val{ font-size: 16px; }
  .page-arsip .dt-section-title{ font-size: 18px; }
  .page-arsip .dt-money{ font-size: 18px; }
  .page-arsip .ap-icbtn.is-disabled{ opacity: .45; cursor: not-allowed; pointer-events: auto; }

  .page-arsip .dt-modal{ position: fixed; inset: 0; z-index: 9999; display: none; }
  .page-arsip .dt-modal.is-open{ display: flex; align-items: center; justify-content: center; padding: 10px; }
  .page-arsip .dt-backdrop{ position: fixed; inset: 0; background: rgba(15, 23, 42, .35); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); }
  .page-arsip .dt-panel{ width: min(1440px, 96vw) !important; max-height: calc(100vh - 20px) !important; display: flex; position: relative; z-index: 1; border-radius: 24px !important; overflow: hidden; }
  .page-arsip .dt-card{ width: 100%; display: flex; flex-direction: column; min-height: 0; border-radius: 24px !important; background: #fff; overflow: hidden; }
  .page-arsip .dt-topbar{ position: sticky; top: 0; z-index: 3; background: #fff; padding: 18px 18px 12px; border-bottom: 1px solid #eef3f6; display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
  .page-arsip .dt-topbar .dt-title{ flex: 1 1 auto; min-width: 0; margin: 0; line-height: 1.35; overflow-wrap: anywhere; transform: translate(6px, -8px) !important; }
  .page-arsip .dt-close-inside{ margin-top: -8px; flex: 0 0 auto; width: 44px; height: 44px; border-radius: 16px; display:grid; place-items:center; padding:0; position: static; }
  .page-arsip .dt-close-inside i{ display:block; line-height: 1; font-size: 18px; }
  .page-arsip .dt-topbar .dt-close-inside{ transform: translate(6px, -8px) !important; }
  .page-arsip .dt-body{ padding: 16px 18px 18px; overflow-y: auto; min-height: 0; overscroll-behavior: contain; }

  .page-arsip .dt-doc-note{ margin-top: 14px; border: 1px solid #e8eef3; background: #f8fbfd; border-radius: 16px; padding: 12px 14px; display:flex; gap:12px; align-items:flex-start; }
  .page-arsip .dt-doc-note-ic{ width: 40px; height: 40px; border-radius: 14px; display:grid; place-items:center; background: #ffffff; border: 1px solid #e8eef3; flex: 0 0 auto; }
  .page-arsip .dt-doc-note-ic i{ font-size: 18px; line-height: 1; display:block; opacity: .9; }
  .page-arsip .dt-doc-note-title{ font-size: 14px; font-weight: 700; margin-bottom: 2px; color: #0f172a; }
  .page-arsip .dt-doc-note-desc{ font-size: 13.5px; color: #475569; line-height: 1.5; }

  .page-arsip .dt-doc-grid{ display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:12px; }
  @media (max-width: 900px){ .page-arsip .dt-doc-grid{ grid-template-columns: 1fr; } }
  .page-arsip .dt-doc-card{ border: 1px solid #e8eef3; background:#fff; border-radius: 16px; padding: 12px 14px; display:flex; align-items:center; gap:12px; text-decoration:none; color: inherit; position: relative; }
  .page-arsip .dt-doc-ic{ width: 44px; height: 44px; border-radius: 16px; display:grid; place-items:center; background:#f8fbfd; border: 1px solid #eef3f6; flex: 0 0 auto; }
  .page-arsip .dt-doc-ic i{ font-size: 18px; }
  .page-arsip .dt-doc-info{ min-width:0; flex:1; }
  .page-arsip .dt-doc-title{ font-size: 14.5px; font-weight: 800; line-height: 1.35; overflow-wrap:anywhere; }
  .page-arsip .dt-doc-sub{ font-size: 12.5px; color:#64748b; margin-top: 2px; overflow-wrap:anywhere; }
  .page-arsip .dt-doc-act{ width: 36px; height: 36px; border-radius: 14px; display:grid; place-items:center; background:#f8fbfd; border: 1px solid #eef3f6; flex:0 0 auto; }
  .page-arsip .dt-doc-act i{ font-size: 16px; }

  .page-arsip .ap-pagination-wrap{ display:flex; align-items:center; justify-content:space-between; gap:12px; padding: 14px 18px 16px; border-top: 2px solid #eef3f6; }
  .page-arsip .ap-page-info{ font-size: 13.5px; color:#64748b; white-space: nowrap; }
  .page-arsip .ap-pagination{ display:flex; align-items:center; gap:6px; flex-wrap:wrap; justify-content:flex-end; }
  .page-arsip .ap-page-btn{ min-width: 36px; height: 34px; padding: 0 10px; border-radius: 10px; border: 1px solid #e6eef2; background:#fff; color:#0f172a; font-size: 13px; font-weight: 600; display:inline-flex; align-items:center; justify-content:center; text-decoration:none; transition: .15s ease; user-select:none; }
  .page-arsip .ap-page-btn:hover{ border-color:#cfe2ea; background:#f8fbfd; }
  .page-arsip .ap-page-btn.is-active{ border-color: transparent; background: var(--navy2); color:#fff; }
  .page-arsip .ap-page-btn.is-disabled{ opacity: .55; pointer-events:none; background:#f8fafc; }
  .page-arsip .ap-page-btn.is-ellipsis{ pointer-events:none; background: transparent; border-color: transparent; min-width: 24px; padding: 0 4px; }

  @media (max-width: 1100px){
    .page-arsip .ap-filter-row{ flex-wrap: wrap; }
    .page-arsip .ap-search{ flex: 1 1 320px; min-width: 260px; }
    .page-arsip .ap-select{ flex: 1 1 220px; min-width: 220px; }
    .page-arsip .ap-pagination-wrap{ flex-direction: column; align-items: flex-start; }
    .page-arsip .ap-pagination{ justify-content:flex-start; }
    .page-arsip .ap-header{ flex-direction: column; align-items: flex-start; }
    .page-arsip .ap-header-right{ width:100%; justify-content:flex-end; }
  }

  @media print{
    .dash-sidebar, .ap-filter, .ap-tools, .ap-aksi,
    .ap-head .ap-check, .ap-row .ap-check,
    .ap-header-right, .dt-modal, .ap-pagination-wrap, .cf-modal{ display:none !important; }

    .dash-main{ width: 100% !important; }
    .dash-wrap{ display:block !important; }
    body{ background:#fff !important; }
    .dash-table{ box-shadow:none !important; }

    .page-arsip .ap-head,
    .page-arsip .ap-row{
      grid-template-columns: 86px 1.25fr 2.45fr 1.55fr 1.10fr 1.25fr;
      padding-left: 0 !important;
      padding-right: 0 !important;
      column-gap: 14px;
    }
  }

  /* =========================
     ✅ CSS MODAL KONFIRMASI (SAMAKAN PPK)
  ========================== */
  .page-arsip .cf-modal{position:fixed;inset:0;z-index:10000;display:none;}
  .page-arsip .cf-modal.is-open{display:flex;align-items:center;justify-content:center;padding:12px;}
  .page-arsip .cf-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.40);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);}
  .page-arsip .cf-panel{
    width:min(520px, 94vw);
    position:relative;
    z-index:1;
    border-radius:24px;
    overflow:hidden;
    box-shadow:0 22px 60px rgba(2,6,23,.25);
  }
  .page-arsip .cf-card{
    background:linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
    border:1px solid rgba(148,163,184,.35);
    border-radius:24px;
    overflow:hidden;
  }
  .page-arsip .cf-top{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
    padding:16px 16px 0 16px;
  }
  .page-arsip .cf-badge{
    width:52px;height:52px;border-radius:18px;
    display:grid;place-items:center;
    background:linear-gradient(135deg, rgba(246,193,0,.18), rgba(15,23,42,.06));
    border:1px solid rgba(246,193,0,.35);
    box-shadow:0 10px 24px rgba(2,6,23,.08);
    flex:0 0 auto;
  }
  .page-arsip .cf-badge i{font-size:22px;line-height:1;color:#0f172a;}
  .page-arsip .cf-close{
    width:44px;height:44px;border-radius:16px;
    border:1px solid #e8eef3;background:#fff;
    display:flex;align-items:center;justify-content:center;
    padding:0;line-height:0;
    cursor:pointer;transition:.15s ease;
    flex:0 0 auto;
  }
  .page-arsip .cf-close:hover{transform:translateY(-1px);border-color:#d6e2ea;background:#f8fbfd;}
  .page-arsip .cf-close:active{transform:translateY(0);}
  .page-arsip .cf-close i{font-size:18px;line-height:1;display:block;}

  .page-arsip .cf-body{padding:10px 16px 16px;}
  .page-arsip .cf-title{
    font-size:20px;
    font-weight:600;
    color:#0f172a;
    letter-spacing:.2px;
    margin:2px 0 6px;
  }
  .page-arsip .cf-desc{
    font-size:14.5px;
    color:#475569;
    line-height:1.55;
    margin:0 0 10px;
  }
  .page-arsip .cf-meta{display:flex;gap:10px;align-items:center;margin:10px 0 12px;}
  .page-arsip .cf-pill{
    display:inline-flex;align-items:center;gap:8px;
    padding:8px 10px;border-radius:999px;
    background:#f8fbfd;border:1px solid #e8eef3;
    font-size:13px;font-weight:500;color:#0f172a;
  }
  .page-arsip .cf-pill i{font-size:14px;opacity:.9;}
  .page-arsip .cf-actions{display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;margin-top:8px;}
  .page-arsip .cf-btn{
    height:42px;
    padding:0 14px;
    border-radius:14px;
    border:1px solid transparent;
    font-size:14px;
    font-weight:500;
    cursor:pointer;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    transition:.15s ease;
    user-select:none;
  }
  .page-arsip .cf-btn-ghost{
    background:#fff;
    border-color:#e8eef3;
    color:#0f172a;
  }
  .page-arsip .cf-btn-ghost:hover{background:#f8fbfd;border-color:#d6e2ea;transform:translateY(-1px);}
  .page-arsip .cf-btn-ghost:active{transform:translateY(0);}
  .page-arsip .cf-btn-danger{
    background:linear-gradient(135deg, #ef4444, #dc2626);
    color:#fff;
    box-shadow:0 14px 30px rgba(220,38,38,.22);
  }
  .page-arsip .cf-btn-danger:hover{transform:translateY(-1px);filter:saturate(1.05);}
  .page-arsip .cf-btn-danger:active{transform:translateY(0);}
  .page-arsip .cf-btn:disabled{opacity:.7;cursor:not-allowed;transform:none;box-shadow:none;}

  /* =========================
     ✅ CSS TOAST NOTIF (SAMAKAN PPK)
  ========================== */
  .page-arsip .nt-wrap{
    position:fixed;
    top:18px;
    right:18px;
    z-index:11000;
    pointer-events:none;
  }
  .page-arsip .nt-toast{
    width:min(420px, calc(100vw - 36px));
    background:linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
    border:1px solid rgba(148,163,184,.35);
    border-left:6px solid rgba(246,193,0,.95);
    border-radius:18px;
    box-shadow:0 18px 52px rgba(2,6,23,.18);
    padding:12px 12px;
    display:flex;
    gap:12px;
    align-items:flex-start;
    transform:translateY(-10px);
    opacity:0;
    transition:.22s ease;
    pointer-events:auto;
    position:relative;
    overflow:hidden;
  }
  .page-arsip .nt-toast.is-show{
    transform:translateY(0);
    opacity:1;
  }
  .page-arsip .nt-ic{
    width:44px;height:44px;border-radius:16px;
    display:grid;place-items:center;
    background:linear-gradient(135deg, rgba(246,193,0,.18), rgba(15,23,42,.06));
    border:1px solid rgba(246,193,0,.35);
    flex:0 0 auto;
  }
  .page-arsip .nt-ic i{font-size:20px;line-height:1;color:#0f172a;}
  .page-arsip .nt-content{min-width:0;flex:1;}
  .page-arsip .nt-title{
    font-size:14px;
    font-weight:900;
    color:#0f172a;
    letter-spacing:.2px;
    margin:1px 0 2px;
  }
  .page-arsip .nt-desc{
    font-size:13.5px;
    color:#475569;
    line-height:1.45;
    overflow-wrap:anywhere;
  }
  .page-arsip .nt-close{
    width:40px;height:40px;border-radius:14px;
    border:1px solid #e8eef3;background:#fff;
    display:grid;place-items:center;
    cursor:pointer;transition:.15s ease;
    flex:0 0 auto;
  }
  .page-arsip .nt-close:hover{transform:translateY(-1px);border-color:#d6e2ea;background:#f8fbfd;}
  .page-arsip .nt-close:active{transform:translateY(0);}
  .page-arsip .nt-close i{font-size:16px;line-height:1;}
  .page-arsip .nt-bar{
    position:absolute;
    left:0;bottom:0;height:3px;
    width:100%;
    background:linear-gradient(90deg, rgba(246,193,0,.95), rgba(15,23,42,.28));
    transform-origin:left center;
    transform:scaleX(1);
  }

  /* ==========================================================
     ✅ UPDATE BARU: KECILIN "BERHASIL" + TEXT POPUP HAPUS
     (ditaruh PALING BAWAH biar override)
  ========================================================== */
  .page-arsip .nt-title{
    font-size:12.5px !important;
    font-weight:800 !important;
    letter-spacing:.15px !important;
    line-height:1.2 !important;
  }
  .page-arsip .nt-desc{
    font-size:12.5px !important;
    line-height:1.45 !important;
  }
  .page-arsip .nt-ic{
    width:40px !important;
    height:40px !important;
    border-radius:14px !important;
  }
  .page-arsip .nt-ic i{font-size:18px !important;}
  .page-arsip .nt-close{width:36px !important;height:36px !important;}
  .page-arsip .nt-close i{font-size:14px !important;}

  .page-arsip .cf-title{
    font-size:17px !important;
    font-weight:700 !important;
    line-height:1.25 !important;
  }
  .page-arsip .cf-desc{
    font-size:13px !important;
    line-height:1.5 !important;
  }
  .page-arsip .cf-pill{
    font-size:12.5px !important;
    padding:7px 10px !important;
  }
  .page-arsip .cf-btn{
    height:40px !important;
    font-size:13px !important;
    border-radius:12px !important;
  }
  .page-arsip .cf-badge{
    width:48px !important;
    height:48px !important;
    border-radius:16px !important;
  }
  .page-arsip .cf-badge i{font-size:20px !important;}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const selectAll = document.getElementById('apSelectAll');
  const unitEl    = document.getElementById('apUnitFilter');
  const filterEl  = document.getElementById('apStatusFilter');
  const yearEl    = document.getElementById('apYearFilter');
  const searchEl  = document.getElementById('apSearchInput');

  const refreshBtn = document.getElementById('apRefreshBtn');
  const deleteBtn  = document.getElementById('apDeleteBtn');

  const bulkDeleteUrl = deleteBtn?.getAttribute('data-bulk-delete-url') || '/unit/arsip';

  const baseUrl   = @json(url('/unit/arsip'));
  const exportUrl = @json($exportUrl);

  // ✅ Export BTN (samakan PPK: id apPrintBtn)
  const printBtn = document.getElementById('apPrintBtn');

  const getRows = () => Array.from(document.querySelectorAll('.ap-row'));
  const getVisibleRows = () => getRows().filter(r => r.style.display !== 'none');

  const lockedUnitName = @json($lockedUnit);
  if (unitEl) unitEl.value = lockedUnitName;

  function getCheckedIds(){
    return Array.from(document.querySelectorAll('.ap-row-check:checked'))
      .map(cb => cb.value)
      .filter(Boolean);
  }

  function setBtnDisabled(btn, disabled){
    if(!btn) return;
    btn.classList.toggle('is-disabled', !!disabled);
    btn.setAttribute('aria-disabled', disabled ? 'true' : 'false');
  }

  function syncSelectAllState(){
    if(!selectAll) return;

    const visible = getVisibleRows();
    const checks = visible.map(r => r.querySelector('.ap-row-check')).filter(Boolean);

    if (checks.length === 0) {
      selectAll.checked = false;
      selectAll.indeterminate = false;
      return;
    }

    const checkedCount = checks.filter(c => c.checked).length;
    selectAll.checked = checkedCount === checks.length;
    selectAll.indeterminate = checkedCount > 0 && checkedCount < checks.length;
  }

  function updateEditState(){
    const editLink = document.getElementById('apEditLink');
    if(!editLink) return;

    const ids = getCheckedIds();
    const active = (ids.length === 1);

    editLink.setAttribute('aria-disabled', active ? 'false' : 'true');
    editLink.classList.toggle('is-disabled', !active);
    editLink.href = active ? `/unit/arsip/${ids[0]}/edit` : '#';
  }

  function updateDeleteState(){
    const ids = getCheckedIds();
    setBtnDisabled(deleteBtn, ids.length === 0);
  }

  // =========================
  // ✅ TOAST NOTIFIKASI (SAMAKAN PPK)
  // =========================
  (function initToast(){
    const toast = document.getElementById('ntToast');
    const closeBtn = document.getElementById('ntCloseBtn');
    if(!toast) return;

    const bar = toast.querySelector('.nt-bar');
    let hideTimer = null;
    const DURATION = 4200;

    const show = () => {
      requestAnimationFrame(() => {
        toast.classList.add('is-show');
        if(bar){
          bar.style.transition = 'none';
          bar.style.transform = 'scaleX(1)';
          // trigger reflow
          void bar.offsetHeight;
          bar.style.transition = `transform ${DURATION}ms linear`;
          bar.style.transform = 'scaleX(0)';
        }
      });

      hideTimer = setTimeout(() => hide(), DURATION + 100);
    };

    const hide = () => {
      clearTimeout(hideTimer);
      toast.classList.remove('is-show');
      setTimeout(() => {
        const wrap = document.getElementById('ntWrap');
        if(wrap) wrap.remove();
      }, 250);
    };

    if(closeBtn) closeBtn.addEventListener('click', hide);

    // pause on hover
    toast.addEventListener('mouseenter', () => {
      clearTimeout(hideTimer);
      if(bar){
        // hentikan bar di posisi saat ini
        const computed = window.getComputedStyle(bar).transform;
        bar.style.transition = 'none';
        bar.style.transform = computed === 'none' ? bar.style.transform : computed;
      }
    });

    toast.addEventListener('mouseleave', () => {
      // lanjutkan bar (lebih simpel: langsung auto-hide cepat)
      hideTimer = setTimeout(() => hide(), 1200);
    });

    show();
  })();

  // =========================
  // ✅ SERVER-SIDE FILTER NAVIGATION (debounce)
  // ✅ + pertahankan sort_nilai dari URL saat ini
  // =========================
  let navTimer = null;

  function getCurrentSortNilai(){
    try{
      const cur = new URL(window.location.href);
      const s = (cur.searchParams.get('sort_nilai') || '').toLowerCase();
      return (s === 'asc' || s === 'desc') ? s : '';
    }catch(e){
      return '';
    }
  }

  function buildUrlWithFilters(){
    const statusVal = filterEl ? filterEl.value : 'Semua';
    const yearVal   = yearEl ? yearEl.value : 'Semua';
    const qVal      = (searchEl ? searchEl.value : '').trim();

    const url = new URL(baseUrl, window.location.origin);

    if(statusVal && statusVal !== 'Semua') url.searchParams.set('status', statusVal);
    if(yearVal && yearVal !== 'Semua') url.searchParams.set('tahun', yearVal);
    if(qVal !== '') url.searchParams.set('q', qVal);

    const s = getCurrentSortNilai();
    if(s) url.searchParams.set('sort_nilai', s);

    url.searchParams.delete('page');
    return url.toString();
  }

  function scheduleNavigate(delay = 1500){
    clearTimeout(navTimer);
    navTimer = setTimeout(() => {
      const nextUrl = buildUrlWithFilters();
      if(nextUrl !== window.location.href){
        window.location.href = nextUrl;
      }
    }, delay);
  }

  if (filterEl) filterEl.addEventListener('change', () => scheduleNavigate(150));
  if (yearEl)   yearEl.addEventListener('change', () => scheduleNavigate(150));

  if (searchEl){
    searchEl.addEventListener('input', () => scheduleNavigate(700));

    searchEl.addEventListener('keydown', function(e){
      if(e.key === 'Enter'){
        e.preventDefault();
        e.stopPropagation();
        scheduleNavigate(0);
        return false;
      }
    });

    if (searchEl.form){
      searchEl.form.addEventListener('submit', function(e){
        e.preventDefault();
        e.stopPropagation();
        return false;
      });
    }
  }

  if(refreshBtn){
    refreshBtn.addEventListener('click', function(){
      window.location.href = baseUrl;
    });
  }

  const editLink = document.getElementById('apEditLink');
  if(editLink){
    editLink.addEventListener('click', function(e){
      e.preventDefault();

      const ids = getCheckedIds();
      if(ids.length !== 1){
        alert(ids.length === 0 ? 'Pilih 1 arsip untuk diedit.' : 'Hanya boleh pilih 1 arsip untuk edit.');
        return;
      }

      window.location.href = `/unit/arsip/${ids[0]}/edit`;
    });
  }

  // =========================
  // ✅ DELETE ONE (SAMAKAN PPK: POST + _method=DELETE)
  // =========================
  async function deleteOne(id){
    // Unit: gunakan endpoint bulkDeleteUrl/{id} (umum), fallback ke /unit/arsip/{id}/delete
    let url = '';
    try{
      const base = String(bulkDeleteUrl || '').replace(/\/+$/,'');
      url = base ? `${base}/${encodeURIComponent(id)}` : `/unit/arsip/${encodeURIComponent(id)}/delete`;
    }catch(e){
      url = `/unit/arsip/${encodeURIComponent(id)}/delete`;
    }

    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf,
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'Accept': 'application/json'
      },
      body: '_method=DELETE'
    });

    if (!res.ok) {
      let msg = `Gagal menghapus arsip ID ${id}.`;
      try {
        const data = await res.json();
        msg = data?.message || msg;
      } catch (e) {}
      throw new Error(msg);
    }
  }

  // =========================
  // ✅ MODAL KONFIRMASI HAPUS (SAMAKAN PPK)
  // =========================
  const cfModal     = document.getElementById('cfModal');
  const cfCloseBtn  = document.getElementById('cfCloseBtn');
  const cfCancelBtn = document.getElementById('cfCancelBtn');
  const cfConfirmBtn= document.getElementById('cfConfirmBtn');
  const cfMeta      = document.getElementById('cfMeta');
  const cfCountEl   = document.getElementById('cfCount');
  const cfHint      = document.getElementById('cfHint'); // (opsional, sama seperti PPK)

  let pendingDeleteIds = [];
  let isDeleting = false;

  function openConfirmModal(ids){
    if(!cfModal) return;
    pendingDeleteIds = Array.isArray(ids) ? ids.slice() : [];
    const count = pendingDeleteIds.length;

    if(cfCountEl) cfCountEl.textContent = String(count || 0);
    if(cfMeta) cfMeta.hidden = !(count > 0);
    if(cfHint) cfHint.hidden = true;

    if(cfConfirmBtn){
      cfConfirmBtn.disabled = false;
      cfConfirmBtn.innerHTML = `<i class="bi bi-trash3"></i> Ya, Hapus`;
    }

    cfModal.classList.add('is-open');
    document.body.classList.add('modal-open');
    document.body.style.overflow = 'hidden';
    cfModal.setAttribute('aria-hidden', 'false');

    setTimeout(() => { cfCancelBtn?.focus?.(); }, 0);
  }

  function closeConfirmModal(){
    if(!cfModal) return;
    if(isDeleting) return;

    cfModal.classList.remove('is-open');
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    cfModal.setAttribute('aria-hidden', 'true');

    pendingDeleteIds = [];
  }

  async function runDelete(ids){
    if(isDeleting) return;
    isDeleting = true;

    if(cfHint) cfHint.hidden = false;
    if(cfConfirmBtn){
      cfConfirmBtn.disabled = true;
      cfConfirmBtn.innerHTML = `<i class="bi bi-hourglass-split"></i> Menghapus...`;
    }

    setBtnDisabled(deleteBtn, true);
    if(deleteBtn) deleteBtn.style.pointerEvents = 'none';

    try {
      for (const id of ids) {
        await deleteOne(id);
      }
      window.location.reload();
    } catch (err) {
      console.error(err);
      alert(err?.message || 'Gagal menghapus arsip. Cek console/log server.');

      if(deleteBtn) deleteBtn.style.pointerEvents = '';
      updateDeleteState();

      if(cfHint) cfHint.hidden = true;
      if(cfConfirmBtn){
        cfConfirmBtn.disabled = false;
        cfConfirmBtn.innerHTML = `<i class="bi bi-trash3"></i> Ya, Hapus`;
      }
      isDeleting = false;
    }
  }

  if(deleteBtn){
    deleteBtn.addEventListener('click', function(){
      const ids = getCheckedIds();
      if(ids.length === 0){
        alert('Pilih minimal 1 arsip (atau pilih semua) untuk dihapus.');
        return;
      }
      openConfirmModal(ids);
    });
  }

  if(cfCancelBtn) cfCancelBtn.addEventListener('click', closeConfirmModal);
  if(cfCloseBtn)  cfCloseBtn.addEventListener('click', closeConfirmModal);

  if(cfModal){
    cfModal.addEventListener('click', function(e){
      const t = e.target;
      if(t && t.getAttribute && t.getAttribute('data-close') === 'true'){
        closeConfirmModal();
      }
    });
  }

  if(cfConfirmBtn){
    cfConfirmBtn.addEventListener('click', async function(){
      const ids = pendingDeleteIds.slice();
      if(ids.length === 0) return;
      await runDelete(ids);
    });
  }

  // =========================
  // checkbox events (tetap)
  // =========================
  document.addEventListener('change', function(e){
    if(e.target && e.target.classList && e.target.classList.contains('ap-row-check')){
      syncSelectAllState();
      updateEditState();
      updateDeleteState();
    }

    if(e.target && e.target.id === 'apSelectAll'){
      const visible = getVisibleRows();
      visible.forEach(row => {
        const cb = row.querySelector('.ap-row-check');
        if (cb) cb.checked = selectAll.checked;
      });
      selectAll.indeterminate = false;
      updateEditState();
      updateDeleteState();
    }
  });

  syncSelectAllState();
  updateEditState();
  updateDeleteState();

  // =========================
  // ✅ SORT NILAI KONTRAK (SERVER-SIDE)
  // =========================
  const sortBtn  = document.getElementById('sortNilaiBtn');
  const sortIcon = document.getElementById('sortNilaiIcon');

  function getSortFromUrl(){
    try{
      const u = new URL(window.location.href);
      const s = (u.searchParams.get('sort_nilai') || '').toLowerCase();
      return (s === 'asc' || s === 'desc') ? s : '';
    }catch(e){
      return '';
    }
  }

  const sortNow = getSortFromUrl();
  if(sortIcon){
    sortIcon.className =
      (sortNow === 'asc')  ? 'bi bi-sort-up' :
      (sortNow === 'desc') ? 'bi bi-sort-down-alt' :
      'bi bi-arrow-down-up';
  }

  if(sortBtn){
    sortBtn.addEventListener('click', function(){
      const u = new URL(window.location.href);
      const cur = getSortFromUrl();

      const next = (cur === '') ? 'desc' : (cur === 'desc' ? 'asc' : '');

      if(next) u.searchParams.set('sort_nilai', next);
      else u.searchParams.delete('sort_nilai');

      u.searchParams.delete('page');
      window.location.href = u.toString();
    });
  }

  // =========================
  // ✅ EXPORT EXCEL (tetap)
  // =========================
  function closeDetailModalIfOpen(){
    const modal = document.getElementById('dtModal');
    if(modal && modal.classList.contains('is-open')){
      modal.classList.remove('is-open');
      document.body.classList.remove('modal-open');
      document.body.style.overflow = '';
      modal.setAttribute('aria-hidden', 'true');
    }
  }

  function buildExportUrl(){
    const u = new URL(exportUrl, window.location.origin);

    const cur = new URL(window.location.href);
    ['status','tahun','q','sort_nilai'].forEach(k => {
      const v = cur.searchParams.get(k);
      if(v !== null && v !== '') u.searchParams.set(k, v);
    });

    u.searchParams.set('format', 'xlsx');
    u.searchParams.delete('page');

    return u.toString();
  }

  if(printBtn){
    printBtn.addEventListener('click', function(e){
      e.preventDefault();
      closeDetailModalIfOpen();
      window.location.href = buildExportUrl();
    });
  }

  // =========================
  // MODAL DETAIL (tetap sama)
  // =========================
  const modal = document.getElementById('dtModal');
  const closeBtn = document.getElementById('dtCloseBtn');

  const elTitle   = document.getElementById('dtTitle');
  const elUnit    = document.getElementById('dtUnit');
  const elTahun   = document.getElementById('dtTahun');
  const elIdRup   = document.getElementById('dtIdRup');
  const elStatus  = document.getElementById('dtStatus');
  const elRekanan = document.getElementById('dtRekanan');
  const elJenis   = document.getElementById('dtJenis');
  const elPagu    = document.getElementById('dtPagu');
  const elHps     = document.getElementById('dtHps');
  const elKontrak = document.getElementById('dtKontrak');

  const elDocNoteWrap = document.getElementById('dtDocNoteWrap');
  const elDocNote     = document.getElementById('dtDocNote');

  const docsEl = document.getElementById('dtDocList');
  const emptyEl = document.getElementById('dtDocEmpty');

  const normalizeGroups = (raw) => {
    if(!raw) return [];
    if(!Array.isArray(raw) && typeof raw === 'object'){
      return Object.keys(raw).map(k => ({ field: k, items: raw[k] }));
    }
    if(Array.isArray(raw)){
      return [{ field: 'dokumen', items: raw }];
    }
    return [];
  };

  const safeText = (s) => String(s ?? '').replace(/[<>&"]/g, (c) => ({
    '<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;'
  }[c]));

  const normalizeStorageUrl = (p) => {
    if(!p) return '#';
    if(String(p).startsWith('http')) return String(p);
    if(String(p).startsWith('/storage/')) return String(p);
    return '/storage/' + String(p).replace(/^\/+/, '');
  };

  const toPreviewUrl = (storageUrl) => {
    const u = normalizeStorageUrl(storageUrl);
    return `/file-viewer?file=${encodeURIComponent(u)}`;
  };

  const toDownloadUrl = (storageUrl) => normalizeStorageUrl(storageUrl);

  const renderDocs = (raw) => {
    if(!docsEl) return;
    docsEl.innerHTML = '';

    const groups = normalizeGroups(raw);

    let total = 0;
    groups.forEach(g => {
      const items = Array.isArray(g.items) ? g.items : [];
      total += items.length;
    });

    if(emptyEl) emptyEl.hidden = total > 0;
    if(total === 0) return;

    groups.forEach(group => {
      const items = Array.isArray(group.items) ? group.items : [];
      items.forEach(item => {

        if(typeof item === 'string'){
          const name = item.split('/').filter(Boolean).pop() || item;
          const fileUrl = normalizeStorageUrl(item);

          const previewUrl = toPreviewUrl(fileUrl);
          const downloadUrl = toDownloadUrl(fileUrl);

          const cardWrap = document.createElement('div');
          cardWrap.className = 'dt-doc-card';
          cardWrap.style.cursor = 'default';

          cardWrap.innerHTML = `
            <a href="${safeText(previewUrl)}" target="_blank" rel="noopener"
              style="display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit;flex:1;min-width:0;">
              <div class="dt-doc-ic"><i class="bi bi-file-earmark"></i></div>
              <div class="dt-doc-info">
                <div class="dt-doc-title">${safeText(name)}</div>
                <div class="dt-doc-sub">${safeText(group.field)}</div>
              </div>
            </a>

            <a class="dt-doc-act" href="${safeText(downloadUrl)}" download title="Download"
               onclick="event.stopPropagation();">
              <i class="bi bi-download"></i>
            </a>
          `;

          docsEl.appendChild(cardWrap);
          return;
        }

        const url  = item?.url || '';
        const name = item?.name || (item?.path ? String(item.path).split('/').pop() : 'Dokumen');
        const label = item?.label || group.field;
        const path  = item?.path || '';

        const previewUrl = url || (path ? toPreviewUrl(path) : '#');
        const downloadUrl = path ? toDownloadUrl(path) : (url || '#');

        const cardWrap = document.createElement('div');
        cardWrap.className = 'dt-doc-card';
        cardWrap.style.cursor = 'default';

        cardWrap.innerHTML = `
          <a href="${safeText(previewUrl)}" target="_blank" rel="noopener"
             style="display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit;flex:1;min-width:0;">
            <div class="dt-doc-ic"><i class="bi bi-file-earmark"></i></div>
            <div class="dt-doc-info">
              <div class="dt-doc-title">${safeText(name)}</div>
              <div class="dt-doc-sub">${safeText(label)}</div>
            </div>
          </a>

          <a class="dt-doc-act" href="${safeText(downloadUrl)}" download title="Download"
             onclick="event.stopPropagation();">
            <i class="bi bi-download"></i>
          </a>
        `;

        docsEl.appendChild(cardWrap);
      });
    });
  };

  function openModal(payload){
    if(!modal) return;

    if(elTitle)   elTitle.textContent   = payload.title || '-';
    if(elUnit)    elUnit.textContent    = payload.unit || '-';
    if(elTahun)   elTahun.textContent   = payload.tahun || '-';
    if(elIdRup)   elIdRup.textContent   = payload.idrup || '-';
    if(elStatus)  elStatus.textContent  = payload.status || '-';
    if(elRekanan) elRekanan.textContent = payload.rekanan || '-';
    if(elJenis)   elJenis.textContent   = payload.jenis || '-';
    if(elPagu)    elPagu.textContent    = payload.pagu || '-';
    if(elHps)     elHps.textContent     = payload.hps || '-';
    if(elKontrak) elKontrak.textContent = payload.kontrak || '-';

    try{ renderDocs(payload.docs || {}); }catch(e){ if(docsEl) docsEl.innerHTML=''; if(emptyEl) emptyEl.hidden=false; }

    if(elDocNoteWrap && elDocNote){
      const note = (payload.docnote || '').trim();
      if(note){
        elDocNote.textContent = note;
        elDocNoteWrap.hidden = false;
      }else{
        elDocNote.textContent = '-';
        elDocNoteWrap.hidden = true;
      }
    }

    modal.classList.add('is-open');
    document.body.classList.add('modal-open');
    document.body.style.overflow = 'hidden';
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeModal(){
    if(!modal) return;
    modal.classList.remove('is-open');
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    modal.setAttribute('aria-hidden', 'true');
  }

  document.addEventListener('click', function(e){
    const link = e.target.closest('.js-open-detail');
    if(!link) return;

    e.preventDefault();
    openModal({
      id: link.dataset.id,
      title: link.dataset.title,
      unit: link.dataset.unit,
      tahun: link.dataset.tahun,
      idrup: link.dataset.idrup,
      status: link.dataset.status,
      rekanan: link.dataset.rekanan,
      jenis: link.dataset.jenis,
      pagu: link.dataset.pagu,
      hps: link.dataset.hps,
      kontrak: link.dataset.kontrak,
      docnote: link.dataset.docnote,
      docs: (function(){ try{ return link.dataset.docs ? JSON.parse(link.dataset.docs) : {}; }catch(e){ return {}; } })()
    });
  });

  if(closeBtn) closeBtn.addEventListener('click', closeModal);

  if(modal){
    modal.addEventListener('click', function(e){
      const t = e.target;
      if(t && t.getAttribute && t.getAttribute('data-close') === 'true'){
        closeModal();
      }
    });
  }

  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape' && modal && modal.classList.contains('is-open')){
      closeModal();
      return;
    }
    if(e.key === 'Escape' && cfModal && cfModal.classList.contains('is-open')){
      closeConfirmModal();
      return;
    }
  });
});
</script>

</body>
</html>