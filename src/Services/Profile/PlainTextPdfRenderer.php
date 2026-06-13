<?php

namespace App\Services\Profile;

final class PlainTextPdfRenderer
{
    private const PAGE_WIDTH = 595;
    private const PAGE_HEIGHT = 842;
    private const MARGIN_X = 48;
    private const MARGIN_TOP = 56;
    private const LINE_HEIGHT = 14;
    private const MAX_LINE_LENGTH = 92;

    public function render(string $text): string
    {
        $pages = array_chunk($this->wrapText($text), $this->linesPerPage());
        if ($pages === []) {
            $pages = [[]];
        }

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $pageReferences = [];
        $pageObjectStart = 3;
        $contentObjectStart = $pageObjectStart + count($pages);

        foreach ($pages as $index => $pageLines) {
            $pageObjectNumber = $pageObjectStart + $index;
            $contentObjectNumber = $contentObjectStart + $index;
            $pageReferences[] = sprintf('%d 0 R', $pageObjectNumber);
            $objects[] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %d %d] /Resources << /Font << /F1 %d 0 R >> >> /Contents %d 0 R >>',
                self::PAGE_WIDTH,
                self::PAGE_HEIGHT,
                $contentObjectStart + count($pages),
                $contentObjectNumber
            );
        }

        foreach ($pages as $pageLines) {
            $stream = $this->buildContentStream($pageLines);
            $objects[] = sprintf("<< /Length %d >>\nstream\n%s\nendstream", strlen($stream), $stream);
        }

        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        array_splice($objects, 1, 0, sprintf('<< /Type /Pages /Kids [%s] /Count %d >>', implode(' ', $pageReferences), count($pages)));

        return $this->buildPdf($objects);
    }

    /**
     * @return list<string>
     */
    private function wrapText(string $text): array
    {
        $lines = [];
        foreach (preg_split('/\R/u', $text) ?: [] as $rawLine) {
            $rawLine = rtrim($rawLine);
            if ($rawLine === '') {
                $lines[] = '';
                continue;
            }

            $wrapped = wordwrap($rawLine, self::MAX_LINE_LENGTH, "\n", true);
            foreach (explode("\n", $wrapped) as $line) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    private function linesPerPage(): int
    {
        return (int) floor((self::PAGE_HEIGHT - (2 * self::MARGIN_TOP)) / self::LINE_HEIGHT);
    }

    /**
     * @param list<string> $lines
     */
    private function buildContentStream(array $lines): string
    {
        $commands = ['BT', '/F1 10 Tf', sprintf('1 0 0 1 %d %d Tm', self::MARGIN_X, self::PAGE_HEIGHT - self::MARGIN_TOP)];

        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $commands[] = sprintf('0 -%d Td', self::LINE_HEIGHT);
            }

            $commands[] = sprintf('(%s) Tj', $this->escapePdfText($line));
        }

        $commands[] = 'ET';

        return implode("\n", $commands);
    }

    private function escapePdfText(string $text): string
    {
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);
        if ($encoded === false) {
            $encoded = preg_replace('/[^\x20-\x7E]/', '?', $text) ?? $text;
        }

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded);
    }

    /**
     * @param list<string> $objects
     */
    private function buildPdf(array $objects): string
    {
        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[$index + 1] = strlen($pdf);
            $pdf .= sprintf("%d 0 obj\n%s\nendobj\n", $index + 1, $object);
        }

        $xrefOffset = strlen($pdf);
        $pdf .= sprintf("xref\n0 %d\n", count($objects) + 1);
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); ++$i) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= sprintf(
            "trailer\n<< /Size %d /Root 1 0 R >>\nstartxref\n%d\n%%%%EOF\n",
            count($objects) + 1,
            $xrefOffset
        );

        return $pdf;
    }
}
