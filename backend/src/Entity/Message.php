<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Index(columns: ['created_at'])]
#[ORM\Index(columns: ['conversation_id'])]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Conversation::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private Conversation $conversation;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private User $sender;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'boolean', options: ['default' => 0])]
    private bool $isSticker = false;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $stickerCode = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getConversation(): Conversation { return $this->conversation; }
    public function setConversation(Conversation $c): self { $this->conversation = $c; return $this; }

    public function getSender(): User { return $this->sender; }
    public function setSender(User $u): self { $this->sender = $u; return $this; }

    public function getContent(): string { return $this->content; }
    public function setContent(string $c): self { $this->content = $c; return $this; }

    public function isSticker(): bool { return $this->isSticker; }
    public function setIsSticker(bool $b): self { $this->isSticker = $b; return $this; }

    public function getStickerCode(): ?string { return $this->stickerCode; }
    public function setStickerCode(?string $s): self { $this->stickerCode = $s; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $d): self { $this->createdAt = $d; return $this; }

    public function getReadAt(): ?\DateTimeImmutable { return $this->readAt; }
    public function setReadAt(?\DateTimeImmutable $d): self { $this->readAt = $d; return $this; }
}
