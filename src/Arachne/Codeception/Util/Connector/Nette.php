<?php

namespace Arachne\Codeception\Util\Connector;

use Arachne\Codeception\IContainerFactory;
use Nette\DI\Container;
use Nette\Http\IResponse;
use Nette\Http\Session;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\Response;

/**
 * @author Jáchym Toušek
 */
class Nette extends Client
{

	/** @var Container */
	protected $container;

	/**
	 * @var IContainerFactory
	 */
	protected $containerFactory;


	/**
	 * @param \Arachne\Codeception\IContainerFactory $containerFactory
	 */
	public function setContainerFactory(IContainerFactory $containerFactory)
	{
		$this->containerFactory = $containerFactory;
	}


	/**
	 * @param Request $request
	 * @return Response
	 */
	public function doRequest($request)
	{
		$_COOKIE = $request->getCookies();
		$_SERVER = $request->getServer();
		$_FILES = $request->getFiles();

		$uri = str_replace('http://localhost', '', $request->getUri());

		$_SERVER['REQUEST_METHOD'] = strtoupper($request->getMethod());
		$_SERVER['REQUEST_URI'] = $uri;
		$_POST = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $request->getParameters() : [];

		// Container initialization can't be called earlier because Nette\Http\IRequest service might be initialized too soon and amOnPage method would not work anymore.
		$this->container = $this->containerFactory->createContainer();
		$this->container->initialize();

		// The HTTP code from previous test sometimes survives in http_response_code() so it's necessary to reset it manually.
		$httpResponse = $this->container->getByType('Nette\Http\IResponse');
		$httpResponse->setCode(IResponse::S200_OK);

		try {
			ob_start();
			$this->container->getByType('Nette\Application\Application')->run();
			$content = ob_get_clean();

		} catch (\Exception $e) {
			ob_end_clean();
			throw $e;
		}


//TODO
//		Session::$started = FALSE;

		$code = $httpResponse->getCode();
		$headers = $httpResponse->getHeaders();

		return new Response($content, $code, $headers);
	}

}
