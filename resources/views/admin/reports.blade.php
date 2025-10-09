{{-- resources/views/admin/rapports.blade.php --}}
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Rapports et Analyses — Admin</title>

  <!-- Bootstrap local -->
  <link rel="stylesheet" href="{{ asset('bootstrap/css/bootstrap.min.css') }}">

  <link rel="stylesheet" href="{{ asset('bootstrap/icons/bootstrap-icons.css') }}">

  <script src="{{ asset('js/chart.umd.min.js') }}"></script>

  <!-- Styles sur-mesure pour coller au rendu -->
<style>
  :root {
    --accent: #ff7a00;
    --bg: #fbefe9;
    --card: #ffffff;
    --muted: #9b8f8a;
    --radius: 14px;
  }

  body {
    background: linear-gradient(180deg, #fbefe9 0%, #f9eae4 100%);
    font-family: 'Poppins', Arial, sans-serif;
    color: #2b2b2b;
    padding: 24px;
  }

  .container-dashboard { max-width: 1200px; margin: 0 auto; }
  h4.page-title { font-weight: 700; margin-bottom: 6px; }
  p.sub { color: var(--muted); margin-bottom: 18px; }

  /* ---- NAVIGATION ---- */
  .nav-top {
    display: flex; gap: 18px; justify-content: center; margin-bottom: 24px;
  }
  .nav-top a {
    padding: 8px 18px; border-radius: 10px; text-decoration: none;
    transition: all 0.2s ease;
  }

  /* Menu actif */
  .nav-top a.active {
    border: 1px solid var(--accent);
    background: #fff;
    color: #2b2b2b;
  }

  /* Menu inactif */
  .nav-top a.inactive {
    background: #f3f3f3;
    color: #6f6f6f;
  }

  /* Hover sur menu inactif */
  .nav-top a.inactive:hover {
    background: #ffe7cc;
    color: var(--accent);
  }

  /* ---- CARTES ET BLOCS ---- */
  .card-rounded {
    background: var(--card);
    border-radius: var(--radius);
    border: none;
    box-shadow: 0 4px 18px rgba(0,0,0,0.04);
  }
  .card-section { padding: 18px; }

  /* ---- STATISTIQUES ---- */
  .stat-box {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
    padding: 18px;
    transition: transform 0.2s ease;
  }

  /* Effet de survol des cases statistiques */
  /* .stat-box:hover {
    transform: translateY(-3px);
  } */

  .icon-box {
    width: 48px; height: 48px; border-radius: 10px;
    background: #fff4eb; display: flex; align-items: center;
    justify-content: center; color: var(--accent); margin-right: 12px;
  }

  .stat-box h6 { font-weight: 600; color: #7a6a64; margin-bottom: 4px; }
  .stat-box .value { font-weight: 700; font-size: 1.2rem; color: #000; }

  /* ---- BLOCS COMMANDES ---- */
  .order-box {
    background: #f9f9f9;
    border-radius: 10px;
    padding: 12px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background 0.3s ease;
  }

  /* Effet hover sur blocs de commandes */
  /* .order-box:hover {
    background: #fff3eb;
  } */

  .order-icon {
    width: 42px; height: 42px; border-radius: 8px;
    background: #fff4eb; display: flex; align-items: center;
    justify-content: center; color: var(--accent); font-size: 18px;
  }

  /* Badge pourcentages */
  .pct-badge {
    display: inline-block;
    padding: 3px 10px;
    font-size: 13px;
    font-weight: 600;
    color: #ffffff;
    background: #ff0000;
    border-radius: 10px;
  }

  /* ---- LISTE ARTICLES ---- */
  .article-item {
    background: #fdfcfc;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 10px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .rank-circle {
    width: 34px; height: 34px; border-radius: 50%;
    background: #fff4eb; display: flex; align-items: center;
    justify-content: center; color: var(--accent); font-weight: 600;
  }

  .badge-type {
    display: inline-block;
    padding: 2px 10px;
    font-size: 12px;
    border-radius: 12px;
    background: #fff4eb;
    color: var(--accent);
  }

  .badge-type.boisson {
    background: #ffeaea;
    color: #ff4d4d;
  }

  .legend-color {
    display: inline-block;
    width: 14px;
    height: 14px;
    border-radius: 3px;
    margin-right: 6px;
  }

  /* ---- BOUTON DECONNEXION ---- */
  .logout-fixed {
    position: fixed;
    top: 15px;
    right: 20px;
    z-index: 1000;
  }

  .btn-ghost {
    background: #f7f6f6;
    padding: 10px 14px;
    border-radius: 8px;
    border: none;
    font-weight: bold;
    transition: 0.3s;
  }

  /* Effet hover bouton déconnexion */
  .btn-ghost:hover {
    background: #fff3eb;
    color: red;
    cursor: pointer;
  }

  .section-title { font-weight: 700; margin-bottom: 1rem; }
  .text-orange { color: var(--accent); }

  /* Scrollbar interne (pour Top 5) */
  .card-section::-webkit-scrollbar { width: 6px; }
  /* .card-section::-webkit-scrollbar-thumb {
    background-color: #ccc;
    border-radius: 1px;
  } */

/* Date fixe en haut à gauche */
.date-fixed {
  position: absolute;
  top: 15px;
  left: 20px;
  z-index: 1000;
}

</style>

</head>
<body>
  <div class="container container-dashboard">
    <!-- 🗓 Date du jour en haut à gauche -->
    <div class="date-fixed text-muted small fw-bold">
    {{-- {{ \Carbon\Carbon::now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }}
      {{ ucfirst(\Carbon\Carbon::now()->locale('fr')->isoFormat('dddd D MMMM YYYY')) }} --}}
      {{ \Illuminate\Support\Str::title(\Carbon\Carbon::now()->locale('fr')->isoFormat('dddd D MMMM YYYY')) }}

    </div>
    {{-- ====== HEADER ET DÉCONNEXION ====== --}}
    <div class="mb-3 d-flex justify-content-center">
      <div class="small-muted">
        Responsable: <span class="fw-bold">{{ session('admin_nom') ?? 'Admin' }}</span>
      </div>
      <form method="post" action="{{ route('admin.logout') }}" class="logout-fixed">
        @csrf
        <button class="btn-ghost" type="submit">
          <i class="bi bi-box-arrow-right me-1"></i> Se Déconnecter
        </button>
      </form>
    </div>

    {{-- ====== MENU DE NAVIGATION ====== --}}
    <div class="nav-top mb-3 d-flex justify-content-center">
      <a href="/admin/vue" class="inactive">Vue d'ensemble</a>
      <a href="/admin/commandes" class="inactive">Commandes</a>
      <a href="/admin/menus" class="inactive">Menu</a>
      <a class="active" href="{{ route('admin.reports') }}">Rapports</a>
    </div>

    {{-- ====== EN-TÊTE ====== --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h4 class="page-title">Rapports et Analyses</h4>
        <p class="sub">
          Analyses basées sur <span class="fw-bold">0 commandes terminées</span>
        </p>
      </div>
    </div>

    {{-- ====== STATISTIQUES HAUTES ====== --}}
    @php
      $stats = [
        ['icon' => 'bi bi-cash-coin', 'title' => "Chiffre d’affaires", 'value' => '0.00€', 'color' => '#ff7a00'],
        ['icon' => 'bi bi-basket2-fill', 'title' => "Commandes totales", 'value' => '0', 'color' => 'red'],
        ['icon' => 'bi bi-graph-up-arrow', 'title' => "Panier moyen", 'value' => '0.00€', 'color' => 'rgb(194, 194, 6)'],
        ['icon' => 'bi bi-clock-history', 'title' => "Temps moyen", 'value' => '0min', 'color' => 'black'],
      ];
    @endphp

    <div class="row g-3 mb-4">
      @foreach($stats as $s)
        <div class="col-md-3">
          <div class="stat-box card-rounded d-flex align-items-center p-3">
            <div class="icon-box me-3" style="color: {{ $s['color'] }}">
              <i class="{{ $s['icon'] }} fs-4"></i>
            </div>
            <div>
              <h6 class="mb-1 fw-semibold text-muted">{{ $s['title'] }}</h6>
              <div class="value fw-bold">{{ $s['value'] }}</div>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    {{-- ====== RÉPARTITION DES COMMANDES + TOP ARTICLES ====== --}}
    <div class="row mb-4 g-3 align-items-stretch">
      <!-- Répartition des commandes -->
      <div class="col-md-6">
        <div class="card-rounded card-section p-3 h-100">
          <div class="d-flex align-items-center mb-3">
            <i class="bi bi-bar-chart me-2 fs-5" style="color:black"></i>
            <h5 class="mb-0 fw-bold">Répartition des commandes</h5>
          </div>

          <div class="order-box mb-3">
            <div class="d-flex align-items-center">
              <div class="order-icon me-3"><i class="bi bi-shop"></i></div>
              <div>
                <div class="fw-semibold">Sur place</div>
                <div class="text-muted small">0 commandes</div>
              </div>
            </div>
            <span class="pct-badge">0%</span>
          </div>

          <div class="order-box">
            <div class="d-flex align-items-center">
              <div class="order-icon me-3"><i class="bi bi-truck" style="color:red"></i></div>
              <div>
                <div class="fw-semibold">Livraison</div>
                <div class="text-muted small">0 commandes</div>
              </div>
            </div>
            <span class="pct-badge">0%</span>
          </div>
        </div>
      </div>

      <!-- Top 5 des articles -->
      <div class="col-md-6">
        <div class="card-rounded card-section h-100 p-3" style="max-height: 280px; overflow-y: auto;">
          <div class="d-flex align-items-center mb-3">
            <i class="bi bi-graph-up-arrow me-2 fs-5" style="color:black"></i>
            <h5 class="mb-0 fw-bold">Top 5 des articles</h5>
          </div>

          <canvas id="topArticlesChart" width="700" height="160"></canvas>
          <script>
            const ctx = document.getElementById('topArticlesChart').getContext('2d');
            new Chart(ctx, {
              type: 'bar',
              data: {
                labels: ['Pizza', 'Burger', 'Coca', 'Pâtes', 'Eau'],
                datasets: [
                  { label: 'Repas', data: [120, 95, 0, 80, 0], backgroundColor: '#ff7a00', borderRadius: 8 },
                  { label: 'Boissons', data: [0, 0, 45, 0, 30], backgroundColor: '#ff4d4d', borderRadius: 8 }
                ]
              },
              options: {
                plugins: { legend: { display: false } },
                scales: {
                  y: { stacked: true, beginAtZero: true, grid: { color: '#f0f0f0' } },
                  x: { stacked: true, grid: { display: false } }
                }
              }
            });
          </script>

          <!-- Légende -->
          <div class="d-flex justify-content-center gap-4 mt-3 mb-3">
            <div class="d-flex align-items-center">
              <span class="legend-color" style="background:#ff7a00;"></span>
              <small>Repas</small>
            </div>
            <div class="d-flex align-items-center">
              <span class="legend-color" style="background:#ff4d4d;"></span>
              <small>Boissons</small>
            </div>
          </div>

          <!-- Liste d’articles -->
          @php
            $articles = [
              ['rank'=>1,'name'=>'Pizza','sold'=>15,'orders'=>12,'price'=>'120.00€','type'=>'repas'],
              ['rank'=>2,'name'=>'Burger','sold'=>12,'orders'=>10,'price'=>'95.00€','type'=>'repas'],
              ['rank'=>3,'name'=>'Coca','sold'=>20,'orders'=>15,'price'=>'45.00€','type'=>'boisson'],
              ['rank'=>4,'name'=>'Pâtes','sold'=>10,'orders'=>8,'price'=>'80.00€','type'=>'repas'],
              ['rank'=>5,'name'=>'Eau','sold'=>25,'orders'=>18,'price'=>'30.00€','type'=>'boisson'],
            ];
          @endphp

          @foreach($articles as $a)
            <div class="article-item">
              <div class="d-flex align-items-center">
                <div class="rank-circle" style="color:black">{{ $a['rank'] }}</div>
                <div class="ms-3">
                  <div class="fw-bold">{{ $a['name'] }}</div>
                  <small class="text-muted">{{ $a['sold'] }} vendus • {{ $a['orders'] }} commandes</small>
                </div>
              </div>
              <div class="text-end">
                <div class="fw-bold">{{ $a['price'] }}</div>
                <span class="badge-type {{ $a['type'] == 'boisson' ? 'boisson' : '' }}">{{ $a['type'] }}</span>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>

    {{-- ====== STATISTIQUES DÉTAILLÉES ====== --}}
    <div class="card-rounded card-section p-4">
      <h5 class="mb-4 fw-bold section-title">Statistiques détaillées</h5>

      <div class="row">
        <div class="col-md-4">
          <h6 class="fw-bold mb-3 text-muted">Commandes par statut</h6>
          <div class="d-flex justify-content-between mb-2"><span>Terminées :</span><span class="fw-semibold">0</span></div>
          <div class="d-flex justify-content-between mb-2"><span>Livrées :</span><span class="fw-semibold">0</span></div>
          <div class="d-flex justify-content-between mb-2"><span>En cours :</span><span class="fw-semibold">0</span></div>
          <div class="d-flex justify-content-between mb-2"><span>Prêtes :</span><span class="fw-semibold">0</span></div>
        </div>

        <div class="col-md-4">
          <h6 class="fw-bold mb-3 text-muted">Analyse des revenus</h6>
          <div class="d-flex justify-content-between mb-2"><span>Revenus sur place :</span><span class="fw-semibold">0.00€</span></div>
          <div class="d-flex justify-content-between mb-2"><span>Revenus livraison :</span><span class="fw-semibold">0.00€</span></div>
        </div>

        <div class="col-md-4">
          <h6 class="fw-bold mb-3 text-muted">Performance menu</h6>
          <div class="d-flex justify-content-between mb-2"><span>Articles repas vendus :</span><span class="fw-semibold">0</span></div>
          <div class="d-flex justify-content-between mb-2"><span>Boissons vendues :</span><span class="fw-semibold">0</span></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS local -->
  <script src="{{ asset('bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
