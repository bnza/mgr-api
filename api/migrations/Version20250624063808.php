<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250624063808 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE auth.site_user_privileges (id UUID NOT NULL, privilege INT NOT NULL, user_id UUID NOT NULL, site_id BIGINT NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_7FEC3DADA76ED395 ON auth.site_user_privileges (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_7FEC3DADF6BD1646 ON auth.site_user_privileges (site_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_7FEC3DADA76ED395F6BD1646 ON auth.site_user_privileges (user_id, site_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE auth.site_user_privileges ADD CONSTRAINT FK_7FEC3DADA76ED395 FOREIGN KEY (user_id) REFERENCES auth.users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE auth.site_user_privileges ADD CONSTRAINT FK_7FEC3DADF6BD1646 FOREIGN KEY (site_id) REFERENCES sites (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sites ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sites ADD created_by_id UUID DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sites ADD CONSTRAINT FK_BC00AA63B03A8386 FOREIGN KEY (created_by_id) REFERENCES auth.users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_BC00AA63B03A8386 ON sites (created_by_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE auth.site_user_privileges DROP CONSTRAINT FK_7FEC3DADA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE auth.site_user_privileges DROP CONSTRAINT FK_7FEC3DADF6BD1646
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE auth.site_user_privileges
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sites DROP CONSTRAINT FK_BC00AA63B03A8386
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_BC00AA63B03A8386
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sites DROP created_at
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sites DROP created_by_id
        SQL);
    }
}
