<?php
session_start();
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: ../login/login.php'); 
    exit();
}

require_once __DIR__ . '/../../db.php';

// Traitement du changement de statut du rendez-vous
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if ($id && $id > 0) {
        $stmt = $pdo->prepare(
            'SELECT is_confirmed FROM "rendez_vous" WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($appointment) {
            $currentStatus = in_array(
                $appointment['is_confirmed'],
                [true, 1, '1', 't', 'true'],
                true
            );

            $newStatus = !$currentStatus;

            $update = $pdo->prepare(
                'UPDATE "rendez_vous"
                 SET is_confirmed = :status
                 WHERE id = :id'
            );

            $update->bindValue(':status', $newStatus, PDO::PARAM_BOOL);
            $update->bindValue(':id', $id, PDO::PARAM_INT);
            $update->execute();
        }
    }

    header('Location: ./doctor.php');
    exit;
}
?>
<!DOCTYPE html>
<html :class="{ 'theme-dark': dark }" x-data="data()" lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Doctor Dashboard - Medic</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script
      src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js"
      defer
    ></script>
    <script src="../../assets/js/init-alpine.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .dashboard-card {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .dashboard-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px -5px rgba(124, 58, 237, 0.1), 0 8px 10px -6px rgba(124, 58, 237, 0.1);
        }
    </style>
  </head>
  <body class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-100 antialiased h-full">
    <div
      class="flex h-screen overflow-hidden"
      :class="{ 'overflow-hidden': isSideMenuOpen }"
    >
      <!-- Desktop sidebar -->
      <aside class="z-20 hidden w-72 overflow-y-auto bg-white dark:bg-gray-800 md:block flex-shrink-0 border-r border-gray-100 dark:border-gray-700/60 shadow-xs">
        <div class="py-6 px-6">
          <a class="text-indigo-600 dark:text-indigo-400 font-bold flex items-center gap-3 text-2xl tracking-tight mb-8">
            <img src="../../assets/images/logo.svg" alt="Logo" width="38" class="drop-shadow-xs">Medic
          </a>
          
          <nav class="space-y-1.5">
            <!-- Dashboard Link -->
            <div class="relative px-3 py-2.5 rounded-xl bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 font-semibold flex items-center gap-3">
              <span class="absolute inset-y-0 left-0 w-1 bg-purple-600 rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
              <svg class="w-5 h-5 shrink-0" aria-hidden="true" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
              </svg>
              <span>Dashboard</span>
            </div>

            <!-- Form de dossier -->
            <a class="px-3 py-2.5 rounded-xl text-gray-600 dark:text-gray-400 font-medium hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-gray-200 transition-colors duration-150 flex items-center gap-3" href="../doctor/form.php">
              <svg class="w-5 h-5 shrink-0" aria-hidden="true" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
              </svg>
              <span>Form de dossier</span>
            </a>

            <!-- All Rendez-Vous -->
            <a class="card-rendez-vous cursor-pointer px-3 py-2.5 rounded-xl text-gray-600 dark:text-gray-400 font-medium hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-gray-200 transition-colors duration-150 flex items-center gap-3">
              <svg class="w-5 h-5 shrink-0" aria-hidden="true" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                <path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
              </svg>
              <span>All Rendez-Vous</span>
            </a>

            <!-- All Dossier Medical -->
            <a class="card_dossier_medical cursor-pointer px-3 py-2.5 rounded-xl text-gray-600 dark:text-gray-400 font-medium hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-gray-200 transition-colors duration-150 flex items-center gap-3">
              <svg class="w-5 h-5 shrink-0" aria-hidden="true" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                <path d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                <path d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
              </svg>
              <span>All Dossier Medical</span>
            </a>
          </nav>
        </div>
      </aside>

      <!-- Main container -->
      <div class="flex flex-col flex-1 w-full overflow-hidden">
        
        <!-- Header -->
        <header class="z-10 py-3.5 bg-white/90 dark:bg-gray-800/95 backdrop-blur-md shadow-xs border-b border-gray-100 dark:border-gray-700">
          <div class="container flex items-center justify-between h-full px-6 mx-auto text-purple-600 dark:text-purple-300">
            
            <!-- Mobile hamburger -->
            <button
              class="p-1.5 mr-4 -ml-1 rounded-lg md:hidden focus:outline-none hover:bg-gray-100 dark:hover:bg-gray-700 transition"
              @click="toggleSideMenu"
              aria-label="Menu"
            >
              <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                <path
                  fill-rule="evenodd"
                  d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                  clip-rule="evenodd"
                ></path>
              </svg>
            </button>

            <!-- Search input -->
            <div class="flex justify-center flex-1 max-w-lg lg:mr-32">
              <div class="relative w-full focus-within:text-purple-500">
                <div class="absolute inset-y-0 flex items-center pl-3">
                  <svg class="w-4 h-4 text-gray-400" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                  </svg>
                </div>
                <input
                  class="w-full pl-9 pr-4 text-sm text-gray-700 placeholder-gray-400 bg-gray-50 border border-gray-200 rounded-xl dark:bg-gray-700/60 dark:border-gray-600 dark:text-gray-200 focus:bg-white focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-200 dark:focus:ring-purple-900 transition py-2"
                  type="text"
                  placeholder="Search for patients, records..."
                  aria-label="Search"
                />
              </div>
            </div>

            <!-- Right Nav Items -->
            <ul class="flex items-center flex-shrink-0 space-x-5">
              
              <!-- Theme toggler -->
              <li>
                <button
                  class="p-2 rounded-xl text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none transition"
                  @click="toggleTheme"
                  aria-label="Toggle color mode"
                >
                  <template x-if="!dark">
                    <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                    </svg>
                  </template>
                  <template x-if="dark">
                    <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"></path>
                    </svg>
                  </template>
                </button>
              </li>

              <!-- Doctor Name Pill -->
              <li class="hidden sm:block">
                <?php
                  if (isset($_SESSION['full_name'])) {
                    echo "<span class=\"text-xs font-semibold text-white bg-purple-600 px-3.5 py-1.5 rounded-full shadow-xs tracking-wide\">Dr. " . htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8') . "</span>";
                  }
                ?>
              </li>

              <!-- Profile menu -->
              <li class="relative">
                <button
                  class="align-middle rounded-full ring-2 ring-purple-500/30 hover:ring-purple-500 focus:outline-none transition"
                  @click="toggleProfileMenu"
                  @keydown.escape="closeProfileMenu"
                  aria-label="Account"
                  aria-haspopup="true"
                >
                  <img
                    class="object-cover w-9 h-9 rounded-full"
                    src="../../assets/images/team-03.png"
                    alt="Doctor profile"
                  />
                </button>
                <template x-if="isProfileMenuOpen">
                  <ul
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click.away="closeProfileMenu"
                    @keydown.escape="closeProfileMenu"
                    class="absolute right-0 w-48 p-2 mt-2 space-y-1 text-gray-600 bg-white border border-gray-100 rounded-xl shadow-lg dark:border-gray-700 dark:text-gray-300 dark:bg-gray-800"
                    aria-label="submenu"
                  >
                    <li>
                      <form action="" method="POST" class="w-full">
                          <button type="submit" name="logout" class="w-full flex items-center gap-2.5 text-red-600 dark:text-red-400 px-3 py-2 rounded-lg text-sm font-medium hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                            <i class="bi bi-box-arrow-right text-base"></i>Logout
                          </button>
                      </form>
                    </li>
                  </ul>
                </template>
              </li>
            </ul>

          </div>
        </header>

        <!-- Main Content Area -->
        <main class="h-full overflow-y-auto bg-gray-50 dark:bg-gray-900 p-6 sm:p-8">
          <div class="container mx-auto max-w-7xl">
            
            <div class="flex items-center justify-between mb-8">
              <h2 class="text-2xl font-bold tracking-tight text-gray-800 dark:text-gray-100">
                Doctor Dashboard Overview
              </h2>
            </div>

            <!-- Stats Grid Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
              
              <!-- Card 1: Rendez-vous -->
              <div class="dashboard-card card-rendez-vous cursor-pointer flex items-center p-6 bg-white rounded-2xl border border-gray-100 shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
                <div class="p-4 mr-5 text-orange-600 bg-orange-100 dark:text-orange-100 dark:bg-orange-500/20 rounded-xl">
                  <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path>
                  </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-400 mb-1">Total Rendez-vous</p>
                    <?php 
                      include("../../db.php");
                      $sql = "SELECT count(*) FROM \"rendez_vous\"";
                      $count_rendez_vous = $pdo->query($sql)->fetchColumn();
                    ?>
                    <p class="text-3xl font-extrabold text-gray-800 dark:text-gray-100"><?php echo $count_rendez_vous; ?></p>
                </div>
              </div>

              <!-- Card 2: Rendez-vous Aujourd'hui -->
              <div class="dashboard-card card-rendez-vousToDay cursor-pointer flex items-center p-6 bg-white rounded-2xl border border-gray-100 shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
                <div class="p-4 mr-5 text-emerald-600 bg-emerald-100 dark:text-emerald-100 dark:bg-emerald-500/20 rounded-xl">
                  <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                  </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-400 mb-1">Aujourd'hui</p>
                    <?php 
                      $sql_today = "SELECT count(*) FROM \"rendez_vous\" WHERE date::date = CURRENT_DATE";
                      $count_rendez_vous_today = $pdo->query($sql_today)->fetchColumn();
                    ?>
                    <p class="text-3xl font-extrabold text-gray-800 dark:text-gray-100"><?php echo $count_rendez_vous_today; ?></p>
                </div>
              </div>

              <!-- Card 3: Dossier Medical -->
              <div class="dashboard-card card_dossier_medical cursor-pointer flex items-center p-6 bg-white rounded-2xl border border-gray-100 shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
                <div class="p-4 mr-5 text-blue-600 bg-blue-100 dark:text-blue-100 dark:bg-blue-500/20 rounded-xl">
                  <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"></path>
                  </svg>
                </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-400 mb-1">Dossiers Médicaux</p>
                  <p class="text-3xl font-extrabold text-gray-800 dark:text-gray-100">376</p>
                </div>
              </div>

            </div>

            <!-- Dynamic Content Sections -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-xs p-6">
              <section class="contentAll_rendez_vous">
                 <?php 
                   include_once ("../doctor/contentAdmin.php");
                   echo $content;
                 ?>
              </section>
              <section class="contentAll_rendez_vousToDay hidden">
                 <?php 
                   include_once ("../doctor/contentAdmin.php");
                   echo $content_today;
                 ?>
              </section>
              <section class="dossier_medical hidden">
                 <?php 
                   include_once ("../doctor/dossierMedical.php");
                   echo $content_dossier;
                 ?>
              </section>
            </div>

          </div>
        </main>
      </div>
    </div>

    <!-- Script to toggle active view panels -->
    <script>
        const contentAll_rendez_vous = document.querySelector(".contentAll_rendez_vous");
        const contentAll_rendez_vousToDay = document.querySelector(".contentAll_rendez_vousToDay");
        const dossier_medical = document.querySelector(".dossier_medical");
        const card_rendez_vous = document.querySelectorAll(".card-rendez-vous");
        const card_rendez_vousToDay = document.querySelector(".card-rendez-vousToDay");
        const card_dossier_medical = document.querySelectorAll(".card_dossier_medical");

        card_rendez_vous.forEach(card => {
          card.addEventListener("click", function() {
            contentAll_rendez_vous.classList.remove("hidden");
            contentAll_rendez_vousToDay.classList.add("hidden");
            dossier_medical.classList.add("hidden");
          });
        });

        card_rendez_vousToDay.addEventListener("click", function(){
          contentAll_rendez_vousToDay.classList.remove("hidden");
          contentAll_rendez_vous.classList.add("hidden");
          dossier_medical.classList.add("hidden");
        });

        card_dossier_medical.forEach(card => {
          card.addEventListener("click", function() {
            dossier_medical.classList.remove("hidden");
            contentAll_rendez_vousToDay.classList.add("hidden");
            contentAll_rendez_vous.classList.add("hidden");
          });
        });
    </script>
  </body>
</html>