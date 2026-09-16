<?php
declare(strict_types=1);
use Cycle\Migrations\Migration;
final class RefreshTokens extends Migration
{
    public function up(): void { $this->database()->execute('CREATE TABLE IF NOT EXISTS refresh_tokens (id BIGSERIAL PRIMARY KEY, user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE, token_hash VARCHAR(64) NOT NULL UNIQUE, expires_at TIMESTAMPTZ NOT NULL, revoked_at TIMESTAMPTZ, created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP)'); }
    public function down(): void { $this->database()->execute('DROP TABLE IF EXISTS refresh_tokens'); }
}
