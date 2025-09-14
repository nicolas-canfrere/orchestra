<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250914065924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS public.process_execution_context (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    process_id UUID NOT NULL,
    status VARCHAR(100) NOT NULL,
    last_state_name VARCHAR(255) NOT NULL,
    executed_transitions JSONB NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    failure TEXT
);
SQL;

        $this->addSql($sql);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
