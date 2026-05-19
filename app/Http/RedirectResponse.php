<?php

namespace LiteTracker\Http;

final class RedirectResponse extends Response
{
	public function __construct(string $location, int $status = 302)
	{
		parent::__construct('', $status, array('Location' => $location));
	}
}
