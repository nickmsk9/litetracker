<?php
/*
 * File: class.smtp.php
 *
 * Description: SMTP class — RFC 821 compliant.
 *              Implements all RFC 821 SMTP commands except TURN.
 *
 * Original author: Chris Ryan <chris@greatbridge.com>
 * Refactored: extracted sendAndCheck() / initCmd() helpers to eliminate
 *             the ~20-line boilerplate repeated in every command method.
 */

/**
 * SMTP — RFC 821 compliant SMTP client.
 *
 * Refactoring notes vs. the original PHP4 class:
 *  - Removed the PHP4-style constructor alias SMTP(); __construct() is canonical.
 *  - `var` declarations replaced with typed properties.
 *  - fputs() → fwrite(); socket_*() → stream_*() (deprecated aliases removed).
 *  - Two private helpers extracted per the original TODO:
 *      initCmd()      — resets $this->error and verifies connection.
 *      sendAndCheck() — sends a raw SMTP line, reads the reply, checks the
 *                       response code, sets $this->error on mismatch.
 *  - All command methods now delegate to those helpers; duplication is gone.
 */
class SMTP
{
    public int    $SMTP_PORT = 25;
    public string $CRLF      = "\r\n";

    /** @var resource|false */
    public mixed  $smtp_conn  = false;
    public ?array $error      = null;
    public ?string $helo_rply = null;
    public int    $do_debug   = 0;

    public function __construct()
    {
        $this->smtp_conn = false;
        $this->error     = null;
        $this->helo_rply = null;
        $this->do_debug  = 0;
    }

    // =========================================================================
    //  CONNECTION
    // =========================================================================

    /**
     * Open a TCP connection to $host:$port within $tval seconds.
     *
     * SMTP CODE SUCCESS: 220
     * SMTP CODE FAILURE: 421
     */
    public function Connect(string $host, int $port = 0, int $tval = 30): bool
    {
        $this->error = null;

        if ($this->connected()) {
            $this->error = ['error' => 'Already connected to a server'];
            return false;
        }

        if ($port <= 0) {
            $port = $this->SMTP_PORT;
        }

        $this->smtp_conn = fsockopen($host, $port, $errno, $errstr, $tval);

        if (!is_resource($this->smtp_conn)) {
            $this->error = [
                'error'  => 'Failed to connect to server',
                'errno'  => $errno,
                'errstr' => $errstr,
            ];
            if ($this->do_debug >= 1) {
                echo 'SMTP -> ERROR: ' . $this->error['error'] . ": $errstr ($errno)" . $this->CRLF;
            }
            return false;
        }

        stream_set_timeout($this->smtp_conn, 1, 0);
        $announce = $this->get_lines();
        stream_set_timeout($this->smtp_conn, 0, 100000);

        if ($this->do_debug >= 2) {
            echo 'SMTP -> FROM SERVER:' . $this->CRLF . $announce;
        }

        return true;
    }

    /**
     * Returns true when an active connection is open.
     */
    public function Connected(): bool
    {
        if (!is_resource($this->smtp_conn)) {
            return false;
        }

        $meta = stream_get_meta_data($this->smtp_conn);
        if ($meta['eof']) {
            if ($this->do_debug >= 1) {
                echo 'SMTP -> NOTICE:' . $this->CRLF . 'EOF caught while checking if connected';
            }
            $this->Close();
            return false;
        }

        return true;
    }

    /**
     * Close the socket. Call Quit() first when possible.
     */
    public function Close(): void
    {
        $this->error     = null;
        $this->helo_rply = null;

        if (is_resource($this->smtp_conn)) {
            fclose($this->smtp_conn);
            $this->smtp_conn = false;
        }
    }

    // =========================================================================
    //  SMTP COMMANDS
    // =========================================================================

