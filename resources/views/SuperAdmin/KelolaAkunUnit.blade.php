<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Akun Unit - SIAPABAJA</title>
  <link rel="stylesheet" href="{{ asset('css/Unit.css') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
<div class="dash-wrap">
  <aside class="dash-sidebar">
    <div class="dash-brand">
      <div class="dash-logo">
        <img src="{{ asset('image/Logo_Unsoed.png') }}" alt="Logo">
      </div>
      <div class="dash-text">
        <div class="dash-app">SIAPABAJA</div>
        <div class="dash-role">Super Admin</div>
      </div>
    </div>

    <nav class="dash-nav">
      <a class="dash-link" href="{{ route('superadmin.dashboard') }}">
        <span class="ic"><i class="bi bi-grid-fill"></i></span>
        Dashboard
      </a>

      <a class="dash-link" href="{{ route('superadmin.arsip') }}">
        <span class="ic"><i class="bi bi-archive-fill"></i></span>
        Arsip PBJ
      </a>

      <a class="dash-link" href="{{ route('superadmin.pengadaan.create') }}">
        <span class="ic"><i class="bi bi-plus-square-fill"></i></span>
        Tambah Pengadaan
      </a>

      <a class="dash-link" href="{{ route('superadmin.kelola.menu') }}">
        <span class="ic"><i class="bi bi-gear-fill"></i></span>
        Kelola Menu
      </a>

      <div class="dash-link dash-link-parent is-open" id="kelolaAkunParent">
        <span class="ic"><i class="bi bi-person-gear"></i></span>
        Kelola Akun
        <i class="bi bi-chevron-down dash-chevron"></i>
      </div>

      <div class="dash-sub is-open" id="kelolaAkunSub">
        <a class="dash-sub-link" href="{{ route('superadmin.kelola.akun') }}">
          <span class="ic"><i class="bi bi-person-circle"></i></span>
          Kelola Akun Saya
        </a>

        <a class="dash-sub-link" href="{{ route('superadmin.kelola.akun.ppk') }}">
          <span class="ic"><i class="bi bi-person-badge-fill"></i></span>
          Kelola Akun PPK
        </a>

        <a class="dash-sub-link active" href="{{ route('superadmin.kelola.akun.unit') }}">
          <span class="ic"><i class="bi bi-people-fill"></i></span>
          Kelola Akun Unit
        </a>
      </div>
    </nav>

    <div class="dash-side-actions">
      <a class="dash-side-btn" href="{{ route('home') }}">
        <i class="bi bi-house-door"></i> Kembali
      </a>
      <a class="dash-side-btn" href="{{ url('/logout') }}">
        <i class="bi bi-box-arrow-right"></i> Keluar
      </a>
    </div>
  </aside>

  <main class="dash-main">
    <div class="dash-header-row">
  <div class="dash-header">
    <h1>Manajemen Akun Unit</h1>
    <p>Kelola akun admin untuk setiap unit kerja</p>
  </div>

  <button type="button" class="btn-add" id="btnOpenAddModal">
    <i class="bi bi-plus-lg"></i>
    Tambah PIC (Unit)
  </button>
</div>

    @if (session('success'))
      <div class="alert-success">
        {{ session('success') }}
      </div>
    @endif

    @if ($errors->any())
      <div class="alert-error">
        <ul style="margin:0; padding-left:18px;">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="table-card">
      <table>
        <thead>
          <tr>
            <th>Username</th>
            <th>Unit Kerja</th>
            <th>Email</th>
            <th>Password</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($unitAccounts as $item)
            <tr>
              <td>{{ $item['username'] ?? '-' }}</td>
              <td>{{ $item['unit_kerja'] ?? '-' }}</td>
              <td>{{ $item['email'] ?? '-' }}</td>
              <td>{{ $item['password'] ?? '********' }}</td>
              <td>
                <span class="status {{ ($item['status'] ?? 'Aktif') === 'Aktif' ? 'aktif' : 'nonaktif' }}">
                  {{ $item['status'] ?? 'Aktif' }}
                </span>
              </td>
              <td class="aksi">
                <button
                  type="button"
                  class="icon-btn btn-edit"
                  data-id="{{ $item['id'] ?? '' }}"
                  data-username="{{ $item['username'] ?? '' }}"
                  data-unit="{{ $item['unit_kerja'] ?? '' }}"
                  data-email="{{ $item['email'] ?? '' }}"
                  data-status="{{ $item['status'] ?? 'Aktif' }}"
                  title="Edit"
                >
                  <i class="bi bi-pencil-fill"></i>
                </button>

                <form action="{{ route('superadmin.kelola.akun.unit.destroy', $item['id']) }}" method="POST" class="form-delete" onsubmit="return confirm('Yakin ingin menghapus akun unit ini?')">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="icon-btn" title="Hapus">
                    <i class="bi bi-trash-fill"></i>
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="empty-cell">Belum ada data akun unit.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </main>
</div>

{{-- MODAL TAMBAH --}}
<div class="modal-backdrop" id="addModal">
  <div class="modal-card">
    <div class="modal-head">
      <h2>Tambah PIC (Unit)</h2>
      <button type="button" class="modal-close" data-close="addModal">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <div class="modal-divider"></div>

    <form action="{{ route('superadmin.kelola.akun.unit.store') }}" method="POST" class="modal-form">
      @csrf

      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" placeholder="Masukkan username" required>
      </div>

      <div class="form-group">
        <label>Unit Kerja</label>
        <input type="text" name="unit_kerja" placeholder="Masukkan unit kerja" required>
      </div>

      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" placeholder="Masukkan email" required>
      </div>

      <div class="form-group">
        <label>Password</label>
        <input type="text" name="password" placeholder="Masukkan password" required>
      </div>

      <div class="form-group">
        <label>Status</label>
        <div class="select-wrap">
          <select name="status" required>
            <option value="Aktif">Aktif</option>
            <option value="Tidak Aktif">Tidak Aktif</option>
          </select>
          <i class="bi bi-chevron-down"></i>
        </div>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn-cancel" data-close="addModal">Batal</button>
        <button type="submit" class="btn-save">Simpan</button>
      </div>
    </form>
  </div>
</div>

{{-- MODAL EDIT --}}
<div class="modal-backdrop" id="editModal">
  <div class="modal-card">
    <div class="modal-head">
      <h2>Edit PIC (Unit)</h2>
      <button type="button" class="modal-close" data-close="editModal">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <div class="modal-divider"></div>

    <form action="{{ route('superadmin.kelola.akun.unit.update', 0) }}" method="POST" class="modal-form" id="editForm">
      @csrf
      @method('PUT')

      <input type="hidden" name="id" id="edit_id">

      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" id="edit_username" placeholder="Masukkan username" required>
      </div>

      <div class="form-group">
        <label>Unit Kerja</label>
        <input type="text" name="unit_kerja" id="edit_unit_kerja" placeholder="Masukkan unit kerja" required>
      </div>

      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" id="edit_email" placeholder="Masukkan email" required>
      </div>

      <div class="form-group">
        <label>Password</label>
        <input type="text" name="password" id="edit_password" placeholder="Kosongkan jika tidak diganti">
      </div>

      <div class="form-group">
        <label>Status</label>
        <div class="select-wrap">
          <select name="status" id="edit_status" required>
            <option value="Aktif">Aktif</option>
            <option value="Tidak Aktif">Tidak Aktif</option>
          </select>
          <i class="bi bi-chevron-down"></i>
        </div>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn-cancel" data-close="editModal">Batal</button>
        <button type="submit" class="btn-save">Simpan</button>
      </div>
    </form>
  </div>
</div>

