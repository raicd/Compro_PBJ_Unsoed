<header class="nav">
  <div class="container nav-inner">

    {{-- BRAND --}}
    <a href="{{ route('landing') }}" class="brand">
      <img class="brand-logo"
           src="{{ asset('image/Logo_Unsoed.png') }}"
           alt="Logo Universitas Jenderal Soedirman">
      <span class="brand-name">SIAPABAJA</span>
    </a>

    {{-- NAVIGATION --}}
    <nav class="nav-links">

      {{-- REGULASI --}}
      <a
        href="{{ request()->routeIs('landing') ? '#regulasi' : route('landing').'#regulasi' }}"
        class="nav-link"
      >
        Regulasi
      </a>

      {{-- ARSIP PBJ (HALAMAN TERPISAH) --}}
      <a
        href="{{ route('ArsipPBJ') }}"
        class="nav-link {{ request()->routeIs('ArsipPBJ') ? 'active' : '' }}"
      >
        Arsip PBJ
      </a>

      {{-- KONTAK --}}
      <a
        href="{{ request()->routeIs('landing') ? '#kontak' : route('landing').'#kontak' }}"
        class="nav-link"
      >
        Kontak
      </a>

      {{-- GUEST --}}
      @guest
        <a class="btn btn-white" href="{{ route('login') }}">
          Masuk
        </a>
      @endguest

      {{-- AUTH --}}
      @auth
        <div class="nav-user">
          <button type="button" class="nav-user-btn" aria-label="User menu">
            <i class="bi bi-person-circle"></i>
          </button>

          <div class="nav-user-menu">
            <div class="nav-user-name">
              {{ Auth::user()->name ?? 'User' }}
            </div>

            <form action="{{ route('logout') }}" method="POST">
              @csrf
              <button type="submit" class="nav-logout">
                <i class="bi bi-box-arrow-right"></i>
                Keluar
              </button>
            </form>
          </div>
        </div>
      @endauth

    </nav>

  </div>
</header>