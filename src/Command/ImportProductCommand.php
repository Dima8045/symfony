<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ProductData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
    private Question $question;
    private mixed $inputFile;
    private int $maxStock;
    private string $minPrice;
    private string $maxPrice;
    private string $projectDir;
    private EntityManagerInterface $entityManager;

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
            ->setDescription('Default imported file /public/files/stock.csv. [--file] makes it possible to define the file manually')
            ->addArgument('test', InputArgument::OPTIONAL)
            ->addOption('file', null, InputOption::VALUE_OPTIONAL)
            ->addOption('max-stock', null, InputOption::VALUE_OPTIONAL)
            ->addOption('min-price', null, InputOption::VALUE_OPTIONAL)
            ->addOption('max-price', null, InputOption::VALUE_OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getArgument('test')) {
            $this->inputFile = $input->getOption('file');
            $this->maxStock = (int) $input->getOption('max-stock');
            $this->minPrice = $input->getOption('min-price');
            $this->maxPrice = $input->getOption('max-price');
        } else {
            $this->inputFile = $input->getOption('file') ?? '/public/files/stock.csv';

            $helper = $this->getHelper('question');

            $this->questionHandler('Enter Max Product Quantity: ');
            $this->maxStock = (int) $helper->ask($input, $output, $this->question);

            $this->questionHandler('Enter Min Product Price: ');
            $this->minPrice = $helper->ask($input, $output, $this->question);

            $this->questionHandler('Enter Max Product Price: ');
            $this->maxPrice = $helper->ask($input, $output, $this->question);
        }


        $rows = $this->csvRowsProvider();

        $products = $this->productsMapper($rows, (bool)$input->getArgument('test'));

        $output->writeln('<fg=white> Available products for processing: ' . (count($products['passed']) + count($products['failed'])) . '</>');
        $output->writeln('<info> Successfully imported products: ' . count($products['passed']) . '</info>');
        $output->writeln('<fg=red> The products were not imported because criteria were not met: ' . count($products['failed']) . '</>');

        return Command::SUCCESS;
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
     * Create file path and get string from CSV file and supply it in array
     * @return array
     */
    private function csvRowsProvider(): array
    {
        $file = $this->projectDir . $this->inputFile;

        if (!file_exists($file)) {
            throw new \Exception($this->inputFile . ' is not exists!');
        }

        $decoder = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);
        return $decoder->decode(file_get_contents($file), 'csv');
    }

    /**
     * Split products by criteria
     * @param array $inputRows
     * @param false $test
     * @return array
     * @throws \Exception
     */
    private function productsMapper(array $inputRows, bool $test = false): array
    {
        $products = [];
        $mappedRows = $this->mapRows($inputRows);

        if (!count($mappedRows['valid'])) {
            throw new \Exception('You have no valid data to import');
        }

        foreach ($mappedRows['valid'] as $row) {
            if ($row['Stock'] < $this->maxStock && $row['Cost in GBP'] < $this->minPrice) {
                $products['failed'][] = $row;
            } elseif ($row['Cost in GBP'] > $this->maxPrice) {
                $products['failed'][] = $row;
            } elseif (!empty($row['Discontinued']) && $row['Discontinued'] === 'yes') {
                $row = $this->mergeParams($row);

                if (!$test) {
                    $row = $this->storeProduct($row, true);
                }

                $products['passed'][] = $row;
            } else {
                $row = $this->mergeParams($row);
                $products['passed'][] = !$test ? $this->storeProduct($row) : $row;
            }
        }

        return $products;
    }

    /**
     * @param $row
     * @return array
     */
    private function mergeParams(array $row): array
    {
        $row['maxStock'] = $this->maxStock;
        $row['minPrice'] = $this->minPrice;
        $row['maxPrice'] = $this->maxPrice;

        return $row;
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

        $productData = $this->entityManager
            ->getRepository(ProductData::class)
            ->findOneBy(['productCode' => $product['Product Code']]);

        if (!$productData) {
            $productData = new ProductData();
        }

        $productData->setProductName($product['Product Name']);
        $productData->setProductDescription($product['Product Description']);
        $productData->setProductCode($product['Product Code']);
        $productData->setAddedAt($timestamp);
        $productData->setCreatedAt($timestamp);
        $productData->setDiscontinuedAt($discontinued ? $timestamp : null);
        $productData->setMaxStock($product['maxStock']);
        $productData->setMinPrice($product['minPrice']);
        $productData->setMaxPrice($product['maxPrice']);

        $this->entityManager->persist($productData);
        $this->entityManager->flush();

        return (bool)$productData->getId();
    }

    /**
     * Map rows by valid content
     * @param array $inputRows
     * @return array
     */
    private function mapRows(array $inputRows): array {
        $rows = [];

        foreach ($inputRows as $row) {
            $passed = 0;
            $passed += !empty($row['Product Code']) && is_string($row['Product Code']);
            $passed += !empty($row['Stock']) && is_numeric($row['Stock']);
            $passed += !empty($row['Cost in GBP']) && $this->checkPrice($row['Cost in GBP']);
            if ($passed === 3) {
                $rows['valid'][] = $row;
            } else {
                $rows['invalid'][] = $row;
            }
        }

        return $rows;
    }

    /**
     * Check valid cost
     * @param string $price
     * @return bool
     */
    private function checkPrice(string $price): bool
    {
        preg_match('/^((\d+)|(\d{1,3})(\,\d{3})*)(\.\d{2})$/', $price, $matches);
        return !!count($matches);
    }

    /**
     * Validate input product parameters
     * @throws \Exception
     */
    private function validate(): void
    {
        $this->question->setValidator(function ($answer) {

            if (empty($answer)) {
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