<style>
  :root{
    --sidebar:#1f5872;
    --yellow:#f6d80f;
    --main-bg:#f3f5f7;
    --text:#214f68;
  }

  *{box-sizing:border-box}

  body{
    margin:0;
    font-family:'Nunito',sans-serif;
    background:var(--main-bg);
    color:#1f2937;
  }

  .dash-sub{
  display:flex;
  flex-direction:column;
  gap:6px;
  padding-left:12px;
  margin-top:4px;
}

.dash-sub-link{
  display:flex;
  align-items:center;
  gap:10px;
  color:rgba(255,255,255,.85);
  text-decoration:none;
  padding:8px 12px;
  border-radius:8px;
  font-size:14px;
  transition:.2s;
}

.dash-sub-link:hover{
  background:rgba(255,255,255,.08);
  color:#fff;
}

.dash-sub-link.active{
  background:#f6d80f;
  color:#184f61;
  font-weight:600;
}

 .dash-header {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.dash-header h1 {
  margin: 0;
  font-size: 26px;
  font-weight: 600;
  color: #184f61;
}

.dash-header p {
  margin: 0;
  font-size: 15px;
  color: #64748b;
  font-weight: 400;
}

.dash-header-row{
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  gap:16px;
  margin-bottom:18px;
}

  .btn-add{
    border:0;
    background:var(--sidebar);
    color:#fff;
    border-radius:8px;
    padding:12px 18px;
    font-family:inherit;
    font-size:16px;
    cursor:pointer;
    display:flex;
    align-items:center;
    gap:10px;
  }

  .alert-success{
    background:#dcfce7;
    color:#166534;
    border:1px solid #bbf7d0;
    padding:12px 14px;
    border-radius:8px;
    margin-bottom:16px;
  }

  .alert-error{
    background:#fee2e2;
    color:#991b1b;
    border:1px solid #fecaca;
    padding:12px 14px;
    border-radius:8px;
    margin-bottom:16px;
  }

  .table-card{
    background:#fff;
    border-radius:10px;
    overflow:hidden;
    box-shadow:0 6px 20px rgba(0,0,0,.08);
  }

  table{
    width:100%;
    border-collapse:collapse;
  }

  thead{
    background:var(--sidebar);
    color:#fff;
  }

  th, td{
  padding:18px 16px;
  text-align:left;
  font-size:15px;
  vertical-align:middle;
}

  tbody tr{
    border-bottom:1px solid #d1d5db;
  }

  tbody td{
    color:#24526c;
  }

  .status{
    display:inline-block;
    min-width:90px;
    text-align:center;
    padding:8px 14px;
    border-radius:6px;
    font-size:14px;
  }

  .status.aktif{
    background:#a8dca1;
    color:#166534;
  }

  .status.nonaktif{
    background:#f2b4b4;
    color:#991b1b;
  }

  .aksi{
    display:flex;
    align-items:center;
    gap:14px;
  }

  .icon-btn{
    border:0;
    background:transparent;
    color:#1f5872;
    font-size:18px;
    cursor:pointer;
    padding:0;
  }

  .form-delete{
    display:inline;
    margin:0;
    padding:0;
  }

  .empty-cell{
    text-align:center;
    color:#6b7280;
    padding:28px 16px;
  }

  .modal-backdrop{
    position:fixed;
    inset:0;
    background:rgba(15, 23, 42, 0.22);
    backdrop-filter:blur(8px);
    -webkit-backdrop-filter:blur(8px);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:9999;
    padding:24px;
  }

  .modal-backdrop.show{
    display:flex;
  }

  .modal-card{
    width:100%;
    max-width:520px;
    background:#fff;
    border-radius:20px;
    box-shadow:0 18px 40px rgba(0,0,0,.18);
    padding:26px 28px;
    animation:modalPop .2s ease;
  }

  @keyframes modalPop{
    from{
      opacity:0;
      transform:scale(.96) translateY(8px);
    }
    to{
      opacity:1;
      transform:scale(1) translateY(0);
    }
  }

  .modal-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
  }

  .modal-head h2{
    margin:0;
    font-size:20px;
    color:#1f5872;
    font-weight:700;
  }

  .modal-close{
    border:0;
    background:transparent;
    color:#1f5872;
    font-size:24px;
    cursor:pointer;
    padding:0;
  }

  .modal-divider{
    height:1px;
    background:#e5e7eb;
    margin:18px 0 22px;
  }

  .modal-form{
    display:flex;
    flex-direction:column;
    gap:18px;
  }

  .form-group{
    display:flex;
    flex-direction:column;
    gap:8px;
  }

  .form-group label{
    font-size:16px;
    color:#1f5872;
    font-weight:500;
  }

  .form-group input,
  .form-group select{
    width:100%;
    height:48px;
    border:1px solid #cfd5db;
    border-radius:8px;
    padding:0 14px;
    font-size:15px;
    font-family:inherit;
    color:#24526c;
    outline:none;
    background:#fff;
  }

  .form-group input:focus,
  .form-group select:focus{
    border-color:#1f5872;
  }

  .select-wrap{
    position:relative;
  }

  .select-wrap select{
    appearance:none;
    -webkit-appearance:none;
    -moz-appearance:none;
    padding-right:42px;
  }

  .select-wrap i{
    position:absolute;
    right:16px;
    top:50%;
    transform:translateY(-50%);
    color:#1f5872;
    pointer-events:none;
    font-size:18px;
  }

  .modal-actions{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:18px;
    margin-top:4px;
  }

  .btn-cancel,
  .btn-save{
    min-width:116px;
    height:46px;
    border-radius:8px;
    font-family:inherit;
    font-size:16px;
    cursor:pointer;
  }

  .btn-cancel{
    border:1px solid #cfd5db;
    background:#fff;
    color:#1f5872;
  }

  .btn-save{
    border:0;
    background:#1f5872;
    color:#fff;
  }

  @media (max-width: 1100px){
    .dash-sidebar{width:250px;}
  }

  @media (max-width: 900px){
    .dash-wrap{flex-direction:column;}
    .dash-sidebar{width:100%;}
    .dash-main{padding:20px;}
    .page-head{flex-direction:column;align-items:stretch;}
    .modal-card{padding:24px 20px;border-radius:18px;}
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const addModal = document.getElementById('addModal');
    const editModal = document.getElementById('editModal');
    const btnOpenAddModal = document.getElementById('btnOpenAddModal');
    const editForm = document.getElementById('editForm');

    if (btnOpenAddModal) {
      btnOpenAddModal.addEventListener('click', function () {
        addModal.classList.add('show');
      });
    }

    document.querySelectorAll('[data-close]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const target = btn.getAttribute('data-close');
        const modal = document.getElementById(target);
        if (modal) modal.classList.remove('show');
      });
    });

    [addModal, editModal].forEach(function (modal) {
      if (!modal) return;
      modal.addEventListener('click', function (e) {
        if (e.target === modal) {
          modal.classList.remove('show');
        }
      });
    });

    document.querySelectorAll('.btn-edit').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const id = btn.dataset.id || '';

        document.getElementById('edit_id').value = id;
        document.getElementById('edit_username').value = btn.dataset.username || '';
        document.getElementById('edit_unit_kerja').value = btn.dataset.unit || '';
        document.getElementById('edit_email').value = btn.dataset.email || '';
        document.getElementById('edit_password').value = '';
        document.getElementById('edit_status').value = btn.dataset.status || 'Aktif';

        if (editForm && id) {
          editForm.action = "{{ url('super-admin/kelola-akun/unit') }}/" + id;
        }

        editModal.classList.add('show');
      });
    });

    const parent = document.getElementById('kelolaAkunParent');
    const sub = document.getElementById('kelolaAkunSub');

    if (parent && sub) {
      parent.addEventListener('click', function () {
        sub.style.display = (sub.style.display === 'none') ? 'flex' : 'none';
        parent.classList.toggle('is-open');
      });
    }
  });
</script>
</body>
</html>