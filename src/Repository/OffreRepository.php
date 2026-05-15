<?php

namespace App\Repository;

use App\Entity\Offre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Offre>
 */
class OffreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offre::class);
    }

    /**
     * Récupère les dernières offres paginées
     *
     * @param int $page numéro de page (>= 1)
     * @param int $limit nombre d'éléments par page
     *
     * @return Offre[]
     */
    public function findLatestPaginated(int $page = 1, int $limit = 10): array
    {
        // Empêche les valeurs négatives pour la page et la limite (programmation défensive)
        $page = max($page, 1);
        $limit = max($limit, 1);

        // Création de la requête vers la base de données, avec l'alias 'o'
        return $this->createQueryBuilder('o')
            // Tri par ordre de temps décroissant, en utilisant l'alias de la requête
            ->orderBy('o.date_de_publication', 'DESC')
            // Elément à partir du quel on démarre
            // (ex page 2 avec 10 éléments : (2 - 1) * 10 = 1 * 10 = 10 -> de 10 à 20 (non inclus car 10 éléments))
            ->setFirstResult(($page - 1) * $limit)
            // Nombre d'éléments maximums retournés par la requête
            ->setMaxResults($limit)
            // Exécution de la requête
            ->getQuery()
            // Recupération des données venant de la requête
            ->getResult();
    }

    //    /**
    //     * @return Offre[] Returns an array of Offre objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Offre
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
