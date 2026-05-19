<?php

namespace LiteTracker\Http;

class Response
{
	protected string $content;
	protected int $status;
	protected array $headers;

	public function __construct(string $content = '', int $status = 200, array $headers = array())
	{
		$this->content = $content;
		$this->status = $status;
		$this->headers = $headers;
	}

	public function send(): void
	{
		if (!headers_sent()) {
			http_response_code($this->status);
			foreach ($this->headers as $name => $value) {
				header($name.': '.$value);
			}
		}

		echo $this->content;
	}
}
