<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ProductData;
use App\Helpers\Logger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

#[AsCommand(
    name: 'app:import-product',
    description: 'Import Products from CSV',
)]
class ImportProductCommand extends Command
{
    private int $maxStock;
    private string $minPrice;
    private string $maxPrice;
    private string $projectDir;
    private EntityManagerInterface $entityManager;
    private Logger $logger;
    private string $logFile;

    public function __construct(EntityManagerInterface $entityManager, string $projectDir)
    {
        $this->entityManager = $entityManager;
        $this->projectDir = $projectDir;
        $importLogDir = $this->projectDir . '/var/log/import';
        $this->logFile = $importLogDir . '/csv-import.log';
        $this->logger = new Logger($importLogDir,  $this->logFile);

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setProcessTitle('Import Products')
            ->addArgument('test', InputArgument::OPTIONAL)
            ->addOption('max-stock', null, InputOption::VALUE_OPTIONAL)
            ->addOption('min-price', null, InputOption::VALUE_OPTIONAL)
            ->addOption('max-price', null, InputOption::VALUE_OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getArgument('test')) {
            $fileName = 'test.csv';
        } else {
            $fileName = 'stock.csv';
        }

        $file = $this->projectDir . '/public/files/' . $fileName;

        if (!file_exists($file)) {
            $this->logger->setMessage('Import file ' . $file . ' is not exists.')->log('error');

            exit;
        }

        $this->maxStock = (int) $input->getOption('max-stock');

        if (!$this->maxStock) {
            $this->logger->setMessage('Max Stock was not specifies')->log('error');

            exit;
        }

        $this->minPrice = $input->getOption('min-price');
        $this->maxPrice = $input->getOption('max-price');


        $rows = $this->csvRowsProvider($file);

        $mappedRows = $this->mapRows($rows);

        if (!count($mappedRows['valid'])) {
            $this->logger->setMessage('You have no valid data to import')->log( 'info');
        }

        $products = $this->productsMapper($mappedRows);

        $this->logger->setMessage(
            'Available: ' . (count($products['passed']) + count($products['failed']))
            . ' Success: ' . count($products['passed'])
            . ' Failed: ' . count($products['failed']));

        if (count($products['failed'])) {
            $message = "\nFailed list:";

            foreach ($products['failed'] as $item) {
                $message .= "\n Product Name: " . $item['Product Name']
                    . ' / Product Code: ' . $item['Product Code'];
            }

            $this->logger->setMessage($message);
        }

        $this->logger->log('success');

        $output->write((count($products['passed']) + count($products['failed'])).','.count($products['passed']).','.count($products['failed']));

        return Command::SUCCESS;
    }

    /**
     * Create file path and get string from CSV file and supply it in array
     * @return array
     */
    private function csvRowsProvider(string $file): array
    {
        $decoder = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);
        return $decoder->decode(file_get_contents($file), 'csv');
    }

    /**
     * Split products by criteria
     * @param array $mappedRows
     * @param false $test
     * @return array
     */
    private function productsMapper(array $mappedRows): array
    {
        $products = [];

        foreach ($mappedRows['valid'] as $row) {
            if ($row['Stock'] < $this->maxStock && $row['Cost in GBP'] < $this->minPrice) {
                $products['failed'][] = $row;
            } elseif ($row['Cost in GBP'] > $this->maxPrice) {
                $products['failed'][] = $row;
            } elseif (!empty($row['Discontinued']) && $row['Discontinued'] === 'yes') {
                $row = $this->mergeParams($row);
                    $row = $this->storeProduct($row, true);

                $products['passed'][] = $row;
            } else {
                $row = $this->mergeParams($row);
                $products['passed'][] = $this->storeProduct($row);
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
            $passed += !empty($row['Cost in GBP']) && is_numeric($row['Cost in GBP']);
            if ($passed === 3) {
                $rows['valid'][] = $row;
            } else {
                $rows['invalid'][] = $row;
            }
        }

        return $rows;
    }
}
