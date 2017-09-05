<?php
/**
 * Created by PhpStorm.
 * User: jgabler
 * Date: 9/15/16
 * Time: 12:04 AM
 */

namespace Ucsf\RestOrmBundle\Tests;


use Ucsf\RestOrmBundle\Doctrine\ORM\EntityManager;

class PostService
{

    private $entityManager;
    private $postRepository;
    
    public function __construct($config)
    {
        $typicode_config = $config['entity_managers']['typicode_em'];
        $this->entityManager = new EntityManager(
            $config['connections']['typicode'],
            $config['entity_managers']['typicode_em']['repositories'],
            $config['entity_managers']['typicode_em']['commands']
        );
        $this->postRepository = $this->entityManager->getRepository(Post::class);
    }

    public function findPostById($id) {
        return $this->postRepository->findById($id);
    }

    public function getPost($id) {
        return $this->entityManager->command('get_post', ['id' => 1]);

    }
}