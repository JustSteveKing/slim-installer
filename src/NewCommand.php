<?php declare(strict_types=1);

namespace JustSteveKing\Slim\Installer\Console;

use RuntimeException;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class NewCommand extends Command
{
    private array $projects = [
        [
            "name" => "Official Slim Skeleton",
            "project" => "slim/slim-skeleton",
        ],
        [
            "name" => "Odan Slim Skeleton",
            "project" => "odan/slim4-skeleton"
        ]
    ];

    protected function configure(): void
    {
        $this->setName('new')
            ->setDescription('Create a new Slim PHP application')
            ->addArgument('name', InputArgument::OPTIONAL)
            ->addOption('project', null, InputOption::VALUE_OPTIONAL, 'Which boilerplate would you like to use?')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Forces install even if the directory already exists');;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        $directory = $name && $name !== '.' ? getcwd().'/'.$name : '.';

        if (! $input->getOption('force')) {
            $this->verifyApplicationDoesntExist($directory);
        }

        if ($input->getOption('force') && $directory === '.') {
            throw new RuntimeException('Cannot use --force option when using current directory for installation!');
        }

        $composer = $this->findComposer();

        $project = $input->getOption('project') ?? $this->askForProjectSelection($input, $output);

        $commands = [
            "{$composer} create-project {$project} {$directory} --remove-vcs --prefer-dist",
        ];

        if ($directory != '.' && $input->getOption('force')) {
            if (PHP_OS_FAMILY == 'Windows') {
                array_unshift($commands, "rd /s /q \"$directory\"");
            } else {
                array_unshift($commands, "rm -rf \"$directory\"");
            }
        }

        if (($process = $this->runCommands($commands, $input, $output))->isSuccessful()) {
            $output->writeln(PHP_EOL.'<comment>Your Slim application is ready! Go build something amazing.</comment>');
        }

        return $process->getExitCode();
    }

    protected function askForProjectSelection(InputInterface $input, OutputInterface $output): string
    {
        $helper = $this->getHelper('question');

        $question = new ChoiceQuestion(
            'Please select which boilerplate you which to use:',
            $this->projects(),
            0
        );

        $question->setErrorMessage('Project %s is invalid.');
    
        $project = $helper->ask($input, $output, $question);
        $output->writeln('You have just selected: '.$project);

        return $project;
    }

    /**
     * Verify that the application does not already exist.
     *
     * @param  string  $directory
     * @return void
     */
    protected function verifyApplicationDoesntExist($directory)
    {
        if ((is_dir($directory) || is_file($directory)) && $directory != getcwd()) {
            throw new RuntimeException('Application already exists!');
        }
    }


    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer()
    {
        $composerPath = getcwd().'/composer.phar';

        if (file_exists($composerPath)) {
            return '"'.PHP_BINARY.'" '.$composerPath;
        }

        return 'composer';
    }

    private function projects(): array
    {
        return array_map(function ($project) {
            return $project['project'];
        }, $this->projects);
    }

    /**
     * Run the given commands.
     *
     * @param  array  $commands
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return Process
     */
    protected function runCommands($commands, InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('no-ansi')) {
            $commands = array_map(function ($value) {
                if (substr($value, 0, 5) === 'chmod') {
                    return $value;
                }

                return $value.' --no-ansi';
            }, $commands);
        }

        if ($input->getOption('quiet')) {
            $commands = array_map(function ($value) {
                if (substr($value, 0, 5) === 'chmod') {
                    return $value;
                }

                return $value.' --quiet';
            }, $commands);
        }

        $process = Process::fromShellCommandline(implode(' && ', $commands), null, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $output->writeln('Warning: '.$e->getMessage());
            }
        }

        $process->run(function ($type, $line) use ($output) {
            $output->write('    '.$line);
        });

        return $process;
    }
}
