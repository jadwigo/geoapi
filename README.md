GeoAPI extension
================

This extension allows you to search for addresses near a latitude/longitude location or near another address.

You can use this to plot the addresses on a google map.

================


### Installation

To get going run the following command, replacing the last argument with the name of your extension:

`composer create-project --no-install 'jadwigo/geoapi:^3.0' <newextname>`  

### Extension Content

This extension includes examples for the following features:

- Routes
  - Handling inside extension class
  - Handling inside a controller class
  - Twig response
  - JSON response
  - URL parameter
  - GET and POST parameter

- Accessing config
   - Extension config
   - Global config

- Own Twig functions

- Own Fieldtypes

- Event listeners
  - Handling inside extension class
  - Handling inside a listener class

- Own Menu Options
  
### Routes

This extension adds several example routes to the system to show you how to define them inline in your Extension class or in a separate controller.

`/async/geoapi` returns "Hello, Bolt!" as plain text.

`/async/geoapi/in/controller` returns "Hello, World!" as plain text but will be handled in a controller class.

`/async/geoapi/json` returns a key value pair in JSON.

`/async/geoapi/parameter/{id}` returns the given url parameter as JSON.

`/async/geoapi/get-parameter?foo=bar&baz=foo&id=7` returns the given GET parameter as JSON.

`/async/geoapi/template` returns content from a Twig template.


### Own Menu Options

This extension adds `/bolt/geoapi-backend-page` as `Custom Page` to the menu in the admin backend. It appears under `Extras`. 

You are not limited to just backend sites or sites served by Bolt. You can add any URL to the menu in the admin backend.

----

For more information, see this page in the Bolt documentation: https://docs.bolt.cm/extensions/config 
