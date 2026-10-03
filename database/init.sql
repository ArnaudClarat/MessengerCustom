-- =====================================================================
-- Messagerie temps réel B2B : création des tables (PostgreSQL)
-- À exécuter UNE fois sur la base Render. Relançable sans risque
-- (IF NOT EXISTS / ON CONFLICT), mais ne modifie pas les tables existantes.
-- =====================================================================

BEGIN;

CREATE EXTENSION IF NOT EXISTS citext;   -- emails/slugs insensibles à la casse

-- ---------------------------------------------------------------------
-- Utilisateurs & authentification
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email             CITEXT NOT NULL UNIQUE,
    password_hash     TEXT,                        -- NULL si connexion externe (OAuth)
    display_name      TEXT NOT NULL,
    avatar_url        TEXT,
    is_bot            BOOLEAN NOT NULL DEFAULT FALSE,
    email_verified_at TIMESTAMPTZ,
    created_at        TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Utile seulement si vous choisissez une auth externe (Google, GitHub...)
CREATE TABLE IF NOT EXISTS auth_identities (
    id               UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id          UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    provider         TEXT NOT NULL,
    provider_user_id TEXT NOT NULL,
    created_at       TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (provider, provider_user_id)
);

-- ---------------------------------------------------------------------
-- Plans (pricing) et organisations
-- NULL = illimité
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS plans (
    code               TEXT PRIMARY KEY,           -- 'free', 'pro'
    name               TEXT NOT NULL,
    price_cents        INTEGER NOT NULL DEFAULT 0, -- prix par mois, en centimes d'euro
    max_members        INTEGER,
    max_messages_month INTEGER,
    max_file_bytes     BIGINT,
    max_storage_bytes  BIGINT,
    retention_days     INTEGER
);

INSERT INTO plans (code, name, price_cents, max_members, max_messages_month,
                   max_file_bytes, max_storage_bytes, retention_days)
VALUES
    ('free', 'Free', 0,   5,  1000, 2097152,  52428800,  30),   -- 2 Mo/fichier, 50 Mo total
    ('pro',  'Pro',  900, 50, NULL, 10485760, 524288000, NULL)  -- 9 €/mois, 10 Mo/fichier, 500 Mo total
ON CONFLICT (code) DO NOTHING;

CREATE TABLE IF NOT EXISTS organizations (
    id         UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name       TEXT NOT NULL,
    slug       CITEXT NOT NULL UNIQUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS subscriptions (
    org_id     UUID PRIMARY KEY REFERENCES organizations(id) ON DELETE CASCADE,
    plan_code  TEXT NOT NULL REFERENCES plans(code) DEFAULT 'free',
    status     TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'canceled')),
    started_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Un utilisateur peut appartenir à plusieurs organisations
CREATE TABLE IF NOT EXISTS org_members (
    org_id    UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    user_id   UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role      TEXT NOT NULL DEFAULT 'member' CHECK (role IN ('owner', 'admin', 'member')),
    status    TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'suspended')),
    joined_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    PRIMARY KEY (org_id, user_id)
);
CREATE INDEX IF NOT EXISTS idx_org_members_user ON org_members (user_id);

