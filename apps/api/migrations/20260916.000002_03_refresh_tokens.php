<?php
declare(strict_types=1);
use Cycle\Migrations\Migration;
final class RefreshTokens extends Migration
{
    public function up(): void { $this->database()->execute('CREATE TABLE IF NOT EXISTS refresh_tokens (id BIGINT AUTO_INCREMENT PRIMARY KEY, user_id BIGINT NOT NULL, token_hash VARCHAR(64) NOT NULL UNIQUE, expires_at TIMESTAMP NOT NULL, revoked_at TIMESTAMP NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, CONSTRAINT refresh_tokens_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE)'); }
    public function down(): void { $this->database()->execute('DROP TABLE IF EXISTS refresh_tokens'); }
}
