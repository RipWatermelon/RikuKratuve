<?php
require dirname(__DIR__) . '/config.php';
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rīku Krātuve | Reģistrācija</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=EB+Garamond:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="auth-page">
    <header class="site-header relative z-10 border-b border-blue-900/40 bg-slate-950/80 backdrop-blur-md">
        <!-- Navigation Bar -->
        <nav class="site-nav max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="site-nav-brand font-title text-blue-200 tracking-wider text-lg font-semibold flex items-center gap-2"><svg class="site-nav-logo" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l8.66 5v10L12 22l-8.66-5V7z"/><path transform="translate(6.6 6.6) scale(0.45)" stroke-width="3.4" d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a2 2 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                RĪKU KRĀTUVE
            </div>
            <div class="site-nav-links flex items-center space-x-6 font-serif-custom text-lg">
                <a href="inventory.php" class="nav-link text-slate-400 hover:text-blue-200 transition"><svg class="nav-link-icon" viewBox="0 0 24 24" aria-hidden="true"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>Inventārs</a>
                <a href="requests.php" class="nav-link text-slate-400 hover:text-blue-200 transition"><svg class="nav-link-icon" viewBox="0 0 24 24" aria-hidden="true"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M9 12h6"/><path d="M9 16h6"/></svg>Pieprasījumi</a>
                <a href="profile.php" class="nav-link text-slate-400 hover:text-blue-200 transition"><svg class="nav-link-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Profils</a>
                <button id="logout-btn" type="button" class="nav-link text-slate-400 hover:text-red-300 transition"><svg class="nav-link-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>Iziet</button>
            </div>
        </nav>
    </header>
    <main class="auth-page-main">
        <section class="auth-card" aria-labelledby="registration-title">
            <header class="auth-card-header">
                <div>
                    <h1 id="registration-title">REĢISTRĀCIJA</h1>
                </div>
            </header>

            <div class="auth-section-heading">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1M18 8v6M15 11h6"/></svg>
                <span>LIETOTĀJA DATI</span>
            </div>
            <div id="register-notice" class="notice"></div>
            <form id="register-form" class="auth-form">
                <div class="auth-field auth-field-wide">
                    <label for="username">Lietotājvārds</label>
                    <div class="auth-input-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M5 20a7 7 0 0 1 14 0"/></svg><input id="username" name="username" placeholder="Ievadi lietotājvārdu" required></div>
                </div>
                <div class="auth-field auth-field-wide">
                    <label for="email">E-pasts</label>
                    <div class="auth-input-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg><input id="email" name="email" type="email" placeholder="Ievadi e-pastu" required></div>
                </div>
                <div class="auth-field">
                    <label for="role">Loma</label>
                    <select id="role" name="role" required>
                        <option value="student">Skolēns</option>
                        <option value="teacher">Skolotājs</option>
                        <option value="admin">Administrācija</option>
                    </select>
                </div>
                <div class="auth-field">
                    <label for="password">Parole</label>
                    <div class="auth-input-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><input id="password" name="password" type="password" placeholder="Izveido paroli" required></div>
                </div>
                <div class="auth-field">
                    <label for="group_name">Grupa</label>
                    <input id="group_name" name="group_name" placeholder="Piemēram: 10.A">
                </div>
                <div class="auth-field">
                    <label for="employee_code">Darbinieka kods</label>
                    <input id="employee_code" name="employee_code" placeholder="Piemēram: T-101">
                </div>
                <button class="auth-secondary-button" type="submit">IZVEIDOT KONTU</button>
            </form>

            <div class="auth-divider"><span>vai</span></div>
            <div class="auth-section-heading">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg>
                <span>PIESLĒGTIES</span>
            </div>
            <div id="login-notice" class="notice"></div>
            <form id="login-form" class="auth-form auth-login-form">
                <div class="auth-field auth-field-wide">
                    <label for="login-username">Lietotājvārds</label>
                    <div class="auth-input-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M5 20a7 7 0 0 1 14 0"/></svg><input id="login-username" name="username" placeholder="Ievadi lietotājvārdu" required></div>
                </div>
                <div class="auth-field auth-field-wide">
                    <label for="login-password">Parole</label>
                    <div class="auth-input-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><input id="login-password" name="password" type="password" placeholder="Ievadi paroli" required></div>
                </div>
                <button class="auth-primary-button" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1M17 8v6M14 11h6"/></svg>PIESLĒGTIES</button>
            </form>
            <p class="auth-admin-note">ⓘ Default admin konts: <strong>admin / admin123</strong></p>
        </section>
    </main>
    <script src="../js/app.js"></script>
</body>
</html>
