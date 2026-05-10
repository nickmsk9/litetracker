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

if (!function_exists('api_json_success')) {
	function api_json_success(array $data = []): void
	{
		unset($data['ok']);
		lt_json_response(array_merge([
			'ok' => 1,
		], $data), 200);
	}
}

if (!function_exists('api_json_error')) {
	function api_json_error(string $message, int $status = 400, array $extra = []): void
	{
		unset($extra['ok'], $extra['message']);
		lt_json_response(array_merge([
			'ok' => 0,
			'message' => $message,
		], $extra), $status);
	}
}

if (!function_exists('api_require_login')) {
	function api_require_login(): void
	{
		global $USER;

		if (empty($USER['id'])) {
			api_json_error('Требуется авторизация.', 401);
		}
	}
}

if (!function_exists('api_require_post')) {
	function api_require_post(): void
	{
		if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
			header('Allow: POST');
			api_json_error('Метод запроса не поддерживается.', 405);
		}
	}
}

if (!function_exists('api_csrf_token_from_request')) {
	function api_csrf_token_from_request(): string
	{
		$headers = [
			'HTTP_X_CSRF_TOKEN',
			'HTTP_X_CSRFTOKEN',
			'HTTP_X_LITETRACKER_CSRF',
		];

		foreach ($headers as $header) {
			$value = trim((string) ($_SERVER[$header] ?? ''));
			if ($value !== '') {
				return $value;
			}
		}

		return trim((string) ($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? ''));
	}
}

if (!function_exists('api_require_csrf')) {
	function api_require_csrf(string $scope = 'default'): void
	{
		if (!lt_csrf_validate($scope, api_csrf_token_from_request())) {
			api_json_error('Защитный токен устарел. Обновите страницу и попробуйте снова.', 403);
		}
	}
}

if (!function_exists('api_rate_limit')) {
	function api_rate_limit(string $key, int $limit, int $period): array
	{
		global $USER;

		$identifier = (!empty($USER['id']) ? 'user:'.(int) $USER['id'] : 'ip:'.($_SERVER['REMOTE_ADDR'] ?? 'cli'));
		$result = lt_rate_limit_hit($key, $identifier, $limit, $period);

		if (!empty($result['blocked'])) {
			$retryAfter = max(1, (int) ($result['reset_at'] ?? time()) - time());
			header('Retry-After: '.$retryAfter);
			api_json_error('Слишком много запросов. Повторите попытку позже.', 429, [
				'rate_limit' => [
					'limit' => (int) ($result['limit'] ?? $limit),
					'remaining' => 0,
					'reset_at' => (int) ($result['reset_at'] ?? 0),
				],
			]);
		}

		return $result;
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
