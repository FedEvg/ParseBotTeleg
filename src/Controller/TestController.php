<?php

namespace App\Controller;

use App\Service\ParseNewsService;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestController extends AbstractController
{

    public function __construct(
        private readonly ParseNewsService $newsService,
    )
    {
    }

    /**
     * @throws \Exception
     */
    #[NoReturn] #[Route('/test', name: 'test')]
    public function test(Request $request): Response
    {
        $news = $this->newsService->getLastNews('lossolomas_kyiv', 1);

        dd($news);
    }

}