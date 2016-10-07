# UcsfOrmOrm

(This document is a work in progress.)

A Symfony bundle that provides a Doctrine-based ORM over REST services

This bundle began as a need to connect Symfony applications with back-end REST services. Rather than
duplicating <a href="https://github.com/guzzle/guzzle">Guzzle</a> code across controller-injected services or making
a complete new and custom library for them to share,
I wanted to have bona fide entity managers, configured like a Doctrine entity manger, managing REST data.
Rather than creating an actual Doctrine driver for REST (a possible retooling in the future) this bundle takes a middle
 approach by using Doctrine's facilities to emulate
that sort of coupling. What you get are configurable entity managers with repositories, a managed way to
initiate specific and detailed REST commands, and the ability to have REST data hydrate Symfony entities that are
configured with Doctrine annotation. The configuration is fairly simple and though different from how Doctrine
itself is configured it otherwise works fairly the same during application development.

## Installation

* Add to composer.json
 * <code>"ucsf/restorm": "dev-master"</code>
* Add the bundle to AppKernel.php
 * <code>new Ucsf\LdapOrmBundle\UcsfRestOrmBundle()</code>
* Install using composer
 * <code>$ composer install ucsf/restorm-bundle</code>

## Documentation

### Develop with RestLdapOrm

#### Configure a REST service in config.yml

```
ucsf_rest_orm:
    connections:
        foo_api:
            base_uri: %foo.base_uri%
            username: %foo.username%
            password: %foo.password%
    entity_managers:
        foo_api:
            # The connection and the entity_manager do not need to be named the same, but they
            # are so named here to show the convenience of matching them up. There can be multiple
            # entity_managers using the same connection.
            connection: foo_api
            repositories:
                NiceCompany\AwesomeBundle\Entity\Widget:
                    persist:
                        method: POST
                        path: widgets
                    all:
                        method: GET
                        path: widgets
                    find:
                      byId:
                          method: GET
                          path: widgets/{{id}}
                      byFactory:
                          method: GET
                          path: widgets?factory={{factoryId}}
                NiceCompany\AwesomeBundle\Entity\Factory:
                    find:
                      byId:
                          method: GET
                          path: factories/{{id}}
            # Commands do not have to copy repository method configuration, this just
            # shows command-based analogs of the repository examples.
            commands:
                get_all_widgets:
                    method: GET
                    path: widgets
                    class: NiceCompany\AwesomeBundle\Entity\Widget
                get_widget:
                    method: GET
                    path: widgets/{{id}}
                    class: NiceCompany\AwesomeBundle\Entity\Widget
                get_widget_by_factory:
                    method: GET
                    path: path: widgets?factory={{factoryId}}
                    class: NiceCompany\AwesomeBundle\Entity\Widget
                delete_widget:
                    # For this delete, there's no id supplied, but the object
                    # to delete itself as the body of the request. See how it's
                    # coded below
                    method: DELETE
                    path: widgets
                    class: NiceCompany\AwesomeBundle\Entity\Widget
```

#### Dependency injection for REST Entity Managers and Services

```
    widget_service:
        class: NiceCompany\AwesomeBundle\Services\WidgetService
        arguments:
            # Note how the entity manager's reference is built as
            #   'ucsf_rest_orm.'.$entityManagerName.'_entity_manager'
            entityManager: "@ucsf_rest_orm.foo_api_entity_manager"

```

#### Creating Entities (usually to represent an object class)

It works pretty much as Doctrine entities that are generated from a real SQL database. I have not
experimented with much more of the Doctrine annotation than what you see here.

```
/**
 * @ORM\Table(name="widget")
 * @ORM\Entity
 */
class Widget {

    /**
     * @var integer
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="widgetName", type="string")
     */
    private $widgetName;

    ...


```



#### Coding the Service

Once you get to this point, it's pretty much just like any other Doctrine entity manager.

```
use UCSF\RestOrmBundle\Doctrine\ORM\EntityManager;
use NiceCompany\AwesomeBundle\Entity\Widget;
use NiceCompany\AwesomeBundle\Entity\Factory;

class WidgetService
{
    private $entityManager;
    private $widgetRepository;

    public function __construct(EntityManager $entityManager) {
        $this->entityManager = $entityManager;
        $this->widgetRepository = $entityManager->getRepository(Widget::class);
        $this->factoryRepository = $entityManager->getRepository(Factory::class);
    }

    // Using repositories

    public function getWidgetById($id) {
        return $this->roleRepository->findById($id);
    }

    public function saveWidget(Widget $widget) {
        return $this->entityManager->persist($widget);
    }

    // Using commands

    public function getWidgetsByFactory($widgetId) {
        return $this->entityManager->command('get_widgets_by_factory', ['widgetId' => $widgetId]);
    }

    public function deleteWidget(Widget $widget) {
        return $this->entityManager->command('delete_widget', [] /* null is fine too */, $widget);
    }
        
```
