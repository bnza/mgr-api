<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250816075649 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE potteries_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE vocabulary.pottery_functional_forms_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE vocabulary.pottery_functional_groups_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE vocabulary.pottery_shapes_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE potteries (id BIGINT NOT NULL, inventory VARCHAR(255) NOT NULL, chronology_lower INT DEFAULT NULL, chronology_upper INT DEFAULT NULL, notes TEXT DEFAULT NULL, stratigraphic_unit_id BIGINT NOT NULL, cultural_context_id SMALLINT DEFAULT NULL, part_id SMALLINT DEFAULT NULL, functional_group_id SMALLINT NOT NULL, functional_form_id SMALLINT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_91910162B12D4A36 ON potteries (inventory)');
        $this->addSql('CREATE INDEX IDX_91910162A502ADE ON potteries (stratigraphic_unit_id)');
        $this->addSql('CREATE INDEX IDX_9191016271C15152 ON potteries (cultural_context_id)');
        $this->addSql('CREATE INDEX IDX_919101624CE34BEC ON potteries (part_id)');
        $this->addSql('CREATE INDEX IDX_919101629D56156 ON potteries (functional_group_id)');
        $this->addSql('CREATE INDEX IDX_91910162BC19D7E9 ON potteries (functional_form_id)');
        $this->addSql('CREATE TABLE vocabulary.pottery_functional_forms (id SMALLINT NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_703164D21D775834 ON vocabulary.pottery_functional_forms (value)');
        $this->addSql('CREATE TABLE vocabulary.pottery_functional_groups (id SMALLINT NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BBE4E3481D775834 ON vocabulary.pottery_functional_groups (value)');
        $this->addSql('CREATE TABLE vocabulary.pottery_shapes (id SMALLINT NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FE2DA1D31D775834 ON vocabulary.pottery_shapes (value)');
        $this->addSql('ALTER TABLE potteries ADD CONSTRAINT FK_91910162A502ADE FOREIGN KEY (stratigraphic_unit_id) REFERENCES sus (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE potteries ADD CONSTRAINT FK_9191016271C15152 FOREIGN KEY (cultural_context_id) REFERENCES vocabulary.cultural_contexts (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE potteries ADD CONSTRAINT FK_919101624CE34BEC FOREIGN KEY (part_id) REFERENCES vocabulary.pottery_shapes (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE potteries ADD CONSTRAINT FK_919101629D56156 FOREIGN KEY (functional_group_id) REFERENCES vocabulary.pottery_functional_groups (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE potteries ADD CONSTRAINT FK_91910162BC19D7E9 FOREIGN KEY (functional_form_id) REFERENCES vocabulary.pottery_functional_forms (id) ON DELETE RESTRICT NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE potteries_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE vocabulary.pottery_functional_forms_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE vocabulary.pottery_functional_groups_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE vocabulary.pottery_shapes_id_seq CASCADE');
        $this->addSql('ALTER TABLE potteries DROP CONSTRAINT FK_91910162A502ADE');
        $this->addSql('ALTER TABLE potteries DROP CONSTRAINT FK_9191016271C15152');
        $this->addSql('ALTER TABLE potteries DROP CONSTRAINT FK_919101624CE34BEC');
        $this->addSql('ALTER TABLE potteries DROP CONSTRAINT FK_919101629D56156');
        $this->addSql('ALTER TABLE potteries DROP CONSTRAINT FK_91910162BC19D7E9');
        $this->addSql('DROP TABLE potteries');
        $this->addSql('DROP TABLE vocabulary.pottery_functional_forms');
        $this->addSql('DROP TABLE vocabulary.pottery_functional_groups');
        $this->addSql('DROP TABLE vocabulary.pottery_shapes');
    }
}
