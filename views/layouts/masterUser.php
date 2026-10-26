<?php
session_start();

if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: ../login/login.php'); 
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medic - Accueil & Prise de Rendez-vous</title>
    <!-- Thème médical réalisé par Seb Code -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">

    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="text-gray-500 bg-white antialiased">

  <!-- Message Banner (Session alerts) -->
  <div class="fixed top-20 left-1/2 -translate-x-1/2 z-50 w-full max-w-md px-4">
      <?php
      if (isset($_SESSION['message'])) {
          $message = $_SESSION['message'];
          $messageType = ($_SESSION['message_type'] === 'success') ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white'; 
          echo "<div class='p-4 rounded-2xl shadow-lg text-center font-semibold text-sm " . $messageType . "'>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</div>";
          unset($_SESSION['message']);
          unset($_SESSION['message_type']);
      }
      ?>
  </div>

    <!-- Fixed Navigation Bar -->
    <header class="fixed top-0 left-0 right-0 z-40 bg-white/90 backdrop-blur-md border-b border-gray-100 shadow-2xs">
        <div class="container px-6 mx-auto max-w-7xl">
            <nav class="py-4 flex flex-col space-y-4 items-center md:flex-row md:justify-between md:space-y-0">
                <a class="text-indigo-700 font-bold text-3xl flex items-center gap-2.5 tracking-tight" href="#">
                    <img class="h-10 inline" src="../../assets/images/logo.svg" alt="Medic"> Medic
                </a>
                <ul class="font-semibold text-sm flex flex-col space-y-2 items-center md:flex-row md:space-y-0 md:space-x-8 text-gray-600">
                    <li><a class="hover:text-indigo-700 transition" href="#apropos">A propos</a></li>
                    <li><a class="hover:text-indigo-700 transition" href="#services">Services</a></li>
                    <li><a class="hover:text-indigo-700 transition" href="#equipe">Témoignages</a></li>
                </ul>
                
                <div class="flex items-center gap-4">
                    <?php
                        if (isset($_SESSION['full_name'])) {
                            echo "<span class=\"text-xs font-semibold text-white bg-indigo-700 px-4 py-2 rounded-xl shadow-xs tracking-wide\">" . htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8') . "</span>";
                        }
                    ?>  

                    <div class="relative">
                        <img src="../../assets/images/team-06.png" class="Profil cursor-pointer w-10 h-10 rounded-full object-cover ring-2 ring-indigo-500/30 hover:ring-indigo-600 transition" alt="Profil utilisateur" width="40"> 
                        
                        <!-- Dropdown menu -->
                        <div class="menu absolute right-0 top-12 hidden w-40 p-2 bg-white border border-gray-100 rounded-2xl shadow-xl z-50">
                            <form action="" method="POST">
                                <button type="submit" name="logout" class="w-full flex items-center gap-2 text-rose-600 px-3 py-2 rounded-xl text-xs font-semibold hover:bg-rose-50 transition">
                                    <i class="bi bi-box-arrow-right text-sm"></i>Déconnexion
                                </button>
                            </form>
                        </div>  
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <!-- Section hero (avec pt-28 pour compenser la navbar fixe) -->
    <section class="bg-slate-50/50 pt-32 pb-12 border-b border-gray-100">
        <div class="container px-6 mx-auto max-w-7xl">
            <div class="my-12 flex flex-col items-center md:flex-row gap-12">
                <div class="w-full py-4 order-2 md:order-1 md:w-1/2">
                    <h1 class="mb-6 text-3xl lg:text-5xl font-extrabold text-gray-900 tracking-tight leading-tight">
                        Une équipe à votre écoute pour <span class="text-teal-500">vous soigner</span>
                    </h1>
                    <p class="mb-8 text-base lg:text-lg text-gray-600 leading-relaxed">
                        Notre clinique offre des soins de qualité dans un cadre moderne et accueillant, avec une équipe dédiée à votre bien-être et une large gamme de services médicaux personnalisés.
                    </p>
                    <a href="#rendez_vous" class="inline-flex items-center justify-center px-7 py-3.5 text-sm text-white font-bold hover:bg-teal-600 rounded-xl shadow-lg shadow-indigo-700/20 transition duration-200 bg-indigo-700">
                        Prendre rendez-vous
                    </a>
                </div>
                <div class="w-full md:w-1/2 order-1 md:order-2 flex justify-center">
                    <img class="max-w-md w-full object-contain" src="../../assets/images/docteur.png" alt="Docteur" data-aos="fade-up" data-aos-duration="1500">
                </div>
            </div>
        </div>
    </section>

    <!-- Section services -->
    <section class="py-20 bg-indigo-700 text-white relative overflow-hidden" data-aos="fade-up" data-aos-duration="1500" id="services">
        <div class="container px-6 pb-24 mx-auto max-w-7xl">
            <div class="max-w-3xl mx-auto text-center mb-16">
                <h2 class="mb-4 text-2xl md:text-4xl font-bold tracking-tight">Une clinique à la pointe pour vous proposer les meilleurs soins</h2>
                <p class="text-indigo-100/90 text-sm md:text-base leading-relaxed">Équipée des dernières technologies médicales, notre clinique s’engage à vous offrir des soins de qualité optimale. Notre priorité est votre bien-être, dans un environnement moderne, sécurisé et chaleureux.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white text-gray-800 text-center p-8 rounded-3xl shadow-xl">
                    <img class="h-12 mx-auto mb-4" src="../../assets/images/icone-docteur.svg" alt="Médecins spécialistes">
                    <h3 class="mb-3 text-xl font-bold">Médecins spécialistes</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Des médecins spécialistes expérimentés, dédiés à votre santé, offrant des diagnostics précis et des traitements adaptés.</p>
                </div>
                <div class="bg-white text-gray-800 text-center p-8 rounded-3xl shadow-xl">
                    <img class="h-12 mx-auto mb-4" src="../../assets/images/icone-urgence.svg" alt="Soins d'urgence">
                    <h3 class="mb-3 text-xl font-bold">Soins d'urgence</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Des soins d'urgence rapides et efficaces, assurés par une équipe médicale qualifiée dans un environnement sécurisé.</p>
                </div>
                <div class="bg-white text-gray-800 text-center p-8 rounded-3xl shadow-xl">
                    <img class="h-12 mx-auto mb-4" src="../../assets/images/icone-disponibilite.svg" alt="Disponibilité 24/7">
                    <h3 class="mb-3 text-xl font-bold">Disponibilité 24/7</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Un service accessible 24h/24 et 7j/7 pour assurer votre santé en toute heure avec un accompagnement personnalisé.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section présentation (A propos) -->
    <section class="py-24" data-aos="fade-up" data-aos-duration="1500" id="apropos">
        <div class="container px-6 mx-auto max-w-7xl">
            <div class="flex flex-wrap items-center gap-y-12">
                <div class="w-full md:w-1/2 order-2 md:order-1 pr-0 md:pr-12">
                    <h2 class="mb-6 text-3xl lg:text-4xl font-extrabold text-gray-900 tracking-tight">Votre confort commence dès votre arrivée</h2>
                    <p class="mb-8 text-gray-600 leading-relaxed text-base">Notre clinique vous garantit un accueil professionnel et bienveillant. Notre équipe dévouée est à votre écoute pour vous offrir une expérience sereine et personnalisée dès votre arrivée.</p>
                    <a class="inline-flex items-center px-6 py-3 text-sm text-white font-bold hover:bg-teal-600 rounded-xl transition duration-200 bg-indigo-700 shadow-md shadow-indigo-700/20" href="#">En savoir plus</a>
                </div>
                <div class="w-full md:w-1/2 order-1 md:order-2">
                    <img class="w-full rounded-3xl shadow-xl object-cover h-80" src="../../assets/images/accueil-clinique.jpg" alt="Présentation de la clinique">
                </div>
            </div>
        </div>
    </section>

    <!-- Section équipe médicale (Témoignages) -->
    <section class="py-20 bg-slate-50/50" id="equipe">
        <div class="container px-6 mx-auto max-w-7xl">
            <div class="max-w-3xl mx-auto mb-16 text-center" data-aos="fade-up" data-aos-duration="1500">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full">Experts</span>
                <h2 class="mt-3 mb-4 text-3xl lg:text-4xl font-extrabold text-gray-900 tracking-tight">Une équipe hautement qualifiée</h2>
                <p class="text-gray-600 text-sm md:text-base">Découvrez nos spécialistes dévoués à votre santé et votre guérison au quotidien.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" data-aos="fade-up" data-aos-duration="1500">
                <!-- Membre 1 -->
                <div class="bg-white p-6 pb-8 text-center rounded-3xl border border-gray-100 shadow-xs hover:shadow-xl transition">
                    <img class="w-full h-64 object-cover mx-auto mb-6 rounded-2xl" src="../../assets/images/team-01.png" alt="Charles KHUTE">
                    <h3 class="text-xl font-bold text-gray-900">Charles KHUTE</h3>
                    <p class="text-sm font-medium text-teal-600 mb-6">Chirurgien</p>
                    <div class="flex justify-center gap-4 text-gray-400">
                        <a class="hover:text-indigo-700 transition" href="#"><i class="fa-brands fa-facebook text-lg"></i></a>
                        <a class="hover:text-indigo-700 transition" href="#"><i class="fa-brands fa-x-twitter text-lg"></i></a>
                        <a class="hover:text-indigo-700 transition" href="#"><i class="fa-brands fa-instagram text-lg"></i></a>
                    </div>
                </div>
                <!-- Membre 2 -->
                <div class="bg-white p-6 pb-8 text-center rounded-3xl border border-gray-100 shadow-xs hover:shadow-xl transition">
                    <img class="w-full h-64 object-cover mx-auto mb-6 rounded-2xl" src="../../assets/images/team-02.png" alt="Eva TEXAMINET">
                    <h3 class="text-xl font-bold text-gray-900">Eva TEXAMINET</h3>
                    <p class="text-sm font-medium text-teal-600 mb-6">Médecin généraliste</p>
                    <div class="flex justify-center gap-4 text-gray-400">
                        <a class="hover:text-indigo-700 transition" href="#"><i class="fa-brands fa-facebook text-lg"></i></a>
                        <a class="hover:text-indigo-700 transition" href="#"><i class="fa-brands fa-x-twitter text-lg"></i></a>
                        <a class="hover:text-indigo-700 transition" href="#"><i class="fa-brands fa-instagram text-lg"></i></a>
                    </div>
                </div>
                <!-- Membre 3 -->
                <div class="bg-white p-6 pb-8 text-center rounded-3xl border border-gray-100 shadow-xs hover:shadow-xl transition">
                    <img class="w-full h-64 object-cover mx-auto mb-6 rounded-2xl" src="../../assets/images/team-03.png" alt="Dan THYSTE">
                    <h3 class="text-xl font-bold text-gray-900">Dan THYSTE</h3>
                    <p class="text-sm font-medium text-teal-600 mb-6">Chef de service</p>
                    <div class="flex justify-center gap-4 text-gray-400">
                        <a class="hover:text-indigo-700 transition" href="#"><i class="fa-brands fa-facebook text-lg"></i></a>
                        <a class="hover:text-indigo-700 transition" href="#"><i class="fa-brands fa-x-twitter text-lg"></i></a>
                        <a class="hover:text-indigo-700 transition" href="#"><i class="fa-brands fa-instagram text-lg"></i></a>
                    </div>
                </div>
                <!-- Membre 4 -->
                <div class="bg-white p-6 pb-8 text-center rounded-3xl border border-gray-100 shadow-xs hover:shadow-xl transition">
                    <img class="w-full h-64 object-cover mx-auto mb-6 rounded-2xl" src="../../assets/images/team-04.png" alt="Joss KULTH">
                    <h3 class="text-xl font-bold text-gray-900">Joss KULTH</h3>
                    <p class="text-sm font-medium text-teal-600 mb-6">Médecin urgentiste</p>
                    <div class="flex justify-center gap-4 text-gray-400">
                        <a class="hover:text-indigo-700 transition" href="#"><i class="fa-brands fa-facebook text-lg"></i></a>
                        <a class="hover:text-indigo-700 transition" href="#"><i class="fa-brands fa-x-twitter text-lg"></i></a>
                        <a class="hover:text-indigo-700 transition" href="#"><i class="fa-brands fa-instagram text-lg"></i></a>
                    </div>
                </div>
                <!-- Membre 5 -->
                <div class="bg-white p-6 pb-8 text-center rounded-3xl border border-gray-100 shadow-xs hover:shadow-xl transition">
                    <img class="w-full h-64 object-cover mx-auto mb-6 rounded-2xl" src="../../assets/images/team-05.png" alt="Emma TAUME">
                    <h3 class="text-xl font-bold text-gray-900">Emma TAUME</h3>
                    <p class="text-sm font-medium text-teal-600 mb-6">Pédiatre</p>
                    <div class="flex justify-center gap-4 text-gray-400">
                        <a class="hover:text-indigo-700 transition" href="#"><i class="fa-brands fa-facebook text-lg"></i></a>
                        <a class="hover:text-indigo-700 transition" href="#"><i class="fa-brands fa-x-twitter text-lg"></i></a>
                        <a class="hover:text-indigo-700 transition" href="#"><i class="fa-brands fa-instagram text-lg"></i></a>
                    </div>
                </div>
                <!-- Membre 6 -->
                <div class="bg-white p-6 pb-8 text-center rounded-3xl border border-gray-100 shadow-xs hover:shadow-xl transition">
                    <img class="w-full h-64 object-cover mx-auto mb-6 rounded-2xl" src="../../assets/images/team-06.png" alt="Ray ANYMASSION">
                    <h3 class="text-xl font-bold text-gray-900">Ray ANYMASSION</h3>
                    <p class="text-sm font-medium text-teal-600 mb-6">Anesthésiste</p>
                    <div class="flex justify-center gap-4 text-gray-400">
                        <a class="hover:text-indigo-700 transition" href="#"><i class="fa-brands fa-facebook text-lg"></i></a>
                        <a class="hover:text-indigo-700 transition" href="#"><i class="fa-brands fa-x-twitter text-lg"></i></a>
                        <a class="hover:text-indigo-700 transition" href="#"><i class="fa-brands fa-instagram text-lg"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section formulaire -->
    <section class="relative py-24 bg-indigo-700 overflow-hidden" data-aos="fade-up" data-aos-duration="1500" id="rendez_vous">
        <div class="container px-6 mx-auto max-w-7xl relative z-10">
            <div class="flex flex-wrap items-center gap-12 lg:gap-0">
                <div class="w-full lg:w-1/2 pr-0 lg:pr-12">
                    <h2 class="mb-6 text-3xl lg:text-5xl font-extrabold text-white tracking-tight leading-tight">Prendre rendez-vous en ligne</h2>
                    <p class="text-indigo-100 text-base leading-relaxed">Remplissez le formulaire rapide ci-contre pour planifier votre consultation avec notre équipe médicale en quelques clics.</p>
                </div>
                
                <div class="w-full lg:w-1/2">
                    <div class="p-8 bg-white rounded-3xl shadow-2xl">
                        <form action="rendezVous.php" method="POST" class="space-y-5">
                            <h3 class="text-xs font-bold tracking-widest text-indigo-700 uppercase text-center mb-6">Demande de Rendez-vous</h3>
                            
                            <div>
                                <label for="cni" class="block text-xs font-semibold uppercase tracking-wider text-gray-600 mb-2">Numéro CNI :</label>
                                <input id="cni" name="cni" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition" type="text" placeholder="Entrez votre numéro CNI" required>
                            </div>
                            
                            <div>
                                <label for="date" class="block text-xs font-semibold uppercase tracking-wider text-gray-600 mb-2">Date souhaitée :</label>
                                <input id="date" name="date" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition" type="date" required>
                            </div>

                            <label class="flex items-start gap-3 pt-2">
                                <input class="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" type="checkbox" name="terms" value="1" required>
                                <span class="text-xs text-gray-500 leading-normal">En soumettant cette demande, vous acceptez notre politique concernant vos données personnelles.</span>
                            </label>

                            <button type="submit" class="w-full mt-2 inline-flex items-center justify-center px-6 py-3.5 text-sm text-white font-bold bg-teal-500 hover:bg-indigo-700 rounded-xl shadow-lg shadow-teal-500/20 transition duration-200">
                                Envoyer la demande
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pied de page -->
    <footer class="bg-gray-50 pt-12 pb-6 border-t border-gray-200">
        <div class="container px-6 mx-auto max-w-7xl flex flex-col items-center space-y-6 md:flex-row md:space-y-0 md:justify-between pb-8">
            <a class="text-indigo-700 font-bold text-2xl flex items-center gap-2.5" href="#">
                <img class="h-10 inline" src="../../assets/images/logo.svg" alt="Medic"> Medic
            </a>
            <ul class="flex flex-col text-center md:flex-row md:space-x-8 text-sm font-medium text-gray-600">
                <li><a class="hover:text-indigo-700 transition" href="#apropos">A propos</a></li>
                <li><a class="hover:text-indigo-700 transition" href="#services">Services</a></li>
                <li><a class="hover:text-indigo-700 transition" href="#equipe">Témoignages</a></li>
            </ul>
        </div>
        <div class="container px-6 mx-auto max-w-7xl pt-6 border-t border-gray-200 flex flex-col items-center md:flex-row md:justify-between text-xs text-gray-500">
            <p>Tous les droits réservés © <a class="text-indigo-700 font-semibold" href="https://seb-code.fr/">Seb Code</a> 2024</p>
            <div class="flex gap-4 mt-4 md:mt-0 text-xl text-gray-600">
                <a class="hover:text-indigo-700 transition" href="https://www.youtube.com/@seb-code"><i class="fa-brands fa-youtube"></i></a>
                <a class="hover:text-indigo-700 transition" href="https://www.facebook.com/sebcode.fr"><i class="fa-brands fa-facebook"></i></a>
                <a class="hover:text-indigo-700 transition" href="https://amzn.to/3mG5oMJ"><i class="fa-brands fa-amazon"></i></a>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button id="scrollToTopBtn" class="fixed bottom-6 right-6 z-50 flex items-center justify-center w-12 h-12 bg-indigo-700 text-white rounded-full shadow-lg opacity-0 pointer-events-none hover:bg-teal-600 transition-all duration-300">
        <i class="fa-solid fa-arrow-up text-lg"></i>
    </button>

    <script>
        AOS.init(); 

        // Profil dropdown toggle
        const profilImg = document.querySelector(".Profil");
        const menuDropdown = document.querySelector(".menu");
        
        if (profilImg && menuDropdown) {
            profilImg.addEventListener("click", function(e) {
                e.stopPropagation();
                menuDropdown.classList.toggle('hidden');
            });

            document.addEventListener("click", function(e) {
                if (!menuDropdown.contains(e.target) && !profilImg.contains(e.target)) {
                    menuDropdown.classList.add('hidden');
                }
            });
        }

        // Scroll to Top button visibility and action
        const scrollToTopBtn = document.getElementById("scrollToTopBtn");

        window.addEventListener("scroll", function() {
            if (window.scrollY > 300) {
                scrollToTopBtn.classList.remove("opacity-0", "pointer-events-none");
                scrollToTopBtn.classList.add("opacity-100", "pointer-events-auto");
            } else {
                scrollToTopBtn.classList.remove("opacity-100", "pointer-events-auto");
                scrollToTopBtn.classList.add("opacity-0", "pointer-events-none");
            }
        });

        scrollToTopBtn.addEventListener("click", function() {
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        });
    </script>
</body>
</html>