<?php

namespace App\Controller;

use App\Tasks\RedisPostWriter;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestController extends AbstractController
{

    public function __construct(
        private readonly RedisPostWriter $redisPostWriter
    )
    {
    }

    /**
     * @throws Exception
     */
    #[NoReturn]
    #[Route('/test', name: 'test')]
    public function test(Request $request): Response
    {
        $this->redisPostWriter->savePostToRedis();

        return new Response('ALL IS GOOD');
    }


}