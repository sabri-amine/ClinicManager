<aside aria-label="Navigation latérale" class="w-72 bg-slate-900 text-slate-300 p-6 flex flex-col justify-between border-r border-slate-800/80 shadow-sm hidden md:flex shrink-0">
    
    <div>
        <!-- Logo / Titre de la Sidebar -->
        <div class="flex items-center gap-3 px-2 mb-8">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-white font-bold shadow-lg shadow-blue-500/30">
                CM
            </div>
            <div>
                <h2 class="text-base font-bold tracking-tight text-white">ClinicManager</h2>
                <p class="text-xs text-slate-400">Espace Administration</p>
            </div>
        </div>

        <!-- Liens de navigation -->
        <nav class="space-y-1.5 font-medium">
            <a 
                href="dashboard.php" 
                class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-600/20 transition-all font-semibold"
            >
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                Tableau de bord
            </a>

            <a 
                href="users.php" 
                class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-400 hover:bg-slate-800/80 hover:text-white transition-all"
            >
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Utilisateurs
            </a>

            <a 
                href="archive.php" 
                class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-400 hover:bg-slate-800/80 hover:text-white transition-all"
            >
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                </svg>
                Archive
            </a>
        </nav>
    </div>

    <!-- Section pied de sidebar -->
    <div class="pt-6 border-t border-slate-800/80">
        <div class="flex items-center gap-3 px-2 py-3 rounded-2xl bg-slate-800/50 border border-slate-800">
            <div class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></div>
            <span class="text-xs font-medium text-slate-400">Système opérationnel</span>
        </div>
    </div>
</aside>