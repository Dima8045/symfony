<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validation;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

#[AsCommand(
    name: 'import-product',
    description: 'Import Products from CSV',
)]
class ImportProductCommand extends Command
{
    private $question;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setProcessTitle('Import Products');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');

        $this->question = new Question('Enter Max Product Quantity: ');
        $this->validate();
        $this->question->setMaxAttempts(5);
        $quantity = $helper->ask($input, $output, $this->question);

        $this->question = new Question('Enter Max Product Price: ');
        $this->validate();
        $this->question->setMaxAttempts(5);
        $price = $helper->ask($input, $output, $this->question);

        $file = $this->projectDir . '/public/files/stock.csv';

        $decoder = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);

        $rows = $decoder->decode(file_get_contents($file), 'csv');
        dd([$quantity, $price, $rows]);
    }

    /**
     * Validate input product parameters
     * @throws \Exception
     */
    protected function validate()
    {
        $this->question->setValidator(function ($answer) {

            if (trim($answer) == '') {
                throw new \Exception('The value cannot be empty');
            } else {
                if (!is_numeric($answer)) {
                    throw new \Exception('The value must be numeric');
                }
            }

            return $answer;
        });
    }
}
