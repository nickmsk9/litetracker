<?php
/*
===================================================================
LiteTracker Source
===================================================================
HTTP/request helpers
===================================================================
*/

if (!function_exists('lt_is_ajax_request')) {
	function lt_is_ajax_request(): bool
	{
		$requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
		if ($requestedWith === 'xmlhttprequest') {
			return true;
		}

		$accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
		return (strpos($accept, 'application/json') !== false);
	}
}

if (!function_exists('lt_json_response')) {
	function lt_json_response(array $payload, int $status = 200): void
	{
		http_response_code($status);
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode($payload, JSON_UNESCAPED_UNICODE);
		die();
	}
}

if (!function_exists('lt_json_ok')) {
	function lt_json_ok(string $message = '', array $data = [], int $status = 200): void
	{
		lt_json_response(array_merge([
			'ok' => 1,
			'message' => $message,
		], $data), $status);
	}
}

if (!function_exists('lt_json_error')) {
	function lt_json_error(string $message, array $data = [], int $status = 400): void
	{
		lt_json_response(array_merge([
			'ok' => 0,
			'message' => $message,
		], $data), $status);
	}
}

if (!function_exists('lt_redirect')) {
	function lt_redirect(string $url): void
	{
		header('Location: '.$url);
		die();
	}
}

if (!function_exists('lt_redirect_back')) {
	function lt_redirect_back(string $fallback = 'index.php'): void
	{
		$referer = trim((string) ($_SERVER['HTTP_REFERER'] ?? ''));
		lt_redirect($referer !== '' ? $referer : $fallback);
	}
}

if (!function_exists('lt_post_string')) {
	function lt_post_string(string $key, string $default = ''): string
	{
		$value = $_POST[$key] ?? $default;
		return is_scalar($value) ? (string) $value : $default;
	}
}

if (!function_exists('lt_get_string')) {
	function lt_get_string(string $key, string $default = ''): string
	{
		$value = $_GET[$key] ?? $default;
		return is_scalar($value) ? (string) $value : $default;
	}
}

if (!function_exists('lt_post_int')) {
	function lt_post_int(string $key, int $default = 0): int
	{
		$value = $_POST[$key] ?? $default;
		return is_scalar($value) ? (int) $value : $default;
	}
}

if (!function_exists('lt_get_int')) {
	function lt_get_int(string $key, int $default = 0): int
	{
		$value = $_GET[$key] ?? $default;
		return is_scalar($value) ? (int) $value : $default;
	}
}
