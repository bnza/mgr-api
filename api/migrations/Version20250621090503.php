<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250621090503 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(
            <<<'SQL'
            CREATE SCHEMA auth
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE SCHEMA vocabulary
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE SEQUENCE analyses_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE SEQUENCE context_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE SEQUENCE stratigraphic_units_relationships_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE TABLE analyses (id BIGINT NOT NULL, type SMALLINT NOT NULL, status SMALLINT NOT NULL, description TEXT DEFAULT NULL, su_id BIGINT NOT NULL, context_id BIGINT NOT NULL, sample_id BIGINT NOT NULL, PRIMARY KEY(id))
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE INDEX IDX_AC86883CBDB1218E ON analyses (su_id)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE INDEX IDX_AC86883C6B00C1CF ON analyses (context_id)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE INDEX IDX_AC86883C1B1FEA20 ON analyses (sample_id)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE TABLE context_stratigraphic_units (id BIGINT NOT NULL, su_id BIGINT NOT NULL, context_id BIGINT NOT NULL, PRIMARY KEY(id))
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE INDEX IDX_A2BE5B62BDB1218E ON context_stratigraphic_units (su_id)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE INDEX IDX_A2BE5B626B00C1CF ON context_stratigraphic_units (context_id)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE UNIQUE INDEX UNIQ_A2BE5B62BDB1218E6B00C1CF ON context_stratigraphic_units (su_id, context_id)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE TABLE contexts (id BIGINT NOT NULL, type SMALLINT NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, site_id BIGINT NOT NULL, PRIMARY KEY(id))
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE INDEX IDX_AC51CEB5F6BD1646 ON contexts (site_id)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE UNIQUE INDEX UNIQ_AC51CEB5F6BD16468CDE57295E237E06 ON contexts (site_id, type, name)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE TABLE samples (id BIGINT NOT NULL, site_id BIGINT NOT NULL, year INT NOT NULL, number VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, su_id BIGINT DEFAULT NULL, context_id BIGINT DEFAULT NULL, PRIMARY KEY(id))
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE INDEX IDX_19925777BDB1218E ON samples (su_id)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE INDEX IDX_199257776B00C1CF ON samples (context_id)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE UNIQUE INDEX UNIQ_19925777F6BD164696901F54 ON samples (site_id, number)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE TABLE auth.site_user_privileges (id UUID NOT NULL, privilege INT NOT NULL, user_id UUID NOT NULL, site_id BIGINT NOT NULL, PRIMARY KEY(id))
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE INDEX IDX_7FEC3DADA76ED395 ON auth.site_user_privileges (user_id)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE INDEX IDX_7FEC3DADF6BD1646 ON auth.site_user_privileges (site_id)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE UNIQUE INDEX UNIQ_7FEC3DADA76ED395F6BD1646 ON auth.site_user_privileges (user_id, site_id)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE TABLE sites (id BIGINT NOT NULL, code VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_by_id UUID DEFAULT NULL, PRIMARY KEY(id))
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE UNIQUE INDEX UNIQ_BC00AA6377153098 ON sites (code)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE UNIQUE INDEX UNIQ_BC00AA635E237E06 ON sites (name)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE INDEX IDX_BC00AA63B03A8386 ON sites (created_by_id)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE TABLE stratigraphic_units_relationships (id BIGINT NOT NULL, lft_su_id BIGINT NOT NULL, relationship_id CHAR(1) NOT NULL, rgt_su_id BIGINT NOT NULL, PRIMARY KEY(id))
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE INDEX IDX_14B3FD8DD4B657AB ON stratigraphic_units_relationships (lft_su_id)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE INDEX IDX_14B3FD8D2C41D668 ON stratigraphic_units_relationships (relationship_id)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE INDEX IDX_14B3FD8D7C1ECED6 ON stratigraphic_units_relationships (rgt_su_id)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE UNIQUE INDEX UNIQ_14B3FD8DD4B657AB7C1ECED6 ON stratigraphic_units_relationships (lft_su_id, rgt_su_id)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE TABLE vocabulary.su_relationships (id CHAR(1) NOT NULL, value VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, inverted_by_id CHAR(1) DEFAULT NULL, PRIMARY KEY(id))
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE UNIQUE INDEX UNIQ_319E6DFF1D775834 ON vocabulary.su_relationships (value)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE UNIQUE INDEX UNIQ_319E6DFFC4CDAD40 ON vocabulary.su_relationships (inverted_by_id)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE TABLE sus (id BIGINT NOT NULL, year INT NOT NULL, number INT NOT NULL, description TEXT DEFAULT NULL, interpretation TEXT DEFAULT NULL, site_id BIGINT NOT NULL, PRIMARY KEY(id))
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE INDEX IDX_32B2A22EF6BD1646 ON sus (site_id)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE UNIQUE INDEX UNIQ_32B2A22EF6BD164696901F54 ON sus (site_id, number)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE TABLE auth.users (id UUID NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, roles TEXT NOT NULL, PRIMARY KEY(id))
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE UNIQUE INDEX UNIQ_18DF2AF8E7927C74 ON auth.users (email)
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE analyses ADD CONSTRAINT FK_AC86883CBDB1218E FOREIGN KEY (su_id) REFERENCES sus (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE analyses ADD CONSTRAINT FK_AC86883C6B00C1CF FOREIGN KEY (context_id) REFERENCES contexts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE analyses ADD CONSTRAINT FK_AC86883C1B1FEA20 FOREIGN KEY (sample_id) REFERENCES samples (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE context_stratigraphic_units ADD CONSTRAINT FK_A2BE5B62BDB1218E FOREIGN KEY (su_id) REFERENCES sus (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE context_stratigraphic_units ADD CONSTRAINT FK_A2BE5B626B00C1CF FOREIGN KEY (context_id) REFERENCES contexts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE contexts ADD CONSTRAINT FK_AC51CEB5F6BD1646 FOREIGN KEY (site_id) REFERENCES sites (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE samples ADD CONSTRAINT FK_19925777BDB1218E FOREIGN KEY (su_id) REFERENCES sus (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE samples ADD CONSTRAINT FK_199257776B00C1CF FOREIGN KEY (context_id) REFERENCES contexts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE auth.site_user_privileges ADD CONSTRAINT FK_7FEC3DADA76ED395 FOREIGN KEY (user_id) REFERENCES auth.users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE auth.site_user_privileges ADD CONSTRAINT FK_7FEC3DADF6BD1646 FOREIGN KEY (site_id) REFERENCES sites (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE sites ADD CONSTRAINT FK_BC00AA63B03A8386 FOREIGN KEY (created_by_id) REFERENCES auth.users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE stratigraphic_units_relationships ADD CONSTRAINT FK_14B3FD8DD4B657AB FOREIGN KEY (lft_su_id) REFERENCES sus (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE stratigraphic_units_relationships ADD CONSTRAINT FK_14B3FD8D2C41D668 FOREIGN KEY (relationship_id) REFERENCES vocabulary.su_relationships (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE stratigraphic_units_relationships ADD CONSTRAINT FK_14B3FD8D7C1ECED6 FOREIGN KEY (rgt_su_id) REFERENCES sus (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE vocabulary.su_relationships ADD CONSTRAINT FK_319E6DFFC4CDAD40 FOREIGN KEY (inverted_by_id) REFERENCES vocabulary.su_relationships (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE sus ADD CONSTRAINT FK_32B2A22EF6BD1646 FOREIGN KEY (site_id) REFERENCES sites (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(
            <<<'SQL'
            DROP SEQUENCE analyses_id_seq CASCADE
        SQL
        );
        $this->addSql(
            <<<'SQL'
            DROP SEQUENCE context_id_seq CASCADE
        SQL
        );
        $this->addSql(
            <<<'SQL'
            DROP SEQUENCE stratigraphic_units_relationships_id_seq CASCADE
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE analyses DROP CONSTRAINT FK_AC86883CBDB1218E
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE analyses DROP CONSTRAINT FK_AC86883C6B00C1CF
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE analyses DROP CONSTRAINT FK_AC86883C1B1FEA20
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE context_stratigraphic_units DROP CONSTRAINT FK_A2BE5B62BDB1218E
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE context_stratigraphic_units DROP CONSTRAINT FK_A2BE5B626B00C1CF
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE contexts DROP CONSTRAINT FK_AC51CEB5F6BD1646
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE samples DROP CONSTRAINT FK_19925777BDB1218E
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE samples DROP CONSTRAINT FK_199257776B00C1CF
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE auth.site_user_privileges DROP CONSTRAINT FK_7FEC3DADA76ED395
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE auth.site_user_privileges DROP CONSTRAINT FK_7FEC3DADF6BD1646
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE sites DROP CONSTRAINT FK_BC00AA63B03A8386
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE stratigraphic_units_relationships DROP CONSTRAINT FK_14B3FD8DD4B657AB
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE stratigraphic_units_relationships DROP CONSTRAINT FK_14B3FD8D2C41D668
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE stratigraphic_units_relationships DROP CONSTRAINT FK_14B3FD8D7C1ECED6
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE vocabulary.su_relationships DROP CONSTRAINT FK_319E6DFFC4CDAD40
        SQL
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE sus DROP CONSTRAINT FK_32B2A22EF6BD1646
        SQL
        );
        $this->addSql(
            <<<'SQL'
            DROP TABLE analyses
        SQL
        );
        $this->addSql(
            <<<'SQL'
            DROP TABLE context_stratigraphic_units
        SQL
        );
        $this->addSql(
            <<<'SQL'
            DROP TABLE contexts
        SQL
        );
        $this->addSql(
            <<<'SQL'
            DROP TABLE samples
        SQL
        );
        $this->addSql(
            <<<'SQL'
            DROP TABLE auth.site_user_privileges
        SQL
        );
        $this->addSql(
            <<<'SQL'
            DROP TABLE sites
        SQL
        );
        $this->addSql(
            <<<'SQL'
            DROP TABLE stratigraphic_units_relationships
        SQL
        );
        $this->addSql(
            <<<'SQL'
            DROP TABLE vocabulary.su_relationships
        SQL
        );
        $this->addSql(
            <<<'SQL'
            DROP TABLE sus
        SQL
        );
        $this->addSql(
            <<<'SQL'
            DROP TABLE auth.users
        SQL
        );
    }
}
