<?php

namespace LiteTracker\Http;

final class Request
{
	private array $query;
	private array $post;
	private array $server;

	public function __construct(array $query = array(), array $post = array(), array $server = array())
	{
		$this->query = $query;
		$this->post = $post;
		$this->server = $server;
	}

	public static function capture(): self
	{
		return new self($_GET, $_POST, $_SERVER);
	}

	public function query(): array
	{
		return $this->query;
	}

	public function post(): array
	{
		return $this->post;
	}

	public function request(): array
	{
		return array_replace($this->query, $this->post);
	}

	public function server(): array
	{
		return $this->server;
	}

	public function method(): string
	{
		return strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
	}

	public function input(string $key, mixed $default = null): mixed
	{
		$request = $this->request();

		return array_key_exists($key, $request) ? $request[$key] : $default;
	}

	public function string(string $key, string $default = ''): string
	{
		$value = $this->input($key, $default);

		return is_array($value) ? $default : trim((string) $value);
	}

	public function int(string $key, int $default = 0): int
	{
		$value = $this->input($key, $default);

		return is_array($value) ? $default : (int) $value;
	}
}
