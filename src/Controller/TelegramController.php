<?php

namespace App\Controller;

use App\Service\Telegram\WebhookHandlerServiceFactory;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

class TelegramController extends AbstractController
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly LoggerInterface $logger,
        private readonly WebhookHandlerServiceFactory $handlerServiceFactory,
    ) {}

    #[Route('/webhook-{botType}-bot', name: 'webhook_bot')]
    public function webhook(string $botType): Response
    {
        xdebug_break();

        try {
            $handlerService = $this->handlerServiceFactory->create($botType);

            $path = $this->router->getContext()->getPathInfo();
            $handlerService->getBot()->setWebhookPath($path);

            $bot = $handlerService->getBot();
            $webhook = $bot->getWebhookUpdate();

            if ($callback = $webhook->get('callback_query')) {
                $update = json_decode(json_encode($callback), true);
                $handlerService->handleCommandCallback($update);

                return new Response('Callback handled successfully', Response::HTTP_OK);
            }

            if ($message = $webhook->getMessage()) {
                $update = json_decode(json_encode($message), true);
                $handlerService->handleCommandMessage($update);

                return new Response('Message handled successfully', Response::HTTP_OK);
            }

            return new Response('No callback or message found', Response::HTTP_BAD_REQUEST);
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Invalid bot type provided', ['botType' => $botType, 'exception' => $e]);
            return new Response('Invalid bot type: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            $this->logError($e);
            return new Response('Server error: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function logError(\Throwable $e): void
    {
        $this->logger->error('Unhandled server error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}