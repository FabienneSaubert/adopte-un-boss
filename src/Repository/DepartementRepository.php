<?php

namespace App\Repository;

use App\Entity\Departement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Departement>
 */
class DepartementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Departement::class);
    }

    // Recherche en base de données du département par son numéro
    public function findOneByNumero(string $numero): ?Departement
    {
        // On crée un QueryBuilder Doctrine.
        // 'd' est l'alias SQL utilisé pour représenter la table departement dans la requête.
        $qb = $this->createQueryBuilder('d');

        // On ajoute une condition WHERE pour filtrer sur la colonne "numero".
        // Ici "d.numero" correspond au champ "numero" de l'entité Departement.
        $qb->andWhere('d.numero = :numero');

        // On associe la valeur PHP à la variable SQL :numero.
        // Cela évite l'injection SQL et permet à Doctrine de gérer correctement les types.
        $qb->setParameter('numero', $numero);

        // On transforme le QueryBuilder en requête Doctrine exécutable.
        $query = $qb->getQuery();

        // On exécute la requête :
        // - si un département est trouvé : on renvoie l'objet Departement
        // - si aucun résultat : on renvoie null
        // - si plusieurs résultats : Doctrine lèvera une exception (d'où l'intérêt de mettre numero en UNIQUE)
        return $query->getOneOrNullResult();
    }

    //    /**
    //     * @return Departement[] Returns an array of Departement objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Departement
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
