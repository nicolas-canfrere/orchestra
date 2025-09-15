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
    process_id UUID PRIMARY KEY,
    status VARCHAR(100) NOT NULL,
    last_state_name VARCHAR(255) NOT NULL,
    executed_transitions JSONB NOT NULL,
    parameters JSONB,
    created_at TIMESTAMP NOT NULL,
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