    /**
     * DATA — send message body.
     *
     * SMTP CODE INTERMEDIATE : 354
     * SMTP CODE SUCCESS       : 250
     */
    public function Data(string $msg_data): bool
    {
        if (!$this->initCmd(__FUNCTION__)) {
            return false;
        }

        fwrite($this->smtp_conn, 'DATA' . $this->CRLF);

        $rply = $this->get_lines();
        $code = substr($rply, 0, 3);

        if ($this->do_debug >= 2) {
            echo 'SMTP -> FROM SERVER:' . $this->CRLF . $rply;
        }

        if ($code !== '354') {
            $this->error = [
                'error'     => 'DATA command not accepted from server',
                'smtp_code' => $code,
                'smtp_msg'  => substr($rply, 4),
            ];
            if ($this->do_debug >= 1) {
                echo 'SMTP -> ERROR: ' . $this->error['error'] . ': ' . $rply . $this->CRLF;
            }
            return false;
        }

        $msg_data   = str_replace(["\r\n", "\r"], "\n", $msg_data);
        $lines      = explode("\n", $msg_data);
        $in_headers = (!empty($lines[0]) && strpos(substr($lines[0], 0, (int) strpos($lines[0], ':')), ' ') === false);
        $max_line   = 998;

        foreach ($lines as $line) {
            $lines_out = [];
            if ($line === '' && $in_headers) {
                $in_headers = false;
            }
            while (strlen($line) > $max_line) {
                $pos         = strrpos(substr($line, 0, $max_line), ' ');
                $lines_out[] = substr($line, 0, $pos);
                $line        = ($in_headers ? "\t" : '') . substr($line, $pos + 1);
            }
            $lines_out[] = $line;

            foreach ($lines_out as $line_out) {
                if (($line_out[0] ?? '') === '.') {
                    $line_out = '.' . $line_out;
                }
                fwrite($this->smtp_conn, $line_out . $this->CRLF);
            }
        }

        fwrite($this->smtp_conn, $this->CRLF . '.' . $this->CRLF);

        $rply = $this->get_lines();
        $code = substr($rply, 0, 3);

        if ($this->do_debug >= 2) {
            echo 'SMTP -> FROM SERVER:' . $this->CRLF . $rply;
        }

        if ($code !== '250') {
            $this->error = [
                'error'     => 'DATA not accepted from server',
                'smtp_code' => $code,
                'smtp_msg'  => substr($rply, 4),
            ];
            if ($this->do_debug >= 1) {
                echo 'SMTP -> ERROR: ' . $this->error['error'] . ': ' . $rply . $this->CRLF;
            }
            return false;
        }

        return true;
    }

    /**
     * EXPN — expand a mailing list name.
     *
     * SMTP CODE SUCCESS: 250
     * SMTP CODE FAILURE: 550 / SMTP CODE ERROR: 500,501,502,504,421
     */
    public function Expand(string $name): array|false
    {
        if (!$this->initCmd(__FUNCTION__)) {
            return false;
        }

        $rply = $this->sendAndCheck("EXPN $name", '250', 'EXPN not accepted from server');
        if ($rply === false) {
            return false;
        }

        $list = [];
        foreach (explode($this->CRLF, $rply) as $l) {
            $list[] = substr($l, 4);
        }
        return $list;
    }

    /**
     * HELO — introduce ourselves to the server.
     *
     * SMTP CODE SUCCESS: 250 / SMTP CODE ERROR: 500,501,504,421
     */
    public function Hello(string $host = ''): bool
    {
        if (!$this->initCmd(__FUNCTION__)) {
            return false;
        }

        $rply = $this->sendAndCheck('HELO ' . ($host !== '' ? $host : 'localhost'), '250', 'HELO not accepted from server');
        if ($rply === false) {
            return false;
        }

        $this->helo_rply = $rply;
        return true;
    }

    /**
     * HELP — retrieve server help text.
     *
     * SMTP CODE SUCCESS: 211,214 / SMTP CODE ERROR: 500,501,502,504,421
     */
    public function Help(string $keyword = ''): string|false
    {
        if (!$this->initCmd(__FUNCTION__)) {
            return false;
        }

        $extra = ($keyword !== '' ? " $keyword" : '');
        return $this->sendAndCheck("HELP$extra", ['211', '214'], 'HELP not accepted from server');
    }

    /**
     * MAIL FROM — start a mail transaction.
     *
     * SMTP CODE SUCCESS: 250
     */
    public function Mail(string $from): bool
    {
        if (!$this->initCmd(__FUNCTION__)) {
            return false;
        }

        return $this->sendAndCheck("MAIL FROM:$from", '250', 'MAIL not accepted from server') !== false;
    }

    /**
     * NOOP — keep-alive.
     *
     * SMTP CODE SUCCESS: 250 / SMTP CODE ERROR: 500,421
     */
    public function Noop(): bool
    {
        if (!$this->initCmd(__FUNCTION__)) {
            return false;
        }

        return $this->sendAndCheck('NOOP', '250', 'NOOP not accepted from server') !== false;
    }

    /**
     * QUIT — end the session gracefully.
     *
     * SMTP CODE SUCCESS: 221 / SMTP CODE ERROR: 500
     */
    public function Quit(bool $close_on_error = true): bool
    {
        if (!$this->initCmd(__FUNCTION__)) {
            return false;
        }

        fwrite($this->smtp_conn, 'quit' . $this->CRLF);
        $byemsg = $this->get_lines();

        if ($this->do_debug >= 2) {
            echo 'SMTP -> FROM SERVER:' . $this->CRLF . $byemsg;
        }

        $code = substr($byemsg, 0, 3);
        $rval = true;
        $e    = null;

        if ($code !== '221') {
            $e    = ['error' => 'SMTP server rejected quit command', 'smtp_code' => $code, 'smtp_rply' => substr($byemsg, 4)];
            $rval = false;
            if ($this->do_debug >= 1) {
                echo 'SMTP -> ERROR: ' . $e['error'] . ': ' . $byemsg . $this->CRLF;
            }
        }

        if ($e === null || $close_on_error) {
            $this->Close();
        }

        return $rval;
    }

