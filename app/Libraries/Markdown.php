<?php

declare(strict_types=1);

namespace App\Libraries;

use League\CommonMark\CommonMarkConverter;

/**
 * Turns editor text (Markdown) into safe HTML.
 *
 * Raw HTML typed by editors is escaped, and javascript:, vbscript: and data:
 * links are removed, so editor text cannot inject scripts into public pages.
 */
class Markdown
{
    private readonly CommonMarkConverter $converter;

    public function __construct()
    {
        $this->converter = new CommonMarkConverter([
            'html_input'         => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level'  => 20,
        ]);
    }

    public function toHtml(?string $text): string
    {
        if ($text === null || trim($text) === '') {
            return '';
        }

        return (string) $this->converter->convert($text);
    }
}
