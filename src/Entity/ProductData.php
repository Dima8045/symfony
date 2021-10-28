<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="tblProductData")
 * @ORM\Entity
 */
class ProductData
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", name="intProductDataId")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=50, name="strProductName")
     */
    private string $productName;

    /**
     * @ORM\Column(type="string", length=255, name="strProductDesc")
     */
    private string $productDescription;

    /**
     * @ORM\Column(type="string", length=10, name="strProductCode")
     */
    private string $productCode;

    /**
     * @ORM\Column(type="datetime", nullable=true, name="dtmAdded")
     */
    private ?\DateTimeInterface $addedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true, name="dtmDiscontinued")
     */
    private ?\DateTimeInterface $discontinuedAt;

    /**
     * @ORM\Column(type="datetime", name="stmTimestamp")
     */
    private \DateTimeInterface $createdAt;

    /**
     * @ORM\Column(type="integer", nullable=true, name="intMaxStock")
     */
    private int $maxStock;

    /**
     * @ORM\Column(type="decimal", precision=9, scale=2, nullable=true, name="decMaxPrice")
     */
    private string $maxPrice;

    /**
     *
     * @ORM\Column(type="decimal", precision=9, scale=2, nullable=true, name="decMinPrice")
     */
    private string $minPrice;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): self
    {
        $this->productName = $productName;

        return $this;
    }

    public function getProductDescription(): ?string
    {
        return $this->productDescription;
    }

    public function setProductDescription(string $productDescription): self
    {
        $this->productDescription = $productDescription;

        return $this;
    }

    public function getProductCode(): ?string
    {
        return $this->productCode;
    }

    public function setProductCode(string $productCode): self
    {
        $this->productCode = $productCode;

        return $this;
    }

    public function getAddedAt(): ?\DateTimeInterface
    {
        return $this->addedAt;
    }

    public function setAddedAt(?\DateTimeInterface $addedAt): self
    {
        $this->addedAt = $addedAt;

        return $this;
    }

    public function getDiscontinuedAt(): ?\DateTimeInterface
    {
        return $this->discontinuedAt;
    }

    public function setDiscontinuedAt(?\DateTimeInterface $discontinuedAt): self
    {
        $this->discontinuedAt = $discontinuedAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getMaxStock(): ?int
    {
        return $this->maxStock;
    }

    public function setMaxStock(?int $maxStock): self
    {
        $this->maxStock = $maxStock;

        return $this;
    }

    public function getMinPrice(): string
    {
        return $this->minPrice;
    }

    public function setMinPrice(string $minPrice): self
    {
        $this->minPrice = $minPrice;

        return $this;
    }

    public function getMaxPrice(): string
    {
        return $this->maxPrice;
    }

    public function setMaxPrice(string $maxPrice): self
    {
        $this->maxPrice = $maxPrice;

        return $this;
    }
}
