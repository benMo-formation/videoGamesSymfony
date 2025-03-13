<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Repository\UsersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UsersRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['user_details']],
            security: 'is_granted("ROLE_ADMIN")'
        ),
        new Get(
            normalizationContext: ['groups' => ['user_details']],
            security: 'is_granted("ROLE_USER") and (object.getUserName() == user.getUserName() or is_granted("ROLE_ADMIN"))'
        ),
        new Post(
            normalizationContext: ['groups' => ['user_details']],
            security: 'is_granted("ROLE_ADMIN")'
        ),
        new Put(
            normalizationContext: ['groups' => ['user_details']],
            security: 'is_granted("ROLE_USER") and (object.getUserName() == user.getUserName() or is_granted("ROLE_ADMIN"))'
        ),
        new Delete(
            security: 'is_granted("ROLE_ADMIN")'
        )
    ],
    paginationEnabled: true
)]
class Users implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user_details'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['user_details'])]
    #[Assert\NotBlank(message: "Le nom d'utilisateur ne peut pas être vide")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Le nom d'utilisateur doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le nom d'utilisateur ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $userName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le mot de passe ne peut pas être vide")]
    #[Assert\Length(
        min: 6,
        minMessage: "Le mot de passe doit contenir au moins {{ limit }} caractères"
    )]
    private ?string $password = null;

    /**
     * @var Collection<int, VideoGame>
     */
    #[ORM\OneToMany(targetEntity: VideoGame::class, mappedBy: 'users')]
    private Collection $VideoGame;

    #[ORM\Column(type: 'json')]
    #[Groups(['user_details'])]
    private array $roles = [];

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user_details'])]
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas valide.")]
    private ?string $email = null;

    public function __construct()
    {
        $this->VideoGame = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function setUserName(string $userName): static
    {
        $this->userName = $userName;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

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
            $videoGame->setUsers($this);
        }

        return $this;
    }

    public function removeVideoGame(VideoGame $videoGame): static
    {
        if ($this->VideoGame->removeElement($videoGame)) {
            // set the owning side to null (unless already changed)
            if ($videoGame->getUsers() === $this) {
                $videoGame->setUsers(null);
            }
        }

        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // Garantir que tous les utilisateurs ont au moins ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // Si vous stockez des données temporaires sensibles sur l'utilisateur, effacez-les ici
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->userName;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }
}