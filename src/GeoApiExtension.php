<?php

namespace Bolt\Extension\Jadwigo\GeoApi;

use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Stylesheet;
use Bolt\Controller\Zone;
use Bolt\Events\StorageEvent;
use Bolt\Events\StorageEvents;
use Bolt\Extension\SimpleExtension;
use Bolt\Extension\Jadwigo\GeoApi\Controller\GeoApiController;
use Bolt\Extension\Jadwigo\GeoApi\Listener\StorageEventListener;
use Bolt\Menu\MenuEntry;
use Silex\ControllerCollection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * GeoApi extension class.
 *
 * @author Lodewijk Evers <jadwigo@gmail.com>
 */
class GeoApiExtension extends SimpleExtension
{
    /**
     * {@inheritdoc}
     */
    public function registerFields()
    {
        /*
         * Custom Field Types:
         * You are not limited to the field types that are provided by Bolt.
         * It's really easy to create your own.
         *
         * This example is just a simple text field to show you
         * how to store and retrieve data.
         *
         * See also the documentation page for more information and a more complex example.
         * https://docs.bolt.cm/extensions/customfields
         */

        return [
            new Field\GeoApiField(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function subscribe(EventDispatcherInterface $dispatcher)
    {
        /*
         * Event Listener:
         *
         * Did you know that Bolt fires events based on backend actions? Now you know! :)
         *
         * Let's register listeners for all 4 storage events.
         *
         * The first listener will be an inline function, the three other ones will be in a separate class.
         * See also the documentation page:
         * https://docs.bolt.cm/extensions/essentials#adding-storage-events
         */

        $dispatcher->addListener(StorageEvents::PRE_SAVE, [$this, 'onPreSave']);

        $storageEventListener = new StorageEventListener($this->getContainer(), $this->getConfig());
        $dispatcher->addListener(StorageEvents::POST_SAVE, [$storageEventListener, 'onPostSave']);
        $dispatcher->addListener(StorageEvents::PRE_DELETE, [$storageEventListener, 'onPreDelete']);
        $dispatcher->addListener(StorageEvents::POST_DELETE, [$storageEventListener, 'onPostDelete']);
    }

    /**
     * Handles PRE_SAVE storage event
     *
     * @param StorageEvent $event
     */
    public function onPreSave(StorageEvent $event)
    {
        // The ContentType of the record being saved
        $contenttype = $event->getContentType();

        // The record being saved
        $record = $event->getContent();

        // A flag to tell if the record was created, updated or deleted,
        // for more information see the page in the documentation
        $created = $event->isCreate();

        // Do whatever you want with this data
        // See page in the documentation for a logging example
    }

    /**
     * {@inheritdoc}
     */
    protected function registerAssets()
    {
        return [
            // Web assets that will be loaded in the frontend
            //new Stylesheet('geoapi.css'),
            //new JavaScript('geoapi.js'),
            // Web assets that will be loaded in the backend
            //(new Stylesheet('geoapi_backend.css'))->setZone(Zone::BACKEND),
            //(new JavaScript('geoapi_backend.js'))->setZone(Zone::BACKEND),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigPaths()
    {
        return ['templates'];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigFunctions()
    {
        return [
            'geoapi' => 'geoapiTwig',
        ];
    }

    /**
     * The callback function when {{ geoapi() }} is used in a template.
     *
     * @return string
     */
    public function geoapiTwig()
    {
        $context = [
            'geoapi_random' => mt_rand(),
        ];

        return $this->renderTemplate('geoapi.twig', $context);
    }

    /**
     * {@inheritdoc}
     *
     * Extending the backend menu:
     *
     * You can provide new Backend sites with their own menu option and template.
     *
     * Here we will add a new route to the system and register the menu option in the backend.
     *
     * You'll find the new menu option under "Extras".
     */
    protected function registerMenuEntries()
    {
        /*
         * Define a menu entry object and register it:
         *   - Route http://example.com/bolt/extend/geoapi
         *   - Menu label 'GeoApi Admin'
         *   - Menu icon a Font Awesome small child
         *   - Required Bolt permissions 'settings'
         */
        $adminMenuEntry = (new MenuEntry('geoapi-backend-page', 'geoapi'))
            ->setLabel('GeoApi Admin')
            ->setIcon('fa:child')
            ->setPermission('settings')
        ;

        return [$adminMenuEntry];
    }

    /**
     * {@inheritdoc}
     *
     * Mount the ExampleController class to all routes that match '/async/geoapi/*'
     *
     * To see specific bindings between route and controller method see 'connect()'
     * function in the ExampleController class.
     */
    protected function registerFrontendControllers()
    {
        $app = $this->getContainer();
        $config = $this->getConfig();
        $apipath = $config['apipath'];

        return [
            $apipath => new GeoApiController($config),
        ];
    }


    /**
     * {@inheritdoc}
     */
    protected function registerBackendControllers()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerBackendRoutes(ControllerCollection $collection)
    {
        $collection->match('/extend/geoapi', [$this, 'exampleBackendPage']);
    }

    /**
     * Handles GET requests on /bolt/geoapi-backend-page and return a template.
     *
     * @param Request $request
     *
     * @return string
     */
    public function exampleBackendPage(Request $request)
    {
        return $this->renderTemplate('geoapi_backend_site.twig', ['title' => 'GeoAPI Page']);
    }


    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfig()
    {
        $key = null;
        // set the global api key as default - if it exists
        $app = $this->getContainer();
        $key = $app['config']->get('general/google_api_key');
        return [
            'apipath' => 'async/geoapi',
            'contenttype' => 'addresses',
            'authkey' => $key,
            'use_cache' => true,
            'cache_time' => (60*60*24*5)
        ];
    }
}
