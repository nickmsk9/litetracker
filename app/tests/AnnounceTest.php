<?php

declare(strict_types=1);

namespace LiteTracker\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for pure announce helper functions.
 *
 * Functions under test come from system/functions/functions.announce.php.
 * All functions are pure (no DB, no side-effects) and are loaded in bootstrap.php.
 */
class AnnounceTest extends TestCase
{
    // -----------------------------------------------------------------------
    // announce_ensure_string_length
    // -----------------------------------------------------------------------

    public function testEnsureStringLengthReturnsValue(): void
    {
        $result = announce_ensure_string_length(str_repeat('x', 20), 20, 'info_hash');
        $this->assertSame(str_repeat('x', 20), $result);
    }

    public function testEnsureStringLengthCoercesToString(): void
    {
        // Integer "12345" has 5 chars
        $result = announce_ensure_string_length(12345, 5, 'test');
        $this->assertSame('12345', $result);
    }

    public function testEnsureStringLengthThrowsOnWrongLength(): void
    {
        $this->expectException(\RuntimeException::class);
        announce_ensure_string_length('tooshort', 20, 'info_hash');
    }

    public function testEnsureStringLengthThrowsOnEmpty(): void
    {
        $this->expectException(\RuntimeException::class);
        announce_ensure_string_length('', 20, 'info_hash');
    }

    public function testEnsureStringLengthThrowsOnTooLong(): void
    {
        $this->expectException(\RuntimeException::class);
        announce_ensure_string_length(str_repeat('x', 21), 20, 'peer_id');
    }

    // -----------------------------------------------------------------------
    // announce_get_string_param / announce_get_int_param
    // -----------------------------------------------------------------------

    public function testGetStringParamReturnsGetValue(): void
    {
        $_GET['info_hash'] = 'abc123';
        $this->assertSame('abc123', announce_get_string_param('info_hash'));
    }

    public function testGetStringParamReturnEmptyWhenMissing(): void
    {
        unset($_GET['missing_key']);
        $this->assertSame('', announce_get_string_param('missing_key'));
    }

    public function testGetIntParamReturnsCastInt(): void
    {
        $_GET['port'] = '6881';
        $this->assertSame(6881, announce_get_int_param('port'));
    }

    public function testGetIntParamReturnsZeroWhenMissing(): void
    {
        unset($_GET['missing_int']);
        $this->assertSame(0, announce_get_int_param('missing_int'));
    }

    public function testGetIntParamIgnoresNonNumeric(): void
    {
        $_GET['left'] = 'abc';
        $this->assertSame(0, announce_get_int_param('left'));
    }

    // -----------------------------------------------------------------------
    // announce_numwant
    // -----------------------------------------------------------------------

    public function testNumwantReturnsDefaultWhenNoGetParam(): void
    {
        unset($_GET['numwant'], $_GET['num_want'], $_GET['num want']);
        $this->assertSame(50, announce_numwant(50));
    }

    public function testNumwantReadsNumwantKey(): void
    {
        $_GET['numwant'] = '30';
        unset($_GET['num_want'], $_GET['num want']);
        $this->assertSame(30, announce_numwant(50));
    }

    public function testNumwantEnforcesMinimumOfOne(): void
    {
        $_GET['numwant'] = '0';
        $this->assertSame(1, announce_numwant(50));
    }

    public function testNumwantEnforcesMinimumOfOneOnNegative(): void
    {
        $_GET['numwant'] = '-5';
        $this->assertSame(1, announce_numwant(50));
    }

    protected function tearDown(): void
    {
        // Clean up global $_GET after each test to avoid inter-test pollution
        $_GET = [];
    }
}
