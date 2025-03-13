<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Repository\EditorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EditorRepository::class)]
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
class Editor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['video_game_details'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['video_game_details'])]
    #[Assert\NotBlank(message: "Le nom de l'éditeur ne peut pas être vide")]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['video_game_details'])]
    #[Assert\NotBlank(message: "Le pays ne peut pas être vide")]
    #[Assert\Country(message: "Ce pays n'est pas valide")]
    private ?string $country = null;

    /**
     * @var Collection<int, VideoGame>
     */
    #[ORM\OneToMany(targetEntity: VideoGame::class, mappedBy: 'editor')]
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

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

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
            $videoGame->setEditor($this);
        }

        return $this;
    }

    public function removeVideoGame(VideoGame $videoGame): static
    {
        if ($this->VideoGame->removeElement($videoGame)) {
            // set the owning side to null (unless already changed)
            if ($videoGame->getEditor() === $this) {
                $videoGame->setEditor(null);
            }
        }

        return $this;
    }
}