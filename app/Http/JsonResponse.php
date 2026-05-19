<?php

namespace LiteTracker\Http;

final class JsonResponse extends Response
{
	public function __construct(array $data, int $status = 200)
	{
		parent::__construct(
			json_encode($data, JSON_UNESCAPED_UNICODE),
			$status,
			array('Content-Type' => 'application/json; charset=UTF-8')
		);
	}
}
