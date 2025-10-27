<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251027163719 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE analyses DROP CONSTRAINT fk_ac86883cb03a8386');
        $this->addSql('ALTER TABLE analyses ADD CONSTRAINT FK_AC86883CB03A8386 FOREIGN KEY (created_by_id) REFERENCES auth.users (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE media_objects DROP CONSTRAINT fk_d3cd4abaa2b28fe8');
        $this->addSql('ALTER TABLE media_objects ADD CONSTRAINT FK_D3CD4ABAA2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES auth.users (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE sites DROP CONSTRAINT fk_bc00aa63b03a8386');
        $this->addSql('ALTER TABLE sites ADD CONSTRAINT FK_BC00AA63B03A8386 FOREIGN KEY (created_by_id) REFERENCES auth.users (id) ON DELETE RESTRICT NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE analyses DROP CONSTRAINT FK_AC86883CB03A8386');
        $this->addSql('ALTER TABLE analyses ADD CONSTRAINT fk_ac86883cb03a8386 FOREIGN KEY (created_by_id) REFERENCES auth.users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE media_objects DROP CONSTRAINT FK_D3CD4ABAA2B28FE8');
        $this->addSql('ALTER TABLE media_objects ADD CONSTRAINT fk_d3cd4abaa2b28fe8 FOREIGN KEY (uploaded_by_id) REFERENCES auth.users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sites DROP CONSTRAINT FK_BC00AA63B03A8386');
        $this->addSql('ALTER TABLE sites ADD CONSTRAINT fk_bc00aa63b03a8386 FOREIGN KEY (created_by_id) REFERENCES auth.users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