    /**
     * RCPT TO — add a recipient.
     *
     * SMTP CODE SUCCESS: 250,251
     */
    public function Recipient(string $to): bool
    {
        if (!$this->initCmd(__FUNCTION__)) {
            return false;
        }

        return $this->sendAndCheck("RCPT TO:$to", ['250', '251'], 'RCPT not accepted from server') !== false;
    }

    /**
     * RSET — abort current transaction.
     *
     * SMTP CODE SUCCESS: 250
     */
    public function Reset(): bool
    {
        if (!$this->initCmd(__FUNCTION__)) {
            return false;
        }

        return $this->sendAndCheck('RSET', '250', 'RSET failed') !== false;
    }

    /**
     * SEND FROM — deliver to terminal.
     *
     * SMTP CODE SUCCESS: 250
     */
    public function Send(string $from): bool
    {
        if (!$this->initCmd(__FUNCTION__)) {
            return false;
        }

        return $this->sendAndCheck("SEND FROM:$from", '250', 'SEND not accepted from server') !== false;
    }

    /**
     * SAML FROM — send to terminal and mail.
     *
     * SMTP CODE SUCCESS: 250
     */
    public function SendAndMail(string $from): bool
    {
        if (!$this->initCmd(__FUNCTION__)) {
            return false;
        }

        return $this->sendAndCheck("SAML FROM:$from", '250', 'SAML not accepted from server') !== false;
    }

    /**
     * SOML FROM — send to terminal or mail.
     *
     * SMTP CODE SUCCESS: 250
     */
    public function SendOrMail(string $from): bool
    {
        if (!$this->initCmd(__FUNCTION__)) {
            return false;
        }

        return $this->sendAndCheck("SOML FROM:$from", '250', 'SOML not accepted from server') !== false;
    }

    /**
     * TURN — not implemented.
     */
    public function Turn(): false
    {
        $this->error = ['error' => 'This method, TURN, of the SMTP is not implemented'];
        if ($this->do_debug >= 1) {
            echo 'SMTP -> NOTICE: ' . $this->error['error'] . $this->CRLF;
        }
        return false;
    }

    /**
     * VRFY — verify a recipient name.
     *
     * SMTP CODE SUCCESS: 250,251
     */
    public function Verify(string $name): string|false
    {
        if (!$this->initCmd(__FUNCTION__)) {
            return false;
        }

        return $this->sendAndCheck("VRFY $name", ['250', '251'], "VRFY failed on name '$name'");
    }

    // =========================================================================
    //  PRIVATE HELPERS  (addresses original TODO: move duplicate code here)
    // =========================================================================

    /**
     * Reset $this->error and verify the connection is open.
     * Returns true when the caller may proceed, false when it must return false.
     */
    private function initCmd(string $caller): bool
    {
        $this->error = null;

        if (!$this->connected()) {
            $this->error = ['error' => "Called {$caller}() without being connected"];
            return false;
        }

        return true;
    }

    /**
     * Send one SMTP command line, read the reply, and validate the response code.
     *
     * @param  string          $rawCmd   Full command without CRLF.
     * @param  string|string[] $expected Accepted 3-digit code(s).
     * @param  string          $errorMsg Context string on mismatch.
     * @return string|false    Server reply on success, false on mismatch.
     */
    private function sendAndCheck(string $rawCmd, string|array $expected, string $errorMsg): string|false
    {
        fwrite($this->smtp_conn, $rawCmd . $this->CRLF);

        $rply = $this->get_lines();
        $code = substr($rply, 0, 3);

        if ($this->do_debug >= 2) {
            echo 'SMTP -> FROM SERVER:' . $this->CRLF . $rply;
        }

        if (!in_array($code, (array) $expected, true)) {
            $this->error = [
                'error'     => $errorMsg,
                'smtp_code' => $code,
                'smtp_msg'  => substr($rply, 4),
            ];
            if ($this->do_debug >= 1) {
                echo 'SMTP -> ERROR: ' . $this->error['error'] . ': ' . $rply . $this->CRLF;
            }
            return false;
        }

        return $rply;
    }

    // =========================================================================
    //  INTERNAL
    // =========================================================================

    /**
     * Read lines from the socket until a complete multi-line response ends.
     */
    public function get_lines(): string
    {
        $data = '';
        while ($str = fgets($this->smtp_conn, 515)) {
            if ($this->do_debug >= 4) {
                echo 'SMTP -> get_lines(): $data was "' . $data . '"' . $this->CRLF;
                echo 'SMTP -> get_lines(): $str is "'  . $str  . '"' . $this->CRLF;
            }
            $data .= $str;
            if ($this->do_debug >= 4) {
                echo 'SMTP -> get_lines(): $data is "' . $data . '"' . $this->CRLF;
            }
            if (substr($str, 3, 1) === ' ') {
                break;
            }
        }
        return $data;
    }
}
