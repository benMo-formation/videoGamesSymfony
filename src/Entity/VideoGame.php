<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Repository\VideoGameRepository;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VideoGameRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['video_game_details']],
            security: 'is_granted("PUBLIC_ACCESS")'
        ),
        new Get(
            normalizationContext: ['groups' => ['video_game_details']],
            security: 'is_granted("PUBLIC_ACCESS")'
        ),
        new Post(
            normalizationContext: ['groups' => ['video_game_details']],
            security: 'is_granted("ROLE_ADMIN")'
        ),
        new Put(
            normalizationContext: ['groups' => ['video_game_details']],
            security: 'is_granted("ROLE_ADMIN")'
        ),
        new Delete(
            security: 'is_granted("ROLE_ADMIN")'
        )
    ],
    paginationEnabled: true
)]
class VideoGame
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['video_game_details'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['video_game_details'])]
    #[Assert\NotBlank(message: "Le titre du jeu ne peut pas être vide")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Le titre doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $title = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['video_game_details'])]
    #[Assert\NotNull(message: "La date de sortie est obligatoire")]
    #[Assert\Type("\DateTimeInterface", message: "La date doit être valide")]
    private ?\DateTimeInterface $releaseDate = null;

    #[ORM\Column(length: 1000)]
    #[Groups(['video_game_details'])]
    #[Assert\NotBlank(message: "La description ne peut pas être vide")]
    #[Assert\Length(
        min: 10,
        max: 1000,
        minMessage: "La description doit contenir au moins {{ limit }} caractères",
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'VideoGame')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['video_game_details'])]
    #[Assert\NotNull(message: "Un éditeur doit être spécifié")]
    private ?Editor $editor = null;

    #[ORM\ManyToOne(inversedBy: 'VideoGame')]
    #[Groups(['video_game_details'])]
    #[Assert\NotNull(message: "Une catégorie doit être spécifiée")]
    private ?Category $category = null;

    #[ORM\ManyToOne(inversedBy: 'VideoGame')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Users $users = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getReleaseDate(): ?\DateTimeInterface
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(\DateTimeInterface $releaseDate): static
    {
        $this->releaseDate = $releaseDate;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getEditor(): ?Editor
    {
        return $this->editor;
    }

    public function setEditor(?Editor $editor): static
    {
        $this->editor = $editor;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getUsers(): ?Users
    {
        return $this->users;
    }

    public function setUsers(?Users $users): static
    {
        $this->users = $users;

        return $this;
    }
}