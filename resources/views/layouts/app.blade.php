<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Tableau de bord' }} — CLINIQUE FAME</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,440;9..144,520;9..144,600&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/fame.css?v=6">
    @livewireStyles
    <style>
        .logout-link{
            display:flex; align-items:center; gap:8px; margin-top:10px; padding:8px 8px;
            font-size:12px; color:#B7A79E; cursor:pointer; border:none; background:none;
            font-family:inherit; width:100%; text-align:left; border-radius:8px;
        }
        .logout-link:hover{ color:#fff; background:rgba(255,255,255,0.05); }
    </style>
</head>
<body>
<div class="backdrop" id="backdrop"></div>
<div class="app">
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <div>
                <div class="brand-name">CLINIQUE FAME</div>
                <div class="brand-sub">Centre Médical Spécialisé</div>
            </div>
            <button class="sidebar-close" id="sidebarClose">✕</button>
        </div>
        <div class="nav-section-label">Général</div>
        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <span class="ic">◆</span> Tableau de bord
        </a>
        @if(auth()->user()?->canAccessAppointments())
        <a href="{{ route('rdv') }}" class="nav-item {{ request()->routeIs('rdv') ? 'active' : '' }}">
            <span class="ic">📅</span> Rendez-vous
        </a>
        @endif
        @can('viewAny', App\Models\Patient::class)
        <a href="{{ route('patients') }}" class="nav-item {{ request()->routeIs('patients') ? 'active' : '' }}">
            <span class="ic">🗂</span> Dossiers patients
        </a>
        @endcan
        @if(count(auth()->user()?->readableStockDomains() ?? []) > 0)
        <div class="nav-section-label">Stocks</div>
        <a href="{{ route('stocks') }}" class="nav-item {{ request()->routeIs('stocks') ? 'active' : '' }}">
            <span class="ic">💊</span> Stocks
        </a>
        @endif
        <div class="sidebar-foot">
            <div class="user-chip">
                <div class="avatar">{{ strtoupper(substr(auth()->user()->name ?? '?', 0, 1)) }}</div>
                <div>
                    <div class="user-name">{{ auth()->user()->name ?? 'Invité' }}</div>
                    <div class="user-role">{{ auth()->user()->role ?? '' }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-link">↩ Se déconnecter</button>
            </form>
        </div>
    </aside>

    <div class="main">
        <div class="topbar">
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="icon-btn hamburger" id="hamburger">☰</div>
                <div class="search">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Rechercher une patiente, un produit…
                </div>
            </div>
            <div class="top-actions">
                <div class="icon-btn">✉</div>
                <div class="notif-wrap" id="notifWrap">
                    <div class="icon-btn" id="notifBtn">🔔<span class="ping"></span></div>
                    <div class="notif-panel">
                        <div class="notif-head">
                            <h3 class="serif">Notifications</h3>
                            <span class="mark">Tout marquer comme lu</span>
                        </div>
                        <div class="notif-list">
                            @forelse($notifications ?? [] as $notif)
                                <div class="notif-item">
                                    <div class="notif-ico {{ $notif['type'] }}">{{ $notif['icon'] }}</div>
                                    <div class="notif-body">
                                        <div class="notif-title">{{ $notif['title'] }}</div>
                                        <div class="notif-desc">{{ $notif['desc'] }}</div>
                                        <div class="notif-time">{{ $notif['time'] }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="notif-item">
                                    <div class="notif-body">
                                        <div class="notif-title">Aucune notification</div>
                                        <div class="notif-desc">Tu es à jour 🎉</div>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="user-chip" style="background:var(--stone);padding:6px 12px 6px 6px;border-radius:999px;">
                    <div class="avatar">{{ strtoupper(substr(auth()->user()->name ?? '?', 0, 1)) }}</div>
                </div>
            </div>
        </div>
        <div class="content">
            <div class="flash-toast" x-data="{ show: false, message: '' }" x-cloak
                 x-show="show" x-transition
                 x-on:toast.window="message = $event.detail.message; show = true; setTimeout(() => show = false, 4500)">
                <div class="flash-toast-icon">✓</div>
                <div class="flash-toast-text" x-text="message"></div>
                <button class="flash-toast-close" @click="show = false">✕</button>
            </div>
            {{ $slot }}
        </div>
    </div>
</div>

@livewireScripts
<div class="expired-toast" id="pageExpiredToast">
    <div class="expired-toast-icon">⏳</div>
    <div>
        <div class="expired-toast-title">Ta session a expiré</div>
        <div class="expired-toast-desc">Pour continuer en toute sécurité, recharge la page.</div>
    </div>
    <button class="expired-toast-btn" onclick="location.reload()">Recharger</button>
</div>
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.hook('request', ({ fail }) => {
            fail(({ status, preventDefault }) => {
                if (status === 419) {
                    preventDefault();
                    document.getElementById('pageExpiredToast')?.classList.add('show');
                }
            });
        });
    });
</script>
<script>
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('backdrop');
    const hamburger = document.getElementById('hamburger');
    const sidebarClose = document.getElementById('sidebarClose');
    function openDrawer(){ sidebar.classList.add('open'); backdrop.classList.add('show'); }
    function closeDrawer(){ sidebar.classList.remove('open'); backdrop.classList.remove('show'); }
    hamburger && hamburger.addEventListener('click', openDrawer);
    sidebarClose && sidebarClose.addEventListener('click', closeDrawer);
    backdrop && backdrop.addEventListener('click', closeDrawer);

    const notifWrap = document.getElementById('notifWrap');
    const notifBtn = document.getElementById('notifBtn');
    notifBtn && notifBtn.addEventListener('click', (e) => { e.stopPropagation(); notifWrap.classList.toggle('open'); });
    document.addEventListener('click', () => notifWrap && notifWrap.classList.remove('open'));

    function animateCount(el){
        const target = parseFloat(el.dataset.count);
        const suffix = el.dataset.suffix || '';
        const dur = 800; const start = performance.now();
        function tick(now){
            const p = Math.min(1, (now - start) / dur);
            const eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(target * eased) + suffix;
            if (p < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    }
    function runEntranceAnimations(){
        document.querySelectorAll('.value[data-count]').forEach(animateCount);
        document.querySelectorAll('.domain-fill[data-w]').forEach(el => {
            requestAnimationFrame(() => el.style.width = el.dataset.w + '%');
        });
    }
    document.addEventListener('DOMContentLoaded', runEntranceAnimations);
    document.addEventListener('livewire:navigated', runEntranceAnimations);
    document.addEventListener('livewire:morphed', runEntranceAnimations);
</script>
</body>
</html>
