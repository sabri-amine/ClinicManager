-- ClinicManager - PostgreSQL Database Schema

-- Types
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'role_type') THEN
        CREATE TYPE role_type AS ENUM ('user', 'admin', 'doctor');
    END IF;
END $$;

-- Table: role
CREATE TABLE IF NOT EXISTS role (
    id SERIAL PRIMARY KEY,
    name role_type NOT NULL
);

-- Table: user
CREATE TABLE IF NOT EXISTS "user" (
    id SERIAL PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    telephone VARCHAR(30),
    specialite VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    role_id INT REFERENCES role(id),
    status VARCHAR(50) NOT NULL CHECK (status IN ('actif', 'archivé')),
    is_confirmed BOOLEAN DEFAULT FALSE
);

-- Table: rendez_vous
CREATE TABLE IF NOT EXISTS rendez_vous (
    id SERIAL PRIMARY KEY,
    cni VARCHAR(20) NOT NULL,
    date_rendez_vous TIMESTAMP NOT NULL,
    heure_rendez_vous TIME DEFAULT '09:00:00',
    heure_fin TIME DEFAULT '10:00:00',
    motif TEXT,
    note_medecin TEXT,
    date_proposee DATE,
    heure_debut_proposee TIME,
    heure_fin_proposee TIME,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT NOT NULL REFERENCES "user"(id) ON DELETE CASCADE,
    doctor_id INT REFERENCES "user"(id) ON DELETE SET NULL,
    statut VARCHAR(30) DEFAULT 'en_attente',
    is_confirmed BOOLEAN DEFAULT FALSE
);

-- Index unique partiel : un seul rendez-vous actif par patient et par médecin
CREATE UNIQUE INDEX IF NOT EXISTS uq_active_rdv_patient_doctor 
ON rendez_vous (user_id, doctor_id) 
WHERE statut IN ('en_attente', 'confirme');

-- Table: dossier_medical
CREATE TABLE IF NOT EXISTS dossier_medical (
    dossier_id SERIAL PRIMARY KEY,
    date_dossie TIMESTAMP NOT NULL,
    diagnostic TEXT,
    traitement TEXT,
    user_id INT NOT NULL REFERENCES "user"(id) ON DELETE CASCADE,
    doctor_id INT REFERENCES "user"(id) ON DELETE SET NULL
);

-- Table: chambre
CREATE TABLE IF NOT EXISTS chambre (
    chambre_id SERIAL PRIMARY KEY,
    num INT NOT NULL,
    type VARCHAR(50),
    statut VARCHAR(50),
    dossier_id INT NOT NULL REFERENCES dossier_medical(dossier_id) ON DELETE CASCADE
);

-- Table: ordonnance
CREATE TABLE IF NOT EXISTS ordonnance (
    id SERIAL PRIMARY KEY,
    dossier_id INT REFERENCES dossier_medical(dossier_id) ON DELETE CASCADE,
    doctor_id INT REFERENCES "user"(id) ON DELETE SET NULL,
    user_id INT NOT NULL REFERENCES "user"(id) ON DELETE CASCADE,
    medicaments TEXT NOT NULL,
    posologie TEXT,
    instructions TEXT,
    date_prescription TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: facture
CREATE TABLE IF NOT EXISTS facture (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES "user"(id) ON DELETE CASCADE,
    rendez_vous_id INT REFERENCES rendez_vous(id) ON DELETE SET NULL,
    dossier_id INT REFERENCES dossier_medical(dossier_id) ON DELETE SET NULL,
    montant NUMERIC(10, 2) NOT NULL DEFAULT 0.00,
    statut_paiement VARCHAR(30) NOT NULL DEFAULT 'en_attente',
    mode_paiement VARCHAR(50) DEFAULT 'especes',
    reference_recu VARCHAR(50) UNIQUE,
    date_facture TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_paiement TIMESTAMP
);

-- Table: notification
CREATE TABLE IF NOT EXISTS notification (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES "user"(id) ON DELETE CASCADE,
    rendez_vous_id INT REFERENCES rendez_vous(id) ON DELETE SET NULL,
    titre VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Trigger anti-double réservation dans PostgreSQL
CREATE OR REPLACE FUNCTION check_rendez_vous_overlap()
RETURNS TRIGGER AS $$
DECLARE
    conflict_count INT;
BEGIN
    -- Ne vérifier le chevauchement horaire que pour les rendez-vous confirmés
    IF NEW.statut = 'confirme' AND NEW.doctor_id IS NOT NULL THEN
        IF NEW.heure_fin IS NULL THEN
            NEW.heure_fin := (COALESCE(NEW.heure_rendez_vous, '09:00:00'::time) + INTERVAL '1 hour')::time;
        END IF;

        SELECT COUNT(*) INTO conflict_count
        FROM rendez_vous
        WHERE doctor_id = NEW.doctor_id
          AND id != COALESCE(NEW.id, 0)
          AND statut = 'confirme'
          AND date_rendez_vous::date = NEW.date_rendez_vous::date
          AND heure_rendez_vous < NEW.heure_fin
          AND NEW.heure_rendez_vous < COALESCE(heure_fin, (heure_rendez_vous + INTERVAL '1 hour')::time);

        IF conflict_count > 0 THEN
            RAISE EXCEPTION 'Double réservation détectée : ce créneau est déjà confirmé pour ce médecin.';
        END IF;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_check_rendez_vous_overlap ON rendez_vous;
CREATE TRIGGER trg_check_rendez_vous_overlap
BEFORE INSERT OR UPDATE ON rendez_vous
FOR EACH ROW
EXECUTE FUNCTION check_rendez_vous_overlap();

