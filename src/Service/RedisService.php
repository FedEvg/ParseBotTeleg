<?php

namespace App\Service;

use Exception;
use Predis\Client;
use Psr\Log\LoggerInterface;

class RedisService
{
    private Client $client;


    public function __construct(
        private readonly LoggerInterface $logger
    )
    {
        $this->client = new Client([
            'scheme' => 'tcp',
            'host' => 'redis',
            'port' => 6379,
            'timeout' => 5.0,
        ]);
    }

    /**
     * @throws Exception
     */
    public function setHash(string $key, array $data, int $ttl = null): void
    {
        try {
            foreach ($data as $field => $value) {
                $this->client->hset($key, $field, $value);
            }

            if ($ttl !== null) {
                $this->client->expire($key, $ttl);
            }

            $this->client->publish('hash_written', json_encode(['key' => $key, 'data' => $data]));
        } catch (\Exception $e) {
            throw new Exception("Ошибка при записи хэша в Redis: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function getHash(string $key): array
    {
        try {
            return $this->client->hgetall($key);
        } catch (\Exception $e) {
            throw new Exception("Ошибка при чтении хэша из Redis: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function updateHashField(string $key, string $field, $value): void
    {
        try {
            $this->client->hset($key, $field, $value);
        } catch (\Exception $e) {
            throw new Exception("Ошибка при обновлении поля в Redis: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function exists(string $key): int
    {
        try {
            return $this->client->exists($key);
        } catch (\Exception $e) {
            throw new Exception("Ошибка при проверке ключа в Redis: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function subscribeToChannels(array $channels, callable $callback): void
    {
        $maxRetries = 5;
        $retryDelay = 1;

        while (true) {
            try {
                $subscriber = $this->client->pubSubLoop();

                foreach ($channels as $channel) {
                    $subscriber->subscribe($channel);
                }

                foreach ($subscriber as $message) {
                    if ($message->kind === 'message') {
                        $callback($message);
                    }
                }
                break;

            } catch (\Exception $e) {
                $this->logger->error('Ошибка подключения к Redis: ' . $e->getMessage(), [
                    'exception' => $e,
                    'remaining_retries' => $maxRetries,
                    'retry_delay' => $retryDelay,
                ]);

                if ($maxRetries <= 0) {
                    $this->logger->critical('Не удалось подключиться к Redis после нескольких попыток.', [
                        'exception' => $e,
                        'max_retries' => $maxRetries,
                    ]);
                    throw new Exception('Не удалось подключиться к Redis после нескольких попыток. Error: ' . $e->getMessage());
                }

                $this->logger->warning("Ошибка подключения, повторная попытка через {$retryDelay} секунд...", [
                    'remaining_retries' => $maxRetries,
                    'retry_delay' => $retryDelay,
                ]);

                sleep($retryDelay);
                $maxRetries--;
            }
        }

    }
}
