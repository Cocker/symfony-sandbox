<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240422145340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add post comments';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE post_comment_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE post_comment (id INT NOT NULL, author_id INT NOT NULL, post_id INT NOT NULL, status VARCHAR(255) NOT NULL, content TEXT NOT NULL, ulid UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A99CE55FC288C859 ON post_comment (ulid)');
        $this->addSql('CREATE INDEX IDX_A99CE55FF675F31B ON post_comment (author_id)');
        $this->addSql('CREATE INDEX IDX_A99CE55F4B89032C ON post_comment (post_id)');
        $this->addSql('COMMENT ON COLUMN post_comment.ulid IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN post_comment.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN post_comment.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE post_comment ADD CONSTRAINT FK_A99CE55FF675F31B FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE post_comment ADD CONSTRAINT FK_A99CE55F4B89032C FOREIGN KEY (post_id) REFERENCES post (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE post_comment_id_seq CASCADE');
        $this->addSql('ALTER TABLE post_comment DROP CONSTRAINT FK_A99CE55FF675F31B');
        $this->addSql('ALTER TABLE post_comment DROP CONSTRAINT FK_A99CE55F4B89032C');
        $this->addSql('DROP TABLE post_comment');
    }
}
