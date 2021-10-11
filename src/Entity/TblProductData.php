<?php

namespace App\Entity;

use App\Repository\TblProductDataRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TblProductDataRepository::class)
 */
class TblProductData
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $intProductDataId;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $strProductName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $strProductDesc;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $strProductCode;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dtmAdded;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dtmDiscontinued;

    /**
     * @ORM\Column(type="datetime")
     */
    private $stmTimestamp;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $maxStock;

    /**
     * @ORM\Column(type="decimal", precision=9, scale=2, nullable=true)
     */
    private $maxPrice;

    /**
     *
     * @ORM\Column(type="decimal", precision=9, scale=2, nullable=true)
     */
    private $minPrice;

    public function getIntProductDataId(): ?int
    {
        return $this->intProductDataId;
    }

    public function setIntProductDataId(int $intProductDataId): self
    {
        $this->intProductDataId = $intProductDataId;

        return $this;
    }

    public function getStrProductName(): ?string
    {
        return $this->strProductName;
    }

    public function setStrProductName(string $strProductName): self
    {
        $this->strProductName = $strProductName;

        return $this;
    }

    public function getStrProductDesc(): ?string
    {
        return $this->strProductDesc;
    }

    public function setStrProductDesc(string $strProductDesc): self
    {
        $this->strProductDesc = $strProductDesc;

        return $this;
    }

    public function getStrProductCode(): ?string
    {
        return $this->strProductCode;
    }

    public function setStrProductCode(string $strProductCode): self
    {
        $this->strProductCode = $strProductCode;

        return $this;
    }

    public function getDtmAdded(): ?\DateTimeInterface
    {
        return $this->dtmAdded;
    }

    public function setDtmAdded(?\DateTimeInterface $dtmAdded): self
    {
        $this->dtmAdded = $dtmAdded;

        return $this;
    }

    public function getDtmDiscontinued(): ?\DateTimeInterface
    {
        return $this->dtmDiscontinued;
    }

    public function setDtmDiscontinued(?\DateTimeInterface $dtmDiscontinued): self
    {
        $this->dtmDiscontinued = $dtmDiscontinued;

        return $this;
    }

    public function getStmTimestamp(): ?\DateTimeInterface
    {
        return $this->stmTimestamp;
    }

    public function setStmTimestamp(\DateTimeInterface $stmTimestamp): self
    {
        $this->stmTimestamp = $stmTimestamp;

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

    public function getMinPrice(): ?string
    {
        return $this->minPrice;
    }

    public function setMinPrice(?string $minPrice): self
    {
        $this->minPrice = $minPrice;

        return $this;
    }

    public function getMaxPrice(): ?string
    {
        return $this->maxPrice;
    }

    public function setMaxPrice(?string $maxPrice): self
    {
        $this->maxPrice = $maxPrice;

        return $this;
    }
}
