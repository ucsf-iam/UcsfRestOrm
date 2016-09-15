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
        $this->entityManager = new EntityManager($config, 'typicode_em');
        $this->postRepository = $this->entityManager->getRepository(Post::class);
    }

    public function findPostById($id) {
        return $this->postRepository->findById($id);
    }

    public function getPost($id) {
        return $this->entityManager->command('get_post', ['id' => 1]);

    }
}