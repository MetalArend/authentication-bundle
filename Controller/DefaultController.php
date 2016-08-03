<?php

namespace Kuleuven\AuthenticationBundle\Controller;

use Kuleuven\AuthenticationBundle\Service\ShibbolethServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Templating\EngineInterface;

class DefaultController
{
    /**
     * @var ShibbolethServiceProvider
     */
    protected $shibbolethServiceProvider;

    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var bool
     */
    protected $debug;

    /**
     * @param ShibbolethServiceProvider $shibbolethServiceProvider
     * @param EngineInterface           $templating
     * @param boolean                   $debug
     */
    public function __construct(ShibbolethServiceProvider $shibbolethServiceProvider, EngineInterface $templating, $debug)
    {
        $this->shibbolethServiceProvider = $shibbolethServiceProvider;
        $this->templating = $templating;
        $this->debug = $debug;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        if (!$this->debug) {
            throw new UnauthorizedHttpException('Only available in debug mode.');
        }

        $response = new Response();
        $response->setContent($this->templating->render('KuleuvenAuthenticationBundle:Default:index.html.twig', [
            'attributes' => $this->shibbolethServiceProvider->getAttributes(),
        ]));
        return $response;
    }
}
