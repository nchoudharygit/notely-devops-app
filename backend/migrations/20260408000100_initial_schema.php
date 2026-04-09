<?php
declare(strict_types=1);
use Phinx\Migration\AbstractMigration;
final class InitialSchema extends AbstractMigration
{
    public function change(): void
    {
        $this->execute("CREATE EXTENSION IF NOT EXISTS pgcrypto");
        $this->execute("
            CREATE TABLE users (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $this->execute("
            CREATE TABLE notes (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                title VARCHAR(255) NOT NULL,
                body TEXT NOT NULL DEFAULT '',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $this->execute("CREATE INDEX ON notes(user_id)");
        $this->execute("
            CREATE TABLE tags (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                name VARCHAR(100) NOT NULL,
                UNIQUE(user_id, name)
            )
        ");
        $this->execute("
            CREATE TABLE note_tags (
                note_id UUID NOT NULL REFERENCES notes(id) ON DELETE CASCADE,
                tag_id UUID NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
                PRIMARY KEY (note_id, tag_id)
            )
        ");
        $this->execute("CREATE INDEX ON note_tags(tag_id)");
        $this->execute("
            CREATE TABLE attachments (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                note_id UUID NOT NULL REFERENCES notes(id) ON DELETE CASCADE,
                storage_key TEXT NOT NULL,
                filename VARCHAR(255) NOT NULL,
                content_type VARCHAR(100) NOT NULL,
                size_bytes BIGINT NOT NULL,
                uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $this->execute("CREATE INDEX ON attachments(note_id)");
    }
}