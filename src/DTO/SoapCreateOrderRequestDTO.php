<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class SoapCreateOrderRequestDTO
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    public ?string $clientName = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    public ?string $clientSurname = null;

    #[Assert\Email]
    #[Assert\Length(max: 150)]
    public ?string $email = null;

    #[Assert\Length(max: 255)]
    public ?string $companyName = null;

    #[Assert\Length(max: 1000)]
    public ?string $description = null;

    /**
     * @var SoapOrderItemDTO[]
     */
    #[Assert\Valid]
    #[Assert\Count(min: 1)]
    public array $items = [];

    public function getFullClientName(): string
    {
        return trim(($this->clientName ?? '') . ' ' . ($this->clientSurname ?? ''));
    }

    public function toOrderDTO(): OrderDTO
    {
        $orderDTO = new OrderDTO();
        $orderDTO->clientName = $this->clientName;
        $orderDTO->clientSurname = $this->clientSurname;
        $orderDTO->email = $this->email;
        $orderDTO->companyName = $this->companyName;
        $orderDTO->description = $this->description;
        $orderDTO->name = 'SOAP Order for ' . $this->getFullClientName();

        // Convert items to articles
        foreach ($this->items as $item) {
            $articleDTO = new OrderArticleDTO();
            $articleDTO->articleId = $item->articleId;
            $articleDTO->articleCode = $item->articleCode;
            $articleDTO->articleName = $item->articleName;
            $articleDTO->amount = $item->amount;
            $articleDTO->price = $item->price;
            $articleDTO->currency = $item->currency;
            $articleDTO->measure = $item->measure;

            $orderDTO->articles[] = $articleDTO;
        }

        return $orderDTO;
    }
}
