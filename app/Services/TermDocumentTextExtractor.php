<?php

namespace App\Services;

use App\Models\TermDocument;
use App\Models\TermDocumentChunk;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Throwable;

class TermDocumentTextExtractor
{
    private const CHUNK_SIZE = 3000; // characters per chunk

    public function extract(TermDocument $document): void
    {
        $document->update(['processing_status' => 'processing']);

        try {
            $fullText = $this->readPdf($document);

            if (empty(trim($fullText))) {
                $document->update([
                    'processing_status' => 'failed',
                    'extraction_error'  => 'O PDF não contém texto extraível. Pode ser um documento escaneado que requer OCR.',
                ]);

                return;
            }

            $document->update([
                'extracted_text'    => $fullText,
                'processing_status' => 'processed',
                'processed_at'      => now(),
                'extraction_error'  => null,
            ]);

            $this->createChunks($document, $fullText);

        } catch (Throwable $e) {
            $document->update([
                'processing_status' => 'failed',
                'extraction_error'  => 'Erro ao processar o PDF: '.$e->getMessage(),
            ]);
        }
    }

    private function readPdf(TermDocument $document): string
    {
        $disk    = Storage::disk('local');
        $absPath = $disk->path($document->stored_path);

        $parser = new Parser();
        $pdf    = $parser->parseFile($absPath);

        $pages = $pdf->getPages();
        $parts = [];

        foreach ($pages as $i => $page) {
            $text = $page->getText();
            if (! empty(trim($text))) {
                $parts[] = $text;
            }
        }

        return implode("\n\n", $parts);
    }

    private function createChunks(TermDocument $document, string $text): void
    {
        // Remove existing chunks
        $document->chunks()->delete();

        // Split into paragraphs first, then group into chunks
        $paragraphs = preg_split('/\n{2,}/', $text) ?: [];
        $chunks     = [];
        $current    = '';
        $order      = 0;

        foreach ($paragraphs as $para) {
            $para = trim($para);
            if ($para === '') {
                continue;
            }

            if (strlen($current) + strlen($para) > self::CHUNK_SIZE && $current !== '') {
                $chunks[] = $current;
                $current  = $para;
            } else {
                $current .= ($current ? "\n\n" : '').$para;
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        foreach ($chunks as $i => $chunk) {
            // Try to detect a clause reference in the first line
            $firstLine = strtok($chunk, "\n") ?: '';
            $clauseRef = null;

            if (preg_match('/\b(cl[aá]usula|art\.?\s*\d+|§\s*\d+|\d+\.\d+)/i', $firstLine, $m)) {
                $clauseRef = $m[0];
            }

            TermDocumentChunk::create([
                'term_document_id' => $document->id,
                'page_number'      => null,
                'section_title'    => null,
                'clause_reference' => $clauseRef,
                'content'          => $chunk,
                'sort_order'       => $i,
            ]);

            $order++;
        }
    }
}
