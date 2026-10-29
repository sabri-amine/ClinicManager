<?php
// views/user/repondre_proposition.php - Réponse du patient à un créneau proposé par le médecin
require_once __DIR__ . '/../../includes/auth.php';
requireRole('user');

$userId = (int)($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $_SESSION['message'] = "Session expirée. Veuillez réessayer.";
        $_SESSION['message_type'] = 'error';
        header("Location: ./user.php#mes_rendez_vous");
        exit;
    }

    $rdvId = filter_input(INPUT_POST, 'rdv_id', FILTER_VALIDATE_INT);
    $decision = trim($_POST['decision'] ?? ''); // 'accepter' ou 'refuser'

    if ($rdvId && in_array($decision, ['accepter', 'refuser'], true)) {
        try {
            // Vérifier que le rendez-vous appartient bien à ce patient et qu'une proposition est en attente
            $stmt = $pdo->prepare('
                SELECT r.*, u_doc.full_name AS doctor_name
                FROM rendez_vous r
                LEFT JOIN "user" u_doc ON r.doctor_id = u_doc.id
                WHERE r.id = :id AND r.user_id = :uid AND r.statut = \'propose\'
            ');
            $stmt->execute([':id' => $rdvId, ':uid' => $userId]);
            $rdv = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$rdv) {
                $_SESSION['message'] = "Aucune proposition en attente trouvée pour ce rendez-vous.";
                $_SESSION['message_type'] = 'error';
                header("Location: ./user.php#mes_rendez_vous");
                exit;
            }

            $docName = $rdv['doctor_name'] ?? 'le médecin';
            $pDate = $rdv['date_proposee'];
            $pDebut = $rdv['heure_debut_proposee'];
            $pFin = $rdv['heure_fin_proposee'] ?: date('H:i:s', strtotime($pDebut . ' + 1 hour'));

            if ($decision === 'accepter') {
                // Vérifier une dernière fois la disponibilité du créneau
                if (!isDoctorSlotAvailable($pdo, (int)$rdv['doctor_id'], $pDate, $pDebut, $pFin, $rdvId)) {
                    $_SESSION['message'] = "Désolé, ce créneau n'est plus disponible. Veuillez contacter la clinique.";
                    $_SESSION['message_type'] = 'error';
                    header("Location: ./user.php#mes_rendez_vous");
                    exit;
                }

                $newDateTime = $pDate . ' ' . $pDebut;
                $upd = $pdo->prepare('
                    UPDATE rendez_vous
                    SET statut = \'confirme\',
                        is_confirmed = true,
                        date_rendez_vous = :ndatetime,
                        heure_rendez_vous = :ndebut,
                        heure_fin = :nfin,
                        date_proposee = NULL,
                        heure_debut_proposee = NULL,
                        heure_fin_proposee = NULL
                    WHERE id = :id
                ');
                $upd->execute([
                    ':ndatetime' => $newDateTime,
                    ':ndebut'    => $pDebut,
                    ':nfin'      => $pFin,
                    ':id'        => $rdvId
                ]);

                // Notification patient
                $dateFr = date('d/m/Y', strtotime($pDate));
                $debutFr = substr($pDebut, 0, 5);
                $finFr = substr($pFin, 0, 5);
                $notifTitre = "Rendez-vous confirmé";
                $notifMsg = "Vous avez accepté le créneau proposé par Dr. $docName. Votre consultation est confirmée pour le $dateFr de $debutFr à $finFr.";
                addNotification($pdo, $userId, $notifTitre, $notifMsg, 'success', $rdvId);

                $_SESSION['message'] = "Créneau accepté ! Votre rendez-vous est maintenant confirmé pour le $dateFr de $debutFr à $finFr.";
                $_SESSION['message_type'] = 'success';

            } else {
                // Le patient refuse la proposition
                $upd = $pdo->prepare('
                    UPDATE rendez_vous
                    SET statut = \'refuse_par_patient\',
                        is_confirmed = false,
                        date_proposee = NULL,
                        heure_debut_proposee = NULL,
                        heure_fin_proposee = NULL
                    WHERE id = :id
                ');
                $upd->execute([':id' => $rdvId]);

                // Notification patient
                $dateFr = date('d/m/Y', strtotime($pDate));
                $notifTitre = "Proposition refusée";
                $notifMsg = "Vous avez refusé le créneau proposé pour le $dateFr. Le Dr. $docName a été informé et pourra vous proposer un autre créneau selon ses disponibilités.";
                addNotification($pdo, $userId, $notifTitre, $notifMsg, 'warning', $rdvId);

                $_SESSION['message'] = "Vous avez décliné la date proposée. Le médecin a été informé.";
                $_SESSION['message_type'] = 'info';
            }

        } catch (PDOException $e) {
            error_log("Erreur repondre proposition: " . $e->getMessage());
            $_SESSION['message'] = "Une erreur est survenue lors de l'enregistrement de votre réponse.";
            $_SESSION['message_type'] = 'error';
        }
    }
}

header("Location: ./user.php#rdv_" . ($rdvId ?? ''));
exit;

