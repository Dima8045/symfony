<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ProductData;
use App\Service\ImportProductLogger;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
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
    private string $importFileDir;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $importLogger, string $importFileDir)
    {
        $this->entityManager = $entityManager;
        $this->importFileDir = $importFileDir;

        $this->logger = $importLogger;

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
            $file = $this->importFileDir . 'test.csv';
        } else {
            $file = $this->importFileDir . 'stock.csv';
        }

        if (!file_exists($file)) {
            $this->logger->error('Import file ' . $file . ' is not exists.');

            return Command::SUCCESS;
        }

        $this->maxStock = (int) $input->getOption('max-stock');

        if (!$this->maxStock) {
            return Command::FAILURE;
        }

        $this->minPrice = $input->getOption('min-price');
        $this->maxPrice = $input->getOption('max-price');


        $rows = $this->csvRowsProvider($file);

        $mappedRows = $this->mapRows($rows);

        if (!count($mappedRows['valid'])) {
            $this->logger->notice('You have no valid data to import');
        }

        $products = $this->productsMapper($mappedRows);

        $message = (
            'Available: ' . (count($products['passed']) + count($products['failed']))
            . ' Success: ' . count($products['passed'])
            . ' Failed: ' . count($products['failed']));

        if (count($products['failed'])) {
            $message .= "\nFailed list:";
        }

        $this->logger->info($message, $products['failed']);

        $output->writeln('<fg=white> Available products for processing: ' . (count($products['passed']) + count($products['failed'])) . '</>');
        $output->writeln('<info> Successfully imported products: ' . count($products['passed']) . '</info>');
        $output->writeln('<fg=red> The products were not imported because criteria were not met: ' . count($products['failed']) . '</>');

        return Command::SUCCESS;
    }

    /**
     * Create file path and get string from CSV file and supply it in array
     * @param string $file
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
                $products['passed'][] = $this->storeProduct($row, true);
            } else {
                $products['passed'][] = $this->storeProduct($row);
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

        $productData = $this->entityManager
            ->getRepository(ProductData::class)
            ->findOneBy(['productCode' => $product['Product Code']]);

        if (!$productData) {
            $productData = new ProductData();
        }

        $productData->setProductName($product['Product Name']);
        $productData->setProductDescription($product['Product Description']);
        $productData->setProductCode($product['Product Code']);
        $productData->setCreatedAt($timestamp);
        $productData->setUpdatedAt($timestamp);
        $productData->setDiscontinuedAt($discontinued ? $timestamp : null);
        $productData->setStock((int) $product['Stock']);
        $productData->setCost($product['Cost in GBP']);

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
