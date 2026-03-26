<?php

namespace Cesa\Document\Services;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use ZipArchive;

class DocxService
{
    /**
     * Convert a DOCX file to HTML while preserving shortcode placeholders like {{$KEY}}.
     * Returns HTML string. Placeholders pass through unchanged.
     */
    public function toHtml(string $absolutePath): string
    {
        // Ensure that PhpWord uses DOM-based HTML writer for better fidelity
        Settings::setOutputEscapingEnabled(false); // allow raw placeholders

        $phpWord = IOFactory::load($absolutePath, 'Word2007');

        // Export to HTML using temporary memory stream
        $tempFile = tmpfile();
        $meta = stream_get_meta_data($tempFile);
        $tempPath = $meta['uri'];

        $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
        $htmlWriter->save($tempPath);

        $html = file_get_contents($tempPath) ?: '';

        // PhpWord may HTML-escape braces; try to restore placeholders if escaped
        // Common encodings: {{ -> &#123;&#123; or &lbrace;&lbrace;
        $html = str_replace(['&#123;&#123;', '&#125;&#125;'], ['{{', '}}'], $html);
        $html = str_replace(['&lbrace;&lbrace;', '&rbrace;&rbrace;'], ['{{', '}}'], $html);

        // Also ensure $ sign stays intact
        $html = str_replace(['&#36;'], ['$'], $html);

        return $html;
    }

    /**
     * Try to re-save the DOCX via LibreOffice to normalize split runs so placeholders become contiguous.
     * Returns a path to the normalized DOCX, or the original path if normalization not possible.
     */
    private function normalizeDocxWithLibreOffice(string $absolutePath): string
    {
        // LibreOffice normalization disabled; return original file
        return $absolutePath;
    }

    /**
     * Extract placeholders like {{$KEY}} or {{KEY}} from a DOCX by scanning XML parts.
     * Returns unique uppercase keys in discovery order.
     *
     * @return array<int, string>
     */
    public function extractPlaceholders(string $absolutePath): array
    {
        $zip = new ZipArchive;
        if ($zip->open($absolutePath) !== true) {
            return [];
        }
        $xmlPaths = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = $stat['name'] ?? '';
            if (preg_match('/^word\/(document|header\d*|footer\d*|footnotes|endnotes)\.xml$/', $name)) {
                $xmlPaths[] = $name;
            }
        }

        $keys = [];
        $seen = [];
        foreach ($xmlPaths as $path) {
            $content = $zip->getFromName($path) ?: '';
            if ($content === '') {
                continue;
            }
            if (preg_match_all('/\{\{\s*\$?([A-Za-z0-9_]+)\s*\}\}/', $content, $m)) {
                foreach ($m[1] as $raw) {
                    $k = strtoupper($raw);
                    if (! isset($seen[$k])) {
                        $seen[$k] = true;
                        $keys[] = $k;
                    }
                }
            }

            // Reconstruct text from <w:t> runs to catch placeholders split across runs
            if (preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/si', $content, $tMatches)) {
                $text = '';
                foreach ($tMatches[1] as $frag) {
                    // Decode XML entities and accumulate
                    $frag = html_entity_decode($frag, ENT_QUOTES | ENT_XML1, 'UTF-8');
                    $text .= $frag;
                }
                if ($text !== '') {
                    // Normalize: remove zero-width spaces, unify full-width braces/dollar, normalize nbsp
                    $replacements = [
                        "\xE2\x80\x8B" => '', // zero-width space
                        "\xC2\xA0"     => ' ',   // nbsp
                    ];
                    $text = strtr($text, $replacements);
                    $text = str_replace(['｛', '｝', '＄'], ['{', '}', '$'], $text);
                    // Also collapse whitespace around braces
                    // Now extract placeholders from the reconstructed text
                    if (preg_match_all('/\{\{\s*\$?([A-Za-z0-9_]+)\s*\}\}/i', $text, $m2)) {
                        foreach ($m2[1] as $raw) {
                            $k = strtoupper($raw);
                            if (! isset($seen[$k])) {
                                $seen[$k] = true;
                                $keys[] = $k;
                            }
                        }
                    }
                }
            }
        }
        $zip->close();

