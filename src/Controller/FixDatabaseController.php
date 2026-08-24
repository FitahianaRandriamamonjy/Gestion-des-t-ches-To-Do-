<?php

namespace App\Controller;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FixDatabaseController extends AbstractController
{
    #[Route('/fix-db-schema-now', name: 'fix_db_schema')]
    public function fixDb(EntityManagerInterface $entityManager): Response
    {
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->updateSchema($metadata, true);

        return new Response('Base de donnees mise a jour avec succes !');
    }
}