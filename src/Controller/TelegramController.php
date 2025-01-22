<?php

namespace App\Controller;

use App\DTO\Telegram\ChatDTO;
use App\DTO\Telegram\FromDTO;
use App\DTO\Telegram\WebhookDTO;
use App\Service\Telegram\WebhookHandlerServiceFactory;
use danog\MadelineProto\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

class TelegramController extends AbstractController
{
    public function __construct(
        private readonly RouterInterface              $router,
        private readonly LoggerInterface              $logger,
        private readonly WebhookHandlerServiceFactory $handlerServiceFactory,
    )
    {
    }

    #[Route('/webhook-{botType}-bot', name: 'webhook_bot')]
    public function webhook(string $botType): Response
    {
        try {
            $handlerService = $this->handlerServiceFactory->create($botType);

            $path = $this->router->getContext()->getPathInfo();
            $handlerService->getBot()->setWebhookPath($path);

            $bot = $handlerService->getBot();
            $webhook = $bot->getWebhookUpdate();

            if ($callback = $webhook->get('callback_query')) {
                $callbackData = json_decode(json_encode($callback), true);

                throw new Exception('Fix the webhook for callback please.');

                $handlerService->handleCommandCallback($callbackData);

                return new Response('Callback handled successfully', Response::HTTP_OK);
            }

            if ($message = $webhook->getMessage()) {
                $messageData = json_decode(json_encode($message), true);

                $webhookDTO = $this->createWebhookDTO($messageData);

                $handlerService->handleUpdate($webhookDTO);

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

    private function createFromDTO(array $data): FromDTO
    {
        return ((new FromDTO())
            ->setId($data['id'])
            ->setIsBot($data['is_bot'])
            ->setFirstName($data['first_name'])
            ->setUserName($data['username'])
            ->setLanguageCode($data['language_code'])
        );
    }

    private function createChatDTO(array $data): ChatDTO
    {
        return ((new ChatDTO())
            ->setId($data['id'])
            ->setFirstName($data['first_name'])
            ->setUserName($data['username'])
            ->setType($data['type'])
        );
    }

    private function createWebhookDTO(array $data): WebhookDTO
    {
        $fromDTO = $this->createFromDTO($data['from']);
        $chatDTO = $this->createChatDTO($data['chat']);

        return ((new WebhookDTO())
            ->setMessageId($data['message_id'])
            ->setFrom($fromDTO)
            ->setChat($chatDTO)
            ->setDate($data['date'])
            ->setText($data['text'])
        );
    }
}