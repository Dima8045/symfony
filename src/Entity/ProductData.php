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
    private ?\DateTimeInterface $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true, name="dtmDiscontinued")
     */
    private ?\DateTimeInterface $discontinuedAt;

    /**
     * @ORM\Column(type="integer", nullable=true, name="intStock")
     */
    private int $stock;

    /**
     * @ORM\Column(type="decimal", precision=9, scale=2, nullable=false, name="decCost")
     */
    private string $cost;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=false, name="stmTimestamp")
     */
    private \DateTimeImmutable $updatedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setProductName(string $productName): self
    {
        $this->productName = $productName;

        return $this;
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

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function setDiscontinuedAt(?\DateTimeInterface $discontinuedAt): self
    {
        $this->discontinuedAt = $discontinuedAt;

        return $this;
    }

    public function setStock(?int $stock): self
    {
        $this->stock = $stock;

        return $this;
    }

    public function setCost(string $cost): self
    {
        $this->cost = $cost;

        return $this;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
