<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\VolcanoArkApiBundle\DTO\UsageMetricValue;
use Tourze\VolcanoArkApiBundle\DTO\UsageResult;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;
use Tourze\VolcanoArkApiBundle\Entity\ApiKeyUsage;
use Tourze\VolcanoArkApiBundle\Repository\ApiKeyRepository;
use Tourze\VolcanoArkApiBundle\Repository\ApiKeyUsageRepository;
use Tourze\VolcanoArkApiBundle\Service\UsageService;

#[AsCommand(
    name: 'volcano:usage:sync',
    description: 'Sync API usage data from Volcano Ark to local database',
)]
class SyncUsageCommand extends Command
{
    public function __construct(
        private readonly ApiKeyRepository $apiKeyRepository,
        private readonly ApiKeyUsageRepository $apiKeyUsageRepository,
        private readonly UsageService $usageService,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('start-date', null, InputOption::VALUE_OPTIONAL, 'Start date (Y-m-d H:i:s)')
            ->addOption('end-date', null, InputOption::VALUE_OPTIONAL, 'End date (Y-m-d H:i:s)')
            ->addOption('key-name', null, InputOption::VALUE_OPTIONAL, 'Specific API key name to sync')
            ->addOption('hours', null, InputOption::VALUE_OPTIONAL, 'Number of hours to sync (from now)', 24)
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force re-sync even if data exists')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $startDate = $this->getStartDate($input);
        $endDate = $this->getEndDate($input);

        $keyNameOption = $input->getOption('key-name');
        $keyName = is_string($keyNameOption) ? $keyNameOption : null;

        $forceOption = $input->getOption('force');
        $force = (bool) $forceOption;

        $io->title('Syncing Volcano Ark API Usage');
        $io->info(sprintf('Period: %s to %s',
            $startDate->format('Y-m-d H:i:s'),
            $endDate->format('Y-m-d H:i:s')
        ));

        $apiKeys = $this->getApiKeysToSync($keyName);

        if ([] === $apiKeys) {
            $io->warning('No API keys found to sync');

            return Command::SUCCESS;
        }

        $io->section(sprintf('Found %d API key(s) to sync', count($apiKeys)));

        $totalSynced = 0;
        $errors = [];

        foreach ($apiKeys as $apiKey) {
            $io->writeln(sprintf('Syncing usage for key: %s', $apiKey->getName()));

            try {
                $synced = $this->syncApiKeyUsage($apiKey, $startDate, $endDate, $force, $io);
                $totalSynced += $synced;

                $io->success(sprintf('Synced %d hours of data for %s', $synced, $apiKey->getName()));
            } catch (\Exception $e) {
                $errors[] = sprintf('Failed to sync %s: %s', $apiKey->getName(), $e->getMessage());
                $io->error($errors[count($errors) - 1]);
            }
        }

        $io->section('Summary');
        $io->info(sprintf('Total hours synced: %d', $totalSynced));

        if ([] !== $errors) {
            $io->warning('Some errors occurred during sync:');
            $io->listing($errors);

            return Command::FAILURE;
        }

        $io->success('Usage sync completed successfully');

        return Command::SUCCESS;
    }

    private function getStartDate(InputInterface $input): \DateTimeImmutable
    {
        $startDateOption = $input->getOption('start-date');
        if (is_string($startDateOption)) {
            return new \DateTimeImmutable($startDateOption);
        }

        $hoursOption = $input->getOption('hours');
        $hours = is_numeric($hoursOption) ? (int) $hoursOption : 24;

        return new \DateTimeImmutable(sprintf('-%d hours', $hours));
    }

    private function getEndDate(InputInterface $input): \DateTimeImmutable
    {
        $endDateOption = $input->getOption('end-date');
        if (is_string($endDateOption)) {
            return new \DateTimeImmutable($endDateOption);
        }

        return new \DateTimeImmutable();
    }

    /**
     * @return array<ApiKey>
     */
    private function getApiKeysToSync(?string $keyName): array
    {
        if (null !== $keyName) {
            $key = $this->apiKeyRepository->findByName($keyName);

            return (null !== $key) ? [$key] : [];
        }

        return $this->apiKeyRepository->findActiveKeys();
    }

    private function syncApiKeyUsage(ApiKey $apiKey, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate, bool $force, SymfonyStyle $io): int
    {
        $synced = 0;
        $currentHour = $this->roundToHour($startDate);
        $endHour = $this->roundToHour($endDate);

        while ($currentHour <= $endHour) {
            $nextHour = $currentHour->modify('+1 hour');

            if ($this->shouldSkipHour($apiKey, $currentHour, $nextHour, $force, $io)) {
                $currentHour = $nextHour;
                continue;
            }

            if ($this->syncSingleHour($apiKey, $currentHour, $nextHour, $io)) {
                ++$synced;
            }

            $currentHour = $nextHour;
        }

        $this->entityManager->flush();

        return $synced;
    }

    private function shouldSkipHour(ApiKey $apiKey, \DateTimeImmutable $currentHour, \DateTimeImmutable $nextHour, bool $force, SymfonyStyle $io): bool
    {
        if ($force) {
            return false;
        }

        $existing = $this->apiKeyUsageRepository->findByApiKeyAndDateRange($apiKey, $currentHour, $nextHour);
        if ([] === $existing) {
            return false;
        }

        $io->writeln(sprintf('  Skipping %s (already synced)', $currentHour->format('Y-m-d H:00')));

        return true;
    }

    private function syncSingleHour(ApiKey $apiKey, \DateTimeImmutable $currentHour, \DateTimeImmutable $nextHour, SymfonyStyle $io): bool
    {
        $io->writeln(sprintf('  Fetching usage for %s', $currentHour->format('Y-m-d H:00')));

        try {
            $usageResults = $this->usageService->getUsageForApiKey(
                $apiKey,
                $currentHour->getTimestamp(),
                $nextHour->getTimestamp() - 1,
                3600
            );

            $this->processUsageResults($apiKey, $usageResults);

            return true;
        } catch (\Exception $e) {
            $io->warning(sprintf('  Failed to fetch usage for %s: %s',
                $currentHour->format('Y-m-d H:00'),
                $e->getMessage()
            ));

            return false;
        }
    }

    /**
     * @param UsageResult[] $usageResults
     */
    private function processUsageResults(ApiKey $apiKey, array $usageResults): void
    {
        foreach ($usageResults as $result) {
            foreach ($result->metricItems as $metricItem) {
                $endpointId = $this->extractEndpointId($metricItem->tags);
                $this->processMetricValues($apiKey, $result, $metricItem->values, $endpointId);
            }
        }
    }

    /**
     * @param array<int, array<string, string>> $tags
     */
    private function extractEndpointId(array $tags): ?string
    {
        foreach ($tags as $tag) {
            if ('EndpointId' === $tag['Key']) {
                return $tag['Value'];
            }
        }

        return null;
    }

    /**
     * @param UsageMetricValue[] $values
     */
    private function processMetricValues(ApiKey $apiKey, UsageResult $result, array $values, ?string $endpointId): void
    {
        foreach ($values as $value) {
            $usageHour = $this->roundToHour((new \DateTimeImmutable())->setTimestamp($value->timestamp));

            $usage = $this->apiKeyUsageRepository->findOrCreateByKeyAndHour($apiKey, $usageHour, $endpointId);

            $this->updateUsageMetrics($usage, $result->name, (int) $value->value);
            $usage->incrementRequestCount();
            $this->entityManager->persist($usage);
        }
    }

    private function updateUsageMetrics(ApiKeyUsage $usage, string $metricName, int $value): void
    {
        if ('PromptTokens' === $metricName) {
            $usage->setPromptTokens($value);
        } elseif ('CompletionTokens' === $metricName) {
            $usage->setCompletionTokens($value);
        }
    }

    private function roundToHour(\DateTimeImmutable $date): \DateTimeImmutable
    {
        return $date->setTime((int) $date->format('H'), 0, 0, 0);
    }
}
