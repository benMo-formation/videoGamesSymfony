<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['category_details']],
            security: 'is_granted("PUBLIC_ACCESS")'
        ),
        new Get(
            normalizationContext: ['groups' => ['category_details']],
            security: 'is_granted("PUBLIC_ACCESS")'
        ),
        new Post(
            normalizationContext: ['groups' => ['category_details']],
            security: 'is_granted("ROLE_ADMIN")'
        ),
        new Put(
            normalizationContext: ['groups' => ['category_details']],
            security: 'is_granted("ROLE_ADMIN")'
        ),
        new Delete(
            security: 'is_granted("ROLE_ADMIN")'
        )
    ],
    paginationEnabled: true
)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['category_details', 'video_game_details'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['category_details', 'video_game_details'])]
    #[Assert\NotBlank(message: "Le nom de la catégorie ne peut pas être vide")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $name = null;

    /**
     * @var Collection<int, VideoGame>
     */
    #[ORM\OneToMany(targetEntity: VideoGame::class, mappedBy: 'category')]
    private Collection $VideoGame;

    public function __construct()
    {
        $this->VideoGame = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, VideoGame>
     */
    public function getVideoGame(): Collection
    {
        return $this->VideoGame;
    }

    public function addVideoGame(VideoGame $videoGame): static
    {
        if (!$this->VideoGame->contains($videoGame)) {
            $this->VideoGame->add($videoGame);
            $videoGame->setCategory($this);
        }

        return $this;
    }

    public function removeVideoGame(VideoGame $videoGame): static
    {
        if ($this->VideoGame->removeElement($videoGame)) {
            // set the owning side to null (unless already changed)
            if ($videoGame->getCategory() === $this) {
                $videoGame->setCategory(null);
            }
        }

        return $this;
    }
}