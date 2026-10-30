<?php
require dirname(__DIR__) . '/config.php';
$user = currentUser();
if (!$user) {
    header('Location: register.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rīku Krātuve</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=EB+Garamond:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500;600;700&display=swap&subset=latin-ext" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --bg-dark: #070a14;
            --panel-bg: rgba(13, 22, 41, 0.75);
            --panel-border: rgba(147, 197, 253, 0.25);
            --glow-color: rgba(147, 197, 253, 0.8);
            --glow-spread: rgba(147, 197, 253, 0.15);
            --text-serif: 'Cinzel', Georgia, serif;
            --font-title: 'Cinzel', serif;
        }

        body {
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(circle at 50% 0%, rgba(30, 58, 138, 0.35) 0%, transparent 70%),
                radial-gradient(circle at 100% 100%, rgba(15, 23, 42, 0.8) 0%, transparent 50%),
                radial-gradient(circle at 0% 50%, rgba(14, 116, 144, 0.15) 0%, transparent 40%);
            background-attachment: fixed;
            color: #cbd5e1;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        /* Glowing Panel Framing */
        .glow-panel {
            background: var(--panel-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--panel-border);
            box-shadow: 
                0 0 20px -5px var(--glow-spread),
                inset 0 0 15px -5px rgba(147, 197, 253, 0.08);
            position: relative;
        }

        .glow-panel-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .glow-panel-hover:hover {
            border-color: rgba(147, 197, 253, 0.6);
            box-shadow: 
                0 0 25px 2px rgba(147, 197, 253, 0.25),
                inset 0 0 20px -3px rgba(147, 197, 253, 0.15);
            transform: translateY(-2px);
        }
        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(7, 10, 20, 0.6);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(147, 197, 253, 0.2);
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(147, 197, 253, 0.4);
        }

        /* Form Input Styling */
        input, select, textarea {
            background: rgba(15, 23, 42, 0.7) !important;
            border: 1px solid rgba(147, 197, 253, 0.2) !important;
            color: #f1f5f9 !important;
            transition: all 0.2s ease;
        }
        input:focus, select:focus, textarea:focus {
            outline: none !important;
            border-color: rgba(147, 197, 253, 0.6) !important;
            box-shadow: 0 0 12px rgba(147, 197, 253, 0.2) !important;
        }
    </style>
</head>
<body class="flex flex-col min-h-screen">

    <!-- Glowing Top Header -->
    <header class="site-header relative z-10 border-b border-blue-900/40 bg-slate-950/80 backdrop-blur-md">
        <!-- Title Banner -->
        <div class="site-title-banner py-6 text-center relative border-b border-blue-500/10">
            <div class="inline-block relative px-8 py-2">
                <h1 class="font-title text-3xl md:text-4xl tracking-widest text-blue-100 glow-text-bright font-normal">
                    rīku krātuve
                </h1>
                <p class="font-serif-custom italic text-blue-300/70 text-sm mt-1">
                    Rīku un mantu koplietošanas platforma
                </p>
            </div>
        </div>

        <!-- Navigation Bar -->
        <nav class="site-nav max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="site-nav-brand font-title text-blue-200 tracking-wider text-lg font-semibold flex items-center gap-2"><svg class="site-nav-logo" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l8.66 5v10L12 22l-8.66-5V7z"/><path transform="translate(6.6 6.6) scale(0.45)" stroke-width="3.4" d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                RĪKU KRĀTUVE
            </div>
            <div class="site-nav-links flex items-center space-x-6 font-serif-custom text-lg">
                <a href="inventory.php" class="nav-link text-blue-200 border-b border-blue-400/50 pb-0.5 hover:text-white transition"><svg class="nav-link-icon" viewBox="0 0 24 24" aria-hidden="true"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>Inventārs</a>
                <a href="requests.php" class="nav-link text-slate-400 hover:text-blue-200 transition"><svg class="nav-link-icon" viewBox="0 0 24 24" aria-hidden="true"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M9 12h6"/><path d="M9 16h6"/></svg>Pieprasījumi</a>
                <a href="profile.php" class="nav-link text-slate-400 hover:text-blue-200 transition"><svg class="nav-link-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Profils</a>
                <button id="logout-btn" type="button" class="nav-link text-slate-400 hover:text-red-300 transition"><svg class="nav-link-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>Iziet</button>
            </div>
        </nav>
    </header>

    <!-- Main Content Layout -->
    <main class="flex-grow max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- Left Sidebar Section -->
            <aside class="lg:col-span-4 space-y-6">
                <div class="glow-panel rounded-xl p-6 relative">
                    <h2 class=" text-2xl text-blue-100 mb-4 border-b border-blue-500/20 pb-2">
                        Pievienot mantu
                    </h2>

                    <div id="inventory-notice" class="notice mb-4 text-sm text-blue-300 bg-blue-950/50 p-3 rounded border border-blue-500/20 hidden"></div>

                    <form id="add-item-form" class="space-y-4">
                        <div>
                            <label for="item-name" class="block text-xs uppercase tracking-wider text-slate-400 mb-1 font-medium">
                                Nosaukums
                            </label>
                            <input id="item-name" name="name" required class="w-full px-3 py-2 rounded-lg text-sm" placeholder="piem. Grāmata">
                        </div>

                        <div>
                            <label for="item-category" class="block text-xs uppercase tracking-wider text-slate-400 mb-1 font-medium">
                                Kategorija
                            </label>
                            <select id="item-category" name="category" required class="w-full px-3 py-2 rounded-lg text-sm">
                                <option value="Grāmatas">Grāmatas</option>
                                <option value="Instrumenti">Instrumenti</option>
                                <option value="Klēpjdatori">Klēpjdatori</option>
                                <option value="Piederumi">Piederumi</option>
                                <option value="Laboratorija">Laboratorija</option>
                            </select>
                        </div>

                        <div>
                            <label for="item-owner-type" class="block text-xs uppercase tracking-wider text-slate-400 mb-1 font-medium">
                                Īpašnieks
                            </label>
                            <select id="item-owner-type" name="owner_type" class="w-full px-3 py-2 rounded-lg text-sm">
                                <option value="user">Lietotājs</option>
                                <option value="school">Skola</option>
                            </select>
                        </div>

                        <div>
                            <label for="item-location" class="block text-xs uppercase tracking-wider text-slate-400 mb-1 font-medium">
                                Atrašanās vieta
                            </label>
                            <select id="item-location" name="location" class="w-full px-3 py-2 rounded-lg text-sm">
                                <option value="">Izvēlieties kabinetu</option>
                                <?php foreach ([100, 200, 300] as $start): ?>
                                    <?php for ($cabinet = $start; $cabinet <= $start + 30; $cabinet++): ?>
                                        <option value="<?= $cabinet ?>"><?= $cabinet ?></option>
                                    <?php endfor; ?>
                                <?php endforeach; ?>
                                <?php for ($cabinet = 100; $cabinet <= 130; $cabinet++): ?>
                                    <option value="V<?= $cabinet ?>">V<?= $cabinet ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div>
                            <label for="item-description" class="block text-xs uppercase tracking-wider text-slate-400 mb-1 font-medium">
                                Apraksts
                            </label>
                            <textarea id="item-description" name="description" rows="3" class="w-full px-3 py-2 rounded-lg text-sm" placeholder="Rīka stāvoklis, lietošanas noteikumi..."></textarea>
                        </div>

                        <button type="submit" class="w-full py-2.5 px-4 mt-2 bg-blue-900/40 hover:bg-blue-800/60 border border-blue-400/40 hover:border-blue-300 text-blue-100 text-lg rounded-lg transition shadow-lg shadow-blue-950/50 flex items-center justify-center gap-2">
                            Pievienot mantu
                        </button>
                    </form>
                </div>
            </aside>

            <!-- Main Inventory Grid Section -->
            <section class="lg:col-span-8 space-y-6">
                <div class="glow-panel rounded-xl p-6 relative">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 border-b border-blue-500/20 pb-4">
                        <h2 class="text-2xl text-blue-100">
                            Inventārs
                        </h2>

                        <!-- Search and Filter Controls -->
                        <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                            <input id="search-input" type="search" placeholder="Meklēt" class="w-full sm:w-48 px-3 py-1.5 rounded-lg text-sm">
                            <select id="category-filter" class="w-full sm:w-auto px-3 py-1.5 rounded-lg text-sm">
                                <option value="all">Visas kategorijas</option>
                                <option value="Grāmatas">Grāmatas</option>
                                <option value="Instrumenti">Instrumenti</option>
                                <option value="Klēpjdatori">Klēpjdatori</option>
                                <option value="Piederumi">Piederumi</option>
                                <option value="Laboratorija">Laboratorija</option>
                            </select>
                        </div>
                    </div>

                    <!-- Three-column dynamic grid output (populated by app.js) -->
                    <div id="inventory-grid" class="grid grid-cols-3 gap-4 item-grid">
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Detailed Modal View -->
    <div id="item-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-md opacity-0 pointer-events-none transition-opacity duration-300" aria-modal="true" role="dialog">
        <div class="glow-panel rounded-xl max-w-lg w-full mx-4 overflow-hidden relative shadow-2xl">
            <div class="modal-header flex items-center  p-4 border-b border-blue-500/20 bg-slate-900/50">
                <h2 class="text-xl text-blue-100">Rīka detaļas</h2>
                <button id="close-item-modal" class="w-[20%] ml-auto text-slate-400 hover:text-white transition text-sm px-2 py-1 rounded border border-slate-700 hover:border-slate-500" type="button">Aizvērt</button>
            </div>
            <div id="item-modal-body" class="p-6 space-y-4 text-sm text-slate-300">
                <!-- Modal content dynamically injected -->
            </div>
        </div>
    </div>

    <!-- Application JavaScript File -->
    <script src="../js/app.js"></script>
</body>
</html>