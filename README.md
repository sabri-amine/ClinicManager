# ClinicManager — Application Web de Gestion de Clinique

ClinicManager est une application web moderne et sécurisée de gestion de clinique médicale développée en **PHP** avec **PostgreSQL**.

---

## 🚀 Fonctionnalités par Rôle

### 1. Espace Patient (Utilisateurs)
- **Authentification sécurisée** : Inscription avec vérification, connexion protégée et hachage bcrypt.
- **Prise de rendez-vous en ligne** :
  - Sélection du médecin traitant selon sa spécialité.
  - Choix de la date et du créneau horaire.
  - **Protection anti-double réservation** : vérification en temps réel de la disponibilité du médecin pour empêcher les conflits d'horaires.
- **Historique personnel** :
  - Suivi des rendez-vous avec statuts clairs : *En attente*, *Confirmé*, *Terminé*, *Annulé*.
  - Possibilité d'annuler un rendez-vous en attente.
- **Dossier médical & Ordonnances** :
  - Consultation des observations cliniques, diagnostics et traitements préconisés.
  - Consultation des ordonnances avec posologies et instructions détaillées.
- **Facturation & Reçus** : Suivi des paiements effectués ou en attente.

### 2. Espace Médecin (Docteurs)
- **Tableau de bord dynamique** : Vue d'ensemble avec statistiques réelles issues de PostgreSQL (total rendez-vous, rendez-vous du jour, dossiers médicaux, ordonnances).
- **Gestion des rendez-vous** :
  - Consultation des rendez-vous globaux et du planning journalier.
  - Mise à jour du statut des consultations (*Confirmer*, *Terminer*, *Annuler*).
  - Envoi automatique de notifications de suivi au patient.
- **Dossiers médicaux & Prescriptions** :
  - Création de dossiers médicaux avec diagnostic et traitement.
  - Prescription directe d'ordonnances médicamenteuses.
  - Attribution optionnelle de chambre en cas d'hospitalisation.
  - Modification et mise à jour des dossiers existants (`edit_dossier.php`).

### 3. Espace Administrateur
- **Tableau de bord analytique** :
  - Statistiques en direct : Utilisateurs actifs, comptes archivés, nouveaux inscrits du jour, total des consultations et chiffre d'affaires des factures encaissées.
- **Gestion des utilisateurs (CRUD & Sécurité)** :
  - Validation et confirmation des nouveaux comptes.
  - Modification des rôles, statuts et coordonnées.
  - **Soft delete (Archivage)** pour garantir l'intégrité des historiques médicaux.
  - Restauration des comptes archivés.

---

## 🔒 Sécurité & Bonnes Pratiques

- **Requêtes préparées PDO PostgreSQL** systématiques : protection intégrale contre les injections SQL.
- **Protection CSRF** via jetons cryptographiques (`includes/auth.php`).
- **Contrôle d'accès strict** : vérification de session et filtrage par rôle sur chaque page et chaque action (`requireRole()`).
- **Protection XSS** : échappement des sorties via `e()` / `htmlspecialchars`.
- **Transactions atomiques PostgreSQL** pour les opérations complexes (dossier + chambre + ordonnance).
- **Intégrité référentielle** : gestion des contraintes et clés étrangères.

---

## 🛠️ Stack Technique

- **Backend** : PHP 8.2+
- **Base de données** : PostgreSQL (avec extension PDO `pdo_pgsql`)
- **Frontend** : HTML5, Tailwind CSS, SweetAlert2, Bootstrap Icons
- **Polices** : DM Sans, Manrope, Inter

---

## 🧪 Lancer la Suite de Tests

Pour exécuter les 38 tests automatisés de syntaxe, de sécurité, de logique métier et de base de données :

```bash
php tests/run_tests.php
```
