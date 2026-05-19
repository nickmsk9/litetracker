<?php

namespace LiteTracker\Http\Controllers;

use LiteTracker\Http\JsonResponse;
use LiteTracker\Http\Request;
use LiteTracker\Http\Response;

final class BrowseController
{
	public function __invoke(Request $request): Response
	{
		$model = \browse_build_page_model($request->query(), $request->server());
		if (isset($model['json'])) {
			return new JsonResponse($model['json']);
		}

		return new Response($this->render($model['vars']));
	}

	private function render(array $vars): string
	{
		extract($vars, EXTR_SKIP);
		if ($activeTag !== '') {
			$_GET['tag'] = $activeTag;
		} else {
			unset($_GET['tag']);
		}

		ob_start();
		\head('Торренты');
		require LT_APP_PATH.'/Http/Views/browse.php';
		\foot();

		return (string) ob_get_clean();
	}
}