        // Fallback: also parse via HTML conversion and union the results to catch split runs
        try {
            $html = $this->toHtml($absolutePath);
            if ($html !== '') {
                if (preg_match_all('/\{\{\s*\$?([A-Za-z0-9_]+)\s*\}\}/', $html, $m2)) {
                    foreach ($m2[1] as $raw) {
                        $k = strtoupper($raw);
                        if (! isset($seen[$k])) {
                            $seen[$k] = true;
                            $keys[] = $k;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return $keys;
    }

    /**
     * Generate a filled DOCX by replacing placeholders with provided values.
     * Supports placeholders {{$KEY}}, {{KEY}}, and ${KEY}, even jika terpecah di beberapa run (w:t).
     * Kami injeksi langsung di XML agar robust terhadap split runs/styling.
     * Returns the absolute path to a temporary output DOCX.
     *
     * @param  array<string, scalar|null>  $values
     */
    public function makeFilledDocx(string $absoluteTemplatePath, array $values): string
    {
        // Normalize runs via LibreOffice if available to increase success rate
        $source = $this->normalizeDocxWithLibreOffice($absoluteTemplatePath);
        $out = tempnam(sys_get_temp_dir(), 'docx_filled_').'.docx';
        copy($source, $out);

        $zip = new ZipArchive;
        if ($zip->open($out) !== true) {
            return $out;
        }
        $targets = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = $stat['name'] ?? '';
            if (preg_match('/^word\/(document|header\d*|footer\d*|footnotes|endnotes)\.xml$/', $name)) {
                $targets[] = $name;
            }
        }

        foreach ($targets as $xml) {
            $content = $zip->getFromName($xml);
            if ($content === false) {
                continue;
            }
            $new = $this->injectPlaceholdersXml($content, $values);
            if ($new !== null) {
                $zip->addFromString($xml, $new);
            }
        }
        $zip->close();

        return $out;
    }

    /**
     * Inject placeholders into a WordprocessingML part (document.xml, header/footer, etc.).
     * Handles placeholders spanning multiple <w:t> nodes within a paragraph.
     * Returns modified XML string, or null on failure.
     *
     * @param  array<string, scalar|null>  $values
     */
    private function injectPlaceholdersXml(string $xml, array $values): ?string
    {
        $dom = new \DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (@$dom->loadXML($xml) === false) {
            return null;
        }
        $xp = new \DOMXPath($dom);
        $xp->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        // Normalize values map
        $vals = [];
        foreach ($values as $k => $v) {
            $vals[strtoupper((string) $k)] = (string) ($v ?? '');
        }

        // Process each paragraph independently
        $paras = $xp->query('//w:p');
        if ($paras instanceof \DOMNodeList) {
            foreach ($paras as $p) {
                $tNodes = $xp->query('.//w:t', $p);
                if (! ($tNodes instanceof \DOMNodeList) || $tNodes->length === 0) {
                    continue;
                }

                // Build arrays of node texts and offsets
                $texts = [];
                $nodes = [];
                $offsets = [];
                $concat = '';
                $cursor = 0;
                foreach ($tNodes as $tn) {
                    $txt = $tn->textContent ?? '';
                    // normalize nbsp and zero-width spaces
                    $txt = str_replace(["\xC2\xA0", "\xE2\x80\x8B"], [' ', ''], $txt);
                    $texts[] = $txt;
                    $nodes[] = $tn;
                    $offsets[] = $cursor;
                    $concat .= $txt;
                    $cursor += mb_strlen($txt, 'UTF-8');
                }

                // Replace placeholders iteratively in the concatenated text
                $pattern = '/(\{\{\s*\$?([A-Za-z0-9_]+)\s*\}\}|\$\{\s*([A-Za-z0-9_]+)\s*\})/u';
                $searchStart = 0;
                while (preg_match($pattern, $concat, $m, PREG_OFFSET_CAPTURE, $searchStart)) {
                    $full = $m[1][0];
                    $fullPos = $m[1][1];
                    $key = strtoupper($m[2][0] !== '' ? $m[2][0] : $m[3][0]);
                    $fullLen = mb_strlen($full, 'UTF-8');
                    $replace = $vals[$key] ?? '';

                    // Map start and end to node indices
                    $startChar = $fullPos;
                    $endChar = $fullPos + $fullLen; // exclusive

                    // Find first node index
                    $firstIdx = 0;
                    $lastIdx = 0;
                    for ($i = 0; $i < count($nodes); $i++) {
                        $start = $offsets[$i];
                        $end = $start + mb_strlen($texts[$i], 'UTF-8');
                        if ($startChar >= $start && $startChar < $end) {
                            $firstIdx = $i;
                            break;
                        }
                    }
                    for ($i = $firstIdx; $i < count($nodes); $i++) {
                        $start = $offsets[$i];
                        $end = $start + mb_strlen($texts[$i], 'UTF-8');
                        if ($endChar <= $end) {
                            $lastIdx = $i;
                            break;
                        }
                        $lastIdx = $i;
                    }

                    // Compute splits for first and last nodes
                    $firstStart = $offsets[$firstIdx];
                    $leftLen = max(0, $startChar - $firstStart);
                    $firstText = $texts[$firstIdx];
                    $left = mb_substr($firstText, 0, $leftLen, 'UTF-8');

                    $lastStart = $offsets[$lastIdx];
                    $lastText = $texts[$lastIdx];
                    $rightCutPos = max(0, $endChar - $lastStart);
                    $right = mb_substr($lastText, $rightCutPos, null, 'UTF-8');

                    // Set first node text to left + replacement + (if same node) right
                    if ($firstIdx === $lastIdx) {
                        $newText = $left.$replace.$right;
                        $texts[$firstIdx] = $newText;
                        $nodes[$firstIdx]->nodeValue = '';
                        $nodes[$firstIdx]->appendChild($dom->createTextNode($newText));
                    } else {
                        $texts[$firstIdx] = $left.$replace;
                        $nodes[$firstIdx]->nodeValue = '';
                        $nodes[$firstIdx]->appendChild($dom->createTextNode($texts[$firstIdx]));
                        // Middle nodes cleared
                        for ($i = $firstIdx + 1; $i < $lastIdx; $i++) {
                            $texts[$i] = '';
                            $nodes[$i]->nodeValue = '';
                        }
                        // Last node keeps right tail
                        $texts[$lastIdx] = $right;
                        $nodes[$lastIdx]->nodeValue = '';
                        if ($right !== '') {
                            $nodes[$lastIdx]->appendChild($dom->createTextNode($right));
                        }
                    }

                    // Update concatenated string and offsets
                    $concat = mb_substr($concat, 0, $startChar, 'UTF-8')
                        .$replace
                        .mb_substr($concat, $endChar, null, 'UTF-8');
                    // Recompute offsets from firstIdx onward
                    $cursor = $offsets[$firstIdx];
                    for ($i = $firstIdx; $i < count($nodes); $i++) {
                        $offsets[$i] = $cursor;
                        $cursor += mb_strlen($texts[$i], 'UTF-8');
                    }
                    // Continue search after the inserted replacement
                    $searchStart = $startChar + mb_strlen($replace, 'UTF-8');
                }
            }
        }

        return $dom->saveXML();
    }
}
