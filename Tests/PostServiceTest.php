<?php
/**
 * Created by PhpStorm.
 * User: jgabler
 * Date: 9/15/16
 * Time: 12:06 AM
 */

namespace Ucsf\RestOrmBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;

class PostServiceTest extends WebTestCase
{
    private $config;
    private $service;

    public function setUp()
    {
        $kernel = static::bootKernel();
        $container = $kernel->getContainer();

        $locator = new FileLocator(__DIR__);
        $restOrmConfigFiles = $locator->locate('config.yml', null, false);
        $this->config = Yaml::parse(file_get_contents($restOrmConfigFiles[0]));

        $this->service = new PostService($this->config['ucsf_rest_orm']);
    }



    public function testConfigCreation() {
        $this->assertNotNull($this->service);
    }

    public function testFindById() {
        $post = $this->service->findPostById(1);
        $this->assertEquals(
            "sunt aut facere repellat provident occaecati excepturi optio reprehenderit",
            $post->getTitle()
        );
    }

    public function testGetPost() {
        $post = $this->service->getPost(1);
        $this->assertEquals(
            "sunt aut facere repellat provident occaecati excepturi optio reprehenderit",
            $post->getTitle()
        );
    }
}