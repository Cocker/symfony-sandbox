<?php

declare(strict_types=1);

namespace App\Api\User\Command;

use App\Api\User\Repository\V1\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsCommand('app:users:prune', 'Prune users')]
#[AsPeriodicTask('1 week')]
class PruneUsersCommand extends Command
{
    public function __construct(private readonly UserRepository $userRepository) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('dry-run')) {
            $io->note('Dry run enabled');

            $count = $this->userRepository->countPrunableUsers();
        } else {
            $count = $this->userRepository->pruneUsers();
        }

        $io->success("Deleted $count users");

        return Command::SUCCESS;
    }
}
