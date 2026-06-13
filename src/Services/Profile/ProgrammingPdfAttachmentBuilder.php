<?php

namespace App\Services\Profile;

final readonly class ProgrammingPdfAttachmentBuilder
{
    public function __construct(
        private PlainTextPdfRenderer $pdfRenderer,
    ) {
    }

    /**
     * @param array<string, mixed> $programming
     */
    public function buildProgrammingPdf(array $programming): string
    {
        return $this->pdfRenderer->render($this->buildDocument('Programmation MonWod', $programming));
    }

    /**
     * @param array<string, mixed> $detailedProgramming
     */
    public function buildSessionDetailsPdf(array $detailedProgramming): string
    {
        return $this->pdfRenderer->render($this->buildDocument('Seances detaillees MonWod', $detailedProgramming));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildDocument(string $title, array $payload): string
    {
        $lines = [
            $title,
            str_repeat('=', strlen($title)),
            '',
        ];

        $this->appendValue($lines, $payload, 0);

        return implode("\n", $lines);
    }

    /**
     * @param list<string> $lines
     */
    private function appendValue(array &$lines, mixed $value, int $indent, ?string $key = null): void
    {
        $prefix = str_repeat('  ', $indent);

        if (is_array($value)) {
            if ($key !== null) {
                $lines[] = sprintf('%s%s:', $prefix, $this->humanizeKey($key));
            }

            if ($value === []) {
                $lines[] = sprintf('%s  -', $prefix);

                return;
            }

            if (array_is_list($value)) {
                foreach ($value as $index => $item) {
                    if (is_array($item)) {
                        $lines[] = sprintf('%s- Element %d', $prefix, $index + 1);
                        $this->appendValue($lines, $item, $indent + 1);
                    } else {
                        $lines[] = sprintf('%s- %s', $prefix, $this->scalarToString($item));
                    }
                }

                return;
            }

            foreach ($value as $childKey => $childValue) {
                $this->appendValue($lines, $childValue, $key === null ? $indent : $indent + 1, (string) $childKey);
            }

            return;
        }

        if ($key === null) {
            $lines[] = sprintf('%s%s', $prefix, $this->scalarToString($value));

            return;
        }

        $lines[] = sprintf('%s%s: %s', $prefix, $this->humanizeKey($key), $this->scalarToString($value));
    }

    private function humanizeKey(string $key): string
    {
        $label = str_replace(['_', '-'], ' ', $key);
        $label = preg_replace('/(?<!^)[A-Z]/', ' $0', $label) ?? $label;

        return ucfirst(trim($label));
    }

    private function scalarToString(mixed $value): string
    {
        if ($value === null) {
            return '-';
        }

        if (is_bool($value)) {
            return $value ? 'oui' : 'non';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '-';
    }
}
