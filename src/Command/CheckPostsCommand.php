<?php

namespace App\Command;

use App\Tasks\RedisPostWriter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckPostsCommand extends Command
{
    public function __construct(
        private readonly RedisPostWriter $redisPostWriter,
        private readonly LoggerInterface $logger
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:check-posts')->setDescription('Парсин постов.');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info('Starting command: app:check-posts');
        $output->writeln('Starting command: app:check-posts');

        try {
            $this->redisPostWriter->savePostToRedis();
            $this->logger->info('Finished processing posts and saving to Redis.');
        } catch (\Exception $e) {
            $this->logger->error('Error occurred while processing posts: ' . $e->getMessage());
            $output->writeln('Error occurred during post processing.');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
