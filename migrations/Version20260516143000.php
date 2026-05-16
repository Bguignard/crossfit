<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260516143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Recompute athlete normalized names with PHP transliteration.';
    }

    public function up(Schema $schema): void
    {
        $rows = $this->connection->fetchAllAssociative('SELECT id, display_name FROM athlete');

        foreach ($rows as $row) {
            $this->connection->update('athlete', [
                'normalized_name' => $this->normalizeName((string) $row['display_name']),
            ], [
                'id' => $row['id'],
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE athlete SET normalized_name = NULL');
    }

    private function normalizeName(string $name): string
    {
        $normalized = trim($name);
        if ($normalized === '') {
            return '';
        }

        if (function_exists('transliterator_transliterate')) {
            $transliterated = transliterator_transliterate('Any-Latin; Latin-ASCII', $normalized);
            if (is_string($transliterated)) {
                $normalized = $transliterated;
            }
        } else {
            $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
            if (is_string($transliterated)) {
                $normalized = $transliterated;
            }
        }

        $normalized = strtolower($normalized);
        $normalized = str_replace(['-', '_'], ' ', $normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? $normalized;

        return trim(preg_replace('/\s+/', ' ', $normalized) ?? $normalized);
    }
}