CREATE TABLE IF NOT EXISTS invitations (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id      UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    email       CITEXT NOT NULL,
    role        TEXT NOT NULL DEFAULT 'member' CHECK (role IN ('admin', 'member')),
    token       TEXT NOT NULL UNIQUE DEFAULT replace(gen_random_uuid()::text || gen_random_uuid()::text, '-', ''),
    invited_by  UUID REFERENCES users(id) ON DELETE SET NULL,
    expires_at  TIMESTAMPTZ NOT NULL DEFAULT now() + interval '7 days',
    accepted_at TIMESTAMPTZ,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- ---------------------------------------------------------------------
-- Conversations (DM, groupe, channel)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS conversations (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id      UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    type        TEXT NOT NULL CHECK (type IN ('direct', 'group', 'channel')),
    visibility  TEXT NOT NULL DEFAULT 'private' CHECK (visibility IN ('public', 'private')),
    name        TEXT,
    topic       TEXT,
    direct_key  TEXT,          -- pour un DM : id_du_plus_petit || ':' || id_du_plus_grand
    created_by  UUID REFERENCES users(id) ON DELETE SET NULL,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
    archived_at TIMESTAMPTZ,
    UNIQUE (id, org_id),       -- sert aux clés étrangères composites plus bas
    UNIQUE (org_id, direct_key),                       -- pas de DM en double
    CONSTRAINT direct_rules CHECK (
        (type = 'direct' AND direct_key IS NOT NULL AND name IS NULL AND visibility = 'private')
        OR (type <> 'direct' AND direct_key IS NULL)
    ),
    CONSTRAINT channel_needs_name CHECK (type <> 'channel' OR name IS NOT NULL),
    CONSTRAINT public_only_channel CHECK (visibility = 'private' OR type = 'channel')
);
-- Deux channels d'une même organisation ne peuvent pas avoir le même nom
CREATE UNIQUE INDEX IF NOT EXISTS uq_channel_name
    ON conversations (org_id, lower(name)) WHERE type = 'channel';

-- ---------------------------------------------------------------------
-- Messages
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS messages (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    conversation_id UUID NOT NULL REFERENCES conversations(id) ON DELETE CASCADE,
    sender_id       UUID REFERENCES users(id) ON DELETE SET NULL,
    type            TEXT NOT NULL DEFAULT 'user' CHECK (type IN ('user', 'system')),
    content         TEXT NOT NULL CHECK (char_length(content) <= 4000),
    reply_to_id     BIGINT REFERENCES messages(id) ON DELETE SET NULL,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    edited_at       TIMESTAMPTZ,
    deleted_at      TIMESTAMPTZ
);
-- Requête principale : derniers messages d'une conversation
CREATE INDEX IF NOT EXISTS idx_messages_conv ON messages (conversation_id, id DESC);
-- Comptage des messages par expéditeur / par période (quotas)
CREATE INDEX IF NOT EXISTS idx_messages_created ON messages (created_at);

-- ---------------------------------------------------------------------
-- Membres d'une conversation = droit d'accès + curseur de lecture
-- Les clés étrangères composites garantissent que la personne appartient
-- bien à l'organisation de la conversation.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS conversation_members (
    conversation_id      UUID NOT NULL,
    user_id              UUID NOT NULL,
    org_id               UUID NOT NULL,
    role                 TEXT NOT NULL DEFAULT 'member' CHECK (role IN ('admin', 'member')),
    last_read_message_id BIGINT REFERENCES messages(id) ON DELETE SET NULL,
    muted                BOOLEAN NOT NULL DEFAULT FALSE,
    joined_at            TIMESTAMPTZ NOT NULL DEFAULT now(),
    PRIMARY KEY (conversation_id, user_id),
    FOREIGN KEY (conversation_id, org_id) REFERENCES conversations (id, org_id) ON DELETE CASCADE,
    FOREIGN KEY (org_id, user_id) REFERENCES org_members (org_id, user_id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_conv_members_user ON conversation_members (user_id);

-- ---------------------------------------------------------------------
-- Pièces jointes (contenu stocké dans la base, table séparée)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS attachments (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id      UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    message_id  BIGINT REFERENCES messages(id) ON DELETE CASCADE,   -- NULL tant que le message n'est pas envoyé
    uploaded_by UUID REFERENCES users(id) ON DELETE SET NULL,
    filename    TEXT NOT NULL,
    mime_type   TEXT NOT NULL,
    size_bytes  BIGINT NOT NULL CHECK (size_bytes > 0),
    sha256      TEXT,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS idx_attachments_message ON attachments (message_id);
CREATE INDEX IF NOT EXISTS idx_attachments_org ON attachments (org_id);

CREATE TABLE IF NOT EXISTS attachment_blobs (
    attachment_id UUID PRIMARY KEY REFERENCES attachments(id) ON DELETE CASCADE,
    data          BYTEA NOT NULL
);

COMMIT;
