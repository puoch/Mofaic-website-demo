<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Turns a visitor's search words into a MySQL boolean-mode full-text query
 * (PD-15): every word must appear, and a word also matches longer words that
 * start with it ("trade" finds "trader").
 *
 * Words MySQL does not index are left out: words shorter than InnoDB's
 * default minimum of 3 characters and InnoDB's default stopwords. If no word
 * is left, the caller falls back to a plain text match.
 */
class FullTextQuery
{
    public const MIN_WORD_LENGTH = 3;

    /** InnoDB's default stopword list (INFORMATION_SCHEMA.INNODB_FT_DEFAULT_STOPWORD). */
    private const STOPWORDS = [
        'a', 'about', 'an', 'are', 'as', 'at', 'be', 'by', 'com', 'de', 'en', 'for', 'from', 'how',
        'i', 'in', 'is', 'it', 'la', 'of', 'on', 'or', 'that', 'the', 'this', 'to', 'was', 'what',
        'when', 'where', 'who', 'will', 'with', 'und', 'www',
    ];

    /**
     * @return string|null the boolean-mode query, or null when no word can be searched
     */
    public static function booleanMode(string $search): ?string
    {
        // Letters and digits only, so full-text operators typed by a visitor
        // (+ - < > ( ) ~ * " @) never reach MySQL.
        preg_match_all('/[\p{L}\p{N}]+/u', mb_strtolower($search), $m);

        $words = [];
        foreach ($m[0] as $word) {
            if (mb_strlen($word) >= self::MIN_WORD_LENGTH && ! in_array($word, self::STOPWORDS, true)) {
                $words[$word] = '+' . $word . '*';
            }
        }

        return $words === [] ? null : implode(' ', array_slice($words, 0, 10));
    }
}
