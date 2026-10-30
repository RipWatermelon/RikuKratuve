<?php
require dirname(__DIR__) . '/config.php';
if (!currentUser()) {
    header('Location: register.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rīku Krātuve | Pieprasījumi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=EB+Garamond:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500;600;700&display=swap&subset=latin-ext" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="flex flex-col min-h-screen">
    <header class="site-header relative z-10 border-b border-blue-900/40 bg-slate-950/80 backdrop-blur-md">

        <nav class="site-nav max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="site-nav-brand font-title text-blue-200 tracking-wider text-lg font-semibold flex items-center gap-2"><svg class="site-nav-logo" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l8.66 5v10L12 22l-8.66-5V7z"/><path transform="translate(6.6 6.6) scale(0.45)" stroke-width="3.4" d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a2 2 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>RĪKU KRĀTUVE</div>
            <div class="site-nav-links font-serif-custom text-lg">
                <a href="inventory.php" class="nav-link text-slate-400 hover:text-blue-200 transition"><svg class="nav-link-icon" viewBox="0 0 24 24" aria-hidden="true"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>Inventārs</a>
                <a href="requests.php" class="nav-link text-blue-200 border-b border-blue-400/50 pb-0.5 hover:text-white transition"><svg class="nav-link-icon" viewBox="0 0 24 24" aria-hidden="true"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M9 12h6"/><path d="M9 16h6"/></svg>Pieprasījumi</a>
                <a href="profile.php" class="nav-link text-slate-400 hover:text-blue-200 transition"><svg class="nav-link-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Profils</a>
                <button id="logout-btn" type="button" class="nav-link text-slate-400 hover:text-red-300 transition"><svg class="nav-link-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>Iziet</button>
            </div>
        </nav>
    </header>
    <main class="flex-grow max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8">
        <div id="requests-notice" class="notice mb-4"></div>
        <section class="glow-panel rounded-xl p-6">
            <h2 class="font-inter text-2xl text-blue-100 mb-6 border-b border-blue-500/20 pb-3">Pieprasījumi</h2>
            <div id="requests-grid" class="item-grid request-grid"></div>
        </section>
        <section class="glow-panel rounded-xl p-6 mt-6">
            <h2 class="font-inter text-2xl text-blue-100  mb-6 border-b border-blue-500/20 pb-3">Gaida vērtējumu</h2>
            <div id="ratings-grid" class="item-grid request-grid"></div>
        </section>
        <section class="glow-panel rounded-xl p-6 mt-6">
            <h2 class="font-inter text-2xl text-blue-100 mb-6 border-b border-blue-500/20 pb-3">Aizņemtās mantas</h2>
            <div id="borrowed-grid" class="item-grid request-grid"></div>
        </section>
    </main>
    <div id="request-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-md opacity-0 pointer-events-none transition-opacity duration-300" aria-modal="true" role="dialog">
        <div class="glow-panel rounded-xl max-w-lg w-full mx-4 overflow-hidden relative shadow-2xl">
            <div class="modal-header flex items-center p-4 border-b border-blue-500/20 bg-slate-900/50">
                <h2 id="request-modal-title" class="font-serif-custom text-xl text-blue-100 italic">Pieprasījuma detaļas</h2>
                <button id="close-request-modal" class="w-[20%] ml-auto text-slate-400 hover:text-white transition text-sm px-2 py-1 rounded border border-slate-700 hover:border-slate-500" type="button">Aizvērt</button>
            </div>
            <div id="request-modal-body" class="p-6 space-y-4 text-sm text-slate-300"></div>
        </div>
    </div>
    <script src="../js/app.js"></script>
    <script src="../js/requests.js"></script>
</body>
</html>
