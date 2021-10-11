<?php

namespace App\Command;

use App\Entity\TblProductData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

#[AsCommand(
    name: 'import-product',
    description: 'Import Products from CSV',
)]
class ImportProductCommand extends Command
{
    const MAX_ATTEMPTS = 5;
    private $question;

    public function __construct(string $projectDir, EntityManagerInterface $entityManager)
    {
        $this->projectDir = $projectDir;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setProcessTitle('Import Products')
            ->addArgument('test', InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        if ($input->getArgument('test')) {
            $maxStock = 10;
            $minPrice = 5;
            $maxPrice = 1000;
        } else {
            $helper = $this->getHelper('question');

            $this->questionHandler('Enter Max Product Quantity: ');
            $maxStock = $helper->ask($input, $output, $this->question);

            $this->questionHandler('Enter Min Product Price: ');
            $minPrice = $helper->ask($input, $output, $this->question);

            $this->questionHandler('Enter Max Product Price: ');
            $maxPrice = $helper->ask($input, $output, $this->question);
        }

        $rows = $this->csvRowsProvider();

        $products = $this->productsMapper($rows, $maxStock, $minPrice, $maxPrice, $input->getArgument('test'));

        $output->writeln('<fg=white> Available products for processing: ' . (count($products['passed']) + count($products['failed'])) . '</>');
        $output->writeln('<info> Successfully imported products: ' . count($products['passed']) . '</info>');
        $output->writeln('<fg=red> No products were imported because criteria were not met: ' . count($products['failed']) . '</>');
        dd([$maxStock, $maxPrice, $minPrice, $products]);
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

    /**
     * Split products by criteria
     * @param array $inputRows
     * @param $maxStock
     * @param $minPrice
     * @param $maxPrice
     * @param false $test
     * @return array
     * @throws \Exception
     */
    protected function productsMapper(array $inputRows, $maxStock, $minPrice, $maxPrice, $test = false): array
    {
        $products = [];
        $mappedRows = $this->mapRows($inputRows);

        if (!count($mappedRows['valid'])) {
            throw new \Exception('You have no valid data to import');
        }

        foreach ($mappedRows['valid'] as $row) {
            if ($row['Stock'] < $maxStock && $row['Cost in GBP'] < $minPrice) {
                $products['failed'][] = $row;
            } elseif ($row['Cost in GBP'] > $maxPrice) {
                $products['failed'][] = $row;
            } elseif (!empty($row['Discontinued']) && $row['Discontinued'] === 'yes') {
                $row = array_merge($row, compact('maxStock', 'minPrice', 'maxPrice'));

                if (!$test) {
                    $row = $this->storeProduct($row, true);
                }

                $products['passed'][] = $row;
            } else {
                $row = array_merge($row, compact('maxStock', 'minPrice', 'maxPrice'));
                $products['passed'][] = !$test ? $this->storeProduct($row) : $row;
            }
        }

        return $products;
    }

    /**
     * Store/Update products
     * @param array $product
     * @param bool $discontinued
     * @return bool
     */
    private function storeProduct( array $product, bool $discontinued = false): bool
    {
        $timestamp = new \DateTimeImmutable();

        $tblProductData = $this->entityManager
            ->getRepository(TblProductData::class)
            ->findOneBy(['strProductCode' => $product['Product Code']]);

        if (!$tblProductData) {
            $tblProductData = new TblProductData();
        }

        $tblProductData->setStrProductName($product['Product Name']);
        $tblProductData->setStrProductDesc($product['Product Description']);
        $tblProductData->setStrProductCode($product['Product Code']);
        $tblProductData->setDtmAdded($timestamp);
        $tblProductData->setStmTimestamp($timestamp);
        $tblProductData->setDtmDiscontinued($discontinued ? $timestamp : null);
        $tblProductData->setMaxStock($product['maxStock']);
        $tblProductData->setMinPrice($product['minPrice']);
        $tblProductData->setMaxPrice($product['maxPrice']);

        $this->entityManager->persist($tblProductData);
        $this->entityManager->flush();

        return (bool)$tblProductData->getIntProductDataId();
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
     * Check valid cost
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
