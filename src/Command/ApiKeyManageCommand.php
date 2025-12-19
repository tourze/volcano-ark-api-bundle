<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\VolcanoArkApiBundle\Service\ApiKeyService;

#[AsCommand(
    name: 'volcano:api-key:manage',
    description: 'Manage Volcano Ark API keys',
)]
final class ApiKeyManageCommand extends Command
{
    public function __construct(
        private readonly ApiKeyService $apiKeyService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::REQUIRED, 'Action to perform: list, create, activate, deactivate, delete')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'API key name')
            ->addOption('api-key', null, InputOption::VALUE_OPTIONAL, 'API key value')
            ->addOption('secret-key', null, InputOption::VALUE_OPTIONAL, 'Secret key value')
            ->addOption('region', null, InputOption::VALUE_OPTIONAL, 'Region (default: cn-beijing)', 'cn-beijing')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = $input->getArgument('action');

        if (!is_string($action)) {
            $io->error('Action must be a string');

            return Command::FAILURE;
        }

        switch ($action) {
            case 'list':
                return $this->listKeys($io);
            case 'create':
                return $this->createKey($input, $io);
            case 'activate':
                return $this->activateKey($input, $io);
            case 'deactivate':
                return $this->deactivateKey($input, $io);
            case 'delete':
                return $this->deleteKey($input, $io);
            default:
                $io->error(sprintf('Unknown action: %s', $action));

                return Command::FAILURE;
        }
    }

    private function listKeys(SymfonyStyle $io): int
    {
        $keys = $this->apiKeyService->getAllKeys();

        if ([] === $keys) {
            $io->warning('No API keys found');

            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($keys as $key) {
            $rows[] = [
                $key->getId(),
                $key->getName(),
                $key->getRegion(),
                $key->isActive() ? 'Active' : 'Inactive',
                $key->getUsageCount(),
                (null !== $key->getLastUsedTime()) ? $key->getLastUsedTime()->format('Y-m-d H:i:s') : 'Never',
            ];
        }

        $io->table(
            ['ID', 'Name', 'Region', 'Status', 'Usage Count', 'Last Used'],
            $rows
        );

        return Command::SUCCESS;
    }

    private function createKey(InputInterface $input, SymfonyStyle $io): int
    {
        $name = $input->getOption('name');
        $apiKey = $input->getOption('api-key');
        $secretKey = $input->getOption('secret-key');
        $region = $input->getOption('region');

        if (null === $name || null === $apiKey || null === $secretKey) {
            $io->error('Name, api-key, and secret-key are required for creating a key');

            return Command::FAILURE;
        }

        if (!is_string($name) || !is_string($apiKey) || !is_string($secretKey)) {
            $io->error('Name, api-key, and secret-key must be strings');

            return Command::FAILURE;
        }

        $regionValue = is_string($region) ? $region : 'cn-beijing';

        $key = $this->apiKeyService->createKey($name, $apiKey, $secretKey, $regionValue);

        $io->success(sprintf('API key "%s" created successfully with ID %d', $key->getName(), $key->getId()));

        return Command::SUCCESS;
    }

    private function activateKey(InputInterface $input, SymfonyStyle $io): int
    {
        $name = $input->getOption('name');

        if (null === $name || !is_string($name)) {
            $io->error('Name is required for activating a key and must be a string');

            return Command::FAILURE;
        }

        $key = $this->apiKeyService->findKeyByName($name);

        if (null === $key) {
            $io->error(sprintf('API key with name "%s" not found', $name));

            return Command::FAILURE;
        }

        $this->apiKeyService->activateKey($key);

        $io->success(sprintf('API key "%s" activated', $key->getName()));

        return Command::SUCCESS;
    }

    private function deactivateKey(InputInterface $input, SymfonyStyle $io): int
    {
        $name = $input->getOption('name');

        if (null === $name || !is_string($name)) {
            $io->error('Name is required for deactivating a key and must be a string');

            return Command::FAILURE;
        }

        $key = $this->apiKeyService->findKeyByName($name);

        if (null === $key) {
            $io->error(sprintf('API key with name "%s" not found', $name));

            return Command::FAILURE;
        }

        $this->apiKeyService->deactivateKey($key);

        $io->success(sprintf('API key "%s" deactivated', $key->getName()));

        return Command::SUCCESS;
    }

    private function deleteKey(InputInterface $input, SymfonyStyle $io): int
    {
        $name = $input->getOption('name');

        if (null === $name || !is_string($name)) {
            $io->error('Name is required for deleting a key and must be a string');

            return Command::FAILURE;
        }

        $key = $this->apiKeyService->findKeyByName($name);

        if (null === $key) {
            $io->error(sprintf('API key with name "%s" not found', $name));

            return Command::FAILURE;
        }

        if ($io->confirm(sprintf('Are you sure you want to delete API key "%s"?', $key->getName()), false)) {
            $this->apiKeyService->deleteKey($key);
            $io->success(sprintf('API key "%s" deleted', $key->getName()));
        } else {
            $io->info('Deletion cancelled');
        }

        return Command::SUCCESS;
    }
}
