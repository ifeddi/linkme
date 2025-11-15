<?php

namespace App\Entity;

use App\Repository\ConversationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
class Conversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // IMPORTANT: toujours stocker user1 = min(id), user2 = max(id)
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private User $user1;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private User $user2;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastMessageAt = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $lastMessagePreview = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $unread_user1 = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $unread_user2 = 0;

    public function getId(): ?int { return $this->id; }

    public function getUser1(): User { return $this->user1; }
    public function setUser1(User $u): self { $this->user1 = $u; return $this; }

    public function getUser2(): User { return $this->user2; }
    public function setUser2(User $u): self { $this->user2 = $u; return $this; }

    public function getLastMessageAt(): ?\DateTimeImmutable { return $this->lastMessageAt; }
    public function setLastMessageAt(?\DateTimeImmutable $d): self { $this->lastMessageAt = $d; return $this; }

    public function getLastMessagePreview(): ?string { return $this->lastMessagePreview; }
    public function setLastMessagePreview(?string $p): self { $this->lastMessagePreview = $p; return $this; }

    public function getUnreadUser1(): int { return $this->unread_user1; }
    public function setUnreadUser1(int $n): self { $this->unread_user1 = $n; return $this; }

    public function getUnreadUser2(): int { return $this->unread_user2; }
    public function setUnreadUser2(int $n): self { $this->unread_user2 = $n; return $this; }
}
