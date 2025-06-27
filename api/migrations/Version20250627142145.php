<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250627142145 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE samples (id BIGINT NOT NULL, site_id BIGINT NOT NULL, year INT NOT NULL, number VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, su_id BIGINT DEFAULT NULL, context_id BIGINT DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_19925777BDB1218E ON samples (su_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_199257776B00C1CF ON samples (context_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_19925777F6BD164696901F54 ON samples (site_id, number)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE samples ADD CONSTRAINT FK_19925777BDB1218E FOREIGN KEY (su_id) REFERENCES sus (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE samples ADD CONSTRAINT FK_199257776B00C1CF FOREIGN KEY (context_id) REFERENCES contexts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE samples DROP CONSTRAINT FK_19925777BDB1218E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE samples DROP CONSTRAINT FK_199257776B00C1CF
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE samples
        SQL);
    }
}
