<?php

namespace Bolt\Extension\Jadwigo\GeoApi\Controller;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller class.
 *
 * @author Lodewijk Evers <jadwigo@gmail.com>
 */
class GeoApiController implements ControllerProviderInterface
{
    /** @var array The extension's configuration parameters */
    private $config;

    /**
     * Initiate the controller with Bolt Application instance and extension config.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Specify which method handles which route.
     *
     * Base route/path is '/async/geoapi'
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app)
    {
        /** @var $ctr \Silex\ControllerCollection */
        $ctr = $app['controllers_factory'];

        // /async/geoapi
        $ctr->get('' , [$this, 'routeGeoApiRoot'])
            ->bind('geoapi-api-root'); 

        // /async/geoapi/get-parameter
        $ctr->get('/get', [$this, 'geoapiUrlGetAllHandler'])
            ->bind('geoapi-url-parameter-get');

        // /async/geoapi/get/controller
        $ctr->get('/get/{id}', [$this, 'geoapiUrlGetJSONHandler'])
            ->bind('geoapi-url-get-id-controller');

        // /async/geoapi/put/controller
        $ctr->get('/put/{id}', [$this, 'geoapiUrlPostJSONHandler'])
            ->bind('geoapi-url-put-id-controller'); 

        // /async/geoapi/template
        $ctr->get('/debug', [$this, 'geoapiUrlTemplate'])
            ->bind('geoapi-url-template');

