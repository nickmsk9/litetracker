<?php

declare(strict_types=1);

namespace LiteTracker\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for authentication and validation helpers.
 *
 * Functions under test: lt_password_hash_value(), lt_password_verify_user(),
 * validusername(), validemail(), validfilename().
 * All are defined in tests/bootstrap.php (copied verbatim from functions.php).
 */
class AuthTest extends TestCase
{
    // -----------------------------------------------------------------------
    // lt_password_hash_value
    // -----------------------------------------------------------------------

    public function testHashValueReturnsBcryptString(): void
    {
        $hash = lt_password_hash_value('secret123');
        $this->assertStringStartsWith('$2y$', $hash);
    }

    public function testHashValueDifferentEachCall(): void
    {
        $h1 = lt_password_hash_value('password');
        $h2 = lt_password_hash_value('password');
        // bcrypt uses different salts each time
        $this->assertNotSame($h1, $h2);
    }

    // -----------------------------------------------------------------------
    // lt_password_verify_user — modern bcrypt path
    // -----------------------------------------------------------------------

    public function testVerifyModernHashSuccess(): void
    {
        $hash = lt_password_hash_value('mypassword');
        $userRow = ['password' => $hash, 'password_code' => ''];
        $needsRehash = false;

        $result = lt_password_verify_user('mypassword', $userRow, $needsRehash);

        $this->assertTrue($result);
        $this->assertFalse($needsRehash); // freshly generated hash is current
    }

    public function testVerifyModernHashWrongPassword(): void
    {
        $hash = lt_password_hash_value('correctpassword');
        $userRow = ['password' => $hash, 'password_code' => ''];

        $this->assertFalse(lt_password_verify_user('wrongpassword', $userRow));
    }

    public function testVerifyEmptyStoredHashReturnsFalse(): void
    {
        $userRow = ['password' => '', 'password_code' => ''];
        $this->assertFalse(lt_password_verify_user('anything', $userRow));
    }

    // -----------------------------------------------------------------------
    // lt_password_verify_user — legacy MD5 path
    // -----------------------------------------------------------------------

    public function testVerifyLegacyMd5Success(): void
    {
        $code   = 'abc123';
        $pass   = 'oldpassword';
        $legacy = md5($code . $pass . $code);
        $userRow = ['password' => $legacy, 'password_code' => $code];
        $needsRehash = false;

        $result = lt_password_verify_user($pass, $userRow, $needsRehash);

        $this->assertTrue($result);
        $this->assertTrue($needsRehash); // legacy hash must be upgraded
    }

    public function testVerifyLegacyMd5WrongPassword(): void
    {
        $code    = 'abc123';
        $legacy  = md5($code . 'correctpass' . $code);
        $userRow = ['password' => $legacy, 'password_code' => $code];

        $this->assertFalse(lt_password_verify_user('wrongpass', $userRow));
    }

    // -----------------------------------------------------------------------
    // validusername
    // -----------------------------------------------------------------------

    public function testValidUsernameAcceptsAlphanumeric(): void
    {
        $this->assertTrue((bool) validusername('user123'));
        $this->assertTrue((bool) validusername('User_Name'));
    }

    public function testValidUsernameAcceptsCyrillic(): void
    {
        $this->assertTrue((bool) validusername('Пользователь'));
    }

    public function testValidUsernameRejectsEmptyString(): void
    {
        $this->assertFalse((bool) validusername(''));
    }

    public function testValidUsernameRejectsSpecialChars(): void
    {
        $this->assertFalse((bool) validusername('user@name'));
        $this->assertFalse((bool) validusername('user name'));
        $this->assertFalse((bool) validusername('user!'));
    }

    // -----------------------------------------------------------------------
    // validemail
    // -----------------------------------------------------------------------

    public function testValidEmailAcceptsNormal(): void
    {
        $this->assertTrue((bool) validemail('user@example.com'));
        $this->assertTrue((bool) validemail('user.name+tag@sub.domain.co.uk'));
    }

    public function testValidEmailRejectsMissingAt(): void
    {
        $this->assertFalse((bool) validemail('notanemail'));
    }

    public function testValidEmailRejectsMissingDomain(): void
    {
        $this->assertFalse((bool) validemail('user@'));
    }

    public function testValidEmailRejectsEmptyString(): void
    {
        $this->assertFalse((bool) validemail(''));
    }

    // -----------------------------------------------------------------------
    // validfilename
    // -----------------------------------------------------------------------

    public function testValidFilenameAcceptsNormal(): void
    {
        $this->assertTrue((bool) validfilename('movie.torrent'));
        $this->assertTrue((bool) validfilename('My Film 2024 [1080p].torrent'));
    }

    public function testValidFilenameRejectsNullByte(): void
    {
        $this->assertFalse((bool) validfilename("bad\0name.torrent"));
    }

    public function testValidFilenameRejectsColon(): void
    {
        $this->assertFalse((bool) validfilename('bad:name.torrent'));
    }

    public function testValidFilenameRejectsBackslash(): void
    {
        $this->assertFalse((bool) validfilename('bad\\name.torrent'));
    }

    public function testValidFilenameRejectsQuestionMark(): void
    {
        $this->assertFalse((bool) validfilename('bad?name.torrent'));
    }

    public function testValidFilenameRejectsAsterisk(): void
    {
        $this->assertFalse((bool) validfilename('bad*name.torrent'));
    }
}
