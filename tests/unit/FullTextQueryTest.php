<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\FullTextQuery;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * PD-15: building the MySQL full-text query from a visitor's search words.
 */
final class FullTextQueryTest extends CIUnitTestCase
{
    public function testEveryWordIsRequiredAndMatchesAsAPrefix(): void
    {
        $this->assertSame('+trade* +agreement*', FullTextQuery::booleanMode('Trade agreement'));
    }

    public function testOperatorsTypedByTheVisitorAreRemoved(): void
    {
        $this->assertSame('+trade* +visa*', FullTextQuery::booleanMode('-trade "visa" (*) @3 ~<>'));
    }

    public function testShortWordsStopwordsAndRepeatsAreDropped(): void
    {
        $this->assertSame('+minister* +juba*', FullTextQuery::booleanMode('The Minister in Juba, minister'));
    }

    public function testReturnsNullWhenNoWordCanBeSearched(): void
    {
        $this->assertNull(FullTextQuery::booleanMode('UN'));
        $this->assertNull(FullTextQuery::booleanMode('the of'));
        $this->assertNull(FullTextQuery::booleanMode('   '));
    }

    public function testKeepsLettersFromOtherAlphabets(): void
    {
        $this->assertSame('+جوبا*', FullTextQuery::booleanMode('جوبا'));
    }

    public function testLimitsTheNumberOfWords(): void
    {
        $query = (string) FullTextQuery::booleanMode(implode(' ', array_map(static fn (int $i): string => 'word' . $i, range(1, 20))));

        $this->assertCount(10, explode(' ', $query));
    }
}
