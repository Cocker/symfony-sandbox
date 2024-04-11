<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240411104710 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ULID to User & UserLogin';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" ADD ulid UUID NOT NULL');
        $this->addSql('COMMENT ON COLUMN "user".ulid IS \'(DC2Type:ulid)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649C288C859 ON "user" (ulid)');
        $this->addSql('ALTER TABLE user_login ADD ulid UUID NOT NULL');
        $this->addSql('COMMENT ON COLUMN user_login.ulid IS \'(DC2Type:ulid)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_48CA3048C288C859 ON user_login (ulid)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX UNIQ_48CA3048C288C859');
        $this->addSql('ALTER TABLE user_login DROP ulid');
        $this->addSql('DROP INDEX UNIQ_8D93D649C288C859');
        $this->addSql('ALTER TABLE "user" DROP ulid');
    }
}
