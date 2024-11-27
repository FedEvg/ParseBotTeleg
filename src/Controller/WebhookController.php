<?php

namespace App\Controller;

use App\Command\Telegram\CommandHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class WebhookController extends AbstractController
{
    public function __construct(
        private readonly CommandHandler $commandHandler,
        private readonly Api            $bot,
    )
    {

        $this->setWebhook();
    }


    private function setWebhook(): void
    {
        $webhookUrl = $_ENV['URL_PROXY_SERVER_FOR_WEBHOOK'] . '/webhook';

        try {
            $this->bot->setWebhook([
                'url' => $webhookUrl,
                'allowed_updates' => ['message', 'callback_query'],
            ]);
        } catch (TelegramSDKException $e) {
            echo 'Помилка установки вебхука: ' . $e->getMessage() . PHP_EOL;
            exit;
        }
    }

    #[Route('/webhook', name: 'webhook')]
    public function handleWebhook(Request $request): Response
    {
        try {
            $webhook = $this->bot->getWebhookUpdate();

            if ($callback = $webhook->get('callback_query')) {
                $this->commandHandler->handleCommandCallback($callback);

                return new Response('Callback handled successfully', Response::HTTP_OK);
            }

            if ($message = $webhook->getMessage()) {
                $this->commandHandler->handleCommandMessage($message);

                return new Response('Message handled successfully', Response::HTTP_OK);
            }

            return new Response('No callback or message found', Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            // Логування помилки (рекомендовано)
            // $this->logger->error('Webhook error: ' . $e->getMessage());

            // Повернення 500 Internal Server Error з повідомленням про помилку
            return new Response('Server error: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}