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
    const MAX_ATTEMPTS = 5;
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

        $this->questionHandler('Enter Max Product Quantity: ');
        $maxStock = $helper->ask($input, $output, $this->question);

        $this->questionHandler('Enter Min Product Price: ');
        $minPrice = $helper->ask($input, $output, $this->question);

        $this->questionHandler('Enter Max Product Price: ');
        $maxPrice = $helper->ask($input, $output, $this->question);

        $rows = $this->csvRowsProvider();

        $rowsMapped = $this->productsMapper($rows, $maxStock, $minPrice, $maxPrice);

        dd([$maxStock, $maxPrice, $minPrice, $rowsMapped]);
    }

    /**
     * Handle question
     * @param string $question
     * @throws \Exception
     */
    private function questionHandler(string $question)
    {
        $this->question = new Question($question);
        $this->validate();
        $this->question->setMaxAttempts(self::MAX_ATTEMPTS);
    }

    /**
     * Get string from CSV file and supply it in array
     * @return mixed
     */
    protected function csvRowsProvider(): array
    {
        $file = $this->projectDir . '/public/files/stock.csv';

        $decoder = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);

        return $decoder->decode(file_get_contents($file), 'csv');
    }

    protected function productsMapper(array $inputRows, $maxStock, $minPrice, $maxPrice)
    {
        $rows = [];
        $mappedRows = $this->mapRows($inputRows);

        if (!count($mappedRows['valid'])) {
            throw new \Exception('You have no valid data to import');
        }

        foreach ($mappedRows['valid'] as $row) {
            if ($row['Stock'] < $maxStock && $row['Cost in GBP'] < $minPrice) {
                $rows['failed'][] = $row;
            } elseif ($row['Cost in GBP'] > $maxPrice) {
                $rows['failed'][] = $row;
            } elseif (!empty($row['Discontinued']) && $row['Discontinued'] === 'yes') {
                $row['dtmDiscontinued'] = date('Y-m-d H:m:i');
                $row = array_merge($row, compact('maxStock', 'minPrice', 'maxPrice'));
                $rows['passed'][] = $row;
            } else {
                $rows['passed'][] = $row;
            }
        }

        return $rows;
    }


    /**
     * Map rows by valid content
     * @param $inputRows
     * @return array
     */
    protected function mapRows($inputRows): array {
        $rows = [];

        foreach ($inputRows as $row) {
            $passed = 0;
            $passed += !empty($row['Stock']) && is_numeric($row['Stock']);
            $passed += !empty($row['Cost in GBP']) && $this->checkPrice($row['Cost in GBP']);
            if ($passed === 2) {
                $rows['valid'][] = $row;
            } else {
                $rows['invalid'][] = $row;
            }
        }

        return $rows;
    }

    /**
     * Check vslid cost
     */
    private function checkPrice($price)
    {
        preg_match('/^((\d+)|(\d{1,3})(\,\d{3})*)(\.\d{2})$/', $price, $matches);
        return !!count($matches);
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