        return $ctr;
    }


    /**
     * Handles GET requests on the /async/geoapi route.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function routeGeoApiRoot(Request $request)
    {
        $jsonResponse = new JsonResponse();

        $jsonResponse->setData([
            'message' => 'Hello, GeoApi!'
        ]);

        // wrap the output in an jsonp request
        if ($request->query->has('callback')) {
          $jsonResponse->setCallback($request->query->get('callback'));
        }

        return $jsonResponse;
    }

    /**
     * Handles GET requests on /async/geoapi/get?x=y and return with json.
     * example: http://localhost/async/geoapi/get?id=123
     * example: http://localhost/async/geoapi/get?slug=abc
     * example: http://localhost/async/geoapi/get?region=zh
     * example: http://localhost/async/geoapi/get?city=Den%20Haag
     * example: http://localhost/async/geoapi/get?location=52.432,4.2
     * example: http://localhost/async/geoapi/get?location=52.432,4.2&distance=20
     * example: http://localhost/async/geoapi/get?latitude=52.234&longitude=4.2
     * example: http://localhost/async/geoapi/get?latitude=52.234&longitude=4.2&distance=20
     * example: http://localhost/async/geoapi/get?postcode=1234ab&distance=20
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function geoapiUrlGetAllHandler(Application $app, Request $request)
    {
        $jsonResponse = new JsonResponse();

        $valid_keys = [
            "id",
            "slug",
            "location",
            "latitude",
            "longitude",
            "postcode",
            "region",
            "city",
            "amount",
            "distance"
        ];

        foreach($valid_keys as $key) {
            if ($request->query->has($key)) {
                $parms[$key] = $request->query->get($key);
            }
        }

        if(!isset($parms['amount'])) {
            $parms['amount'] = 10;
        }
        // Getting a repository via alias.
        $repo = $app['storage']->getRepository('addresses');

        if(isset($parms['id'])) {
            $results['address'] = $repo->find($parms['id']);
        } elseif(isset($parms['slug'])) {
            $results['address'] = $repo->findOneBy(['slug' => $parms['slug']]);
        } elseif(isset($parms['region'])) {
            $criteria = [
                'status'    => 'published',
                'provincie' => $parms['region']
            ];
            $orderby = ['datepublish', 'DESC'];
            $results['addresses'] = $repo->findBy($criteria, $orderby, $parms['amount']);
        } elseif(isset($parms['city'])) {
            $criteria = [
                'status'    => 'published',
                'plaats'    => $parms['city']
            ];
            $orderby = ['datepublish', 'DESC'];
            $results['addresses'] = $repo->findBy($criteria, $orderby, $parms['amount']);
        } elseif(
            isset($parms['location'])
            || (isset($parms['latitude']) && isset($parms['longitude']))
        ) {
            if(isset($parms['location'])) {
                list($latitude, $longitude) = explode(',', $parms['location']);
            } elseif(isset($parms['latitude']) && isset($parms['longitude'])) {
                $latitude = $parms['latitude'];
                $longitude = $parms['longitude'];
            }

            if(isset($parms['distance'])) {
                $max_distance = $parms['distance'];
            } else {
                $max_distance = 10;
            }

            $basequery = 'SELECT id, slug, latitude, longitude, postcode, plaats, provincie,
                accuracy, achternaam, adres, bigregistratienr, businessid,
                datechanged, datecreated, datepublish, datedepublish,
                email, googlemapslink, kvknr, land, locationid, soort, telefoon, title, 
                tussenvoegsels, voorletters, website,
                ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance';
            $basequery .= ' FROM bolt_addresses';
            $basequery .= ' WHERE status = "published"';
            $basequery .= ' HAVING distance < ?';
            $basequery .= ' ORDER BY distance';
            $basequery .= ' LIMIT 0, '. $parms['amount'];
            $params = array($latitude, $longitude, $latitude, $max_distance);
            $results['addresses'] = $app['db']->fetchAll($basequery, $params);
        } else {
            $results = null;
        }


        $jsonResponse->setData([
            'all' => $request->query->all(),
            'parms' => $parms,
            'results' => $results
        ]);
        //dump($app, $parms, $jsonResponse);

        // wrap the output in an jsonp request
        if ($request->query->has('callback')) {
          $jsonResponse->setCallback($request->query->get('callback'));
        }

        return $jsonResponse;
    }

    /**
     * Handles GET requests on /async/geoapi/get/{id} and return with json.
     * example: http://localhost/async/geoapi/get/{id}?foo=bar&baz=foo&id=7
     *
     * @param Request $request
     * @param $id
     *
     * @return JsonResponse
     */
    public function geoapiUrlGetJSONHandler(Request $request, $id)
    {
        $jsonResponse = new JsonResponse();

        $jsonResponse->setData([
            'all' => $request->query->all(),
            'id' => $id,
        ]);

        // wrap the output in an jsonp request
        if ($request->query->has('callback')) {
          $jsonResponse->setCallback($request->query->get('callback'));
        }

        return $jsonResponse;
    }

    /**
     * Handles POST requests on /async/geoapi/get-parameter and return with some data as json.
     * example: http://localhost/async/geoapi/put/{id}?foo=bar&baz=foo&id=7
     *
     * @param Request $request
     * @param $id
     *
     * @return JsonResponse
     */
    public function geoapiUrlPostJSONHandler(Request $request, $id)
    {
        $jsonResponse = new JsonResponse();

        $jsonResponse->setData([
            'all' => $request->query->all(),
            'id' => $id,
        ]);

        // wrap the output in an jsonp request
        if ($request->query->has('callback')) {
          $jsonResponse->setCallback($request->query->get('callback'));
        }

        return $jsonResponse;
    }

    /**
     * Handles GET requests on /async/geoapi/template and return a template.
     *
     * @param Request $request
     *
     * @return string
     */
    public function geoapiUrlTemplate(Application $app, Request $request)
    {
        return $app['twig']->render('geoapi_site.twig', [
            'title' => 'GeoApi',
            'content' => 'This is the root of the GeoApi, you probably want to see the documentation now',
            'request' => $request,
            'query' => $request->query->all(),
            'attributes' => $request->attributes->all(),
            'accept' => $request->headers->get('accept'),
            'headers' => $request->headers->all(),
        ], []);
    }
}
