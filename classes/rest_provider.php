<?php

class ezSugarRestProvider implements ezpRestProviderInterface
{
    public function getRoutes()
    {
        return array (new ezpRestVersionedRoute( new ezcMvcRailsRoute( '/getinfo/node/:sugarId', 'ezSugarRestController', 'getinfo' ), 1 ) );

    }

    public function getViewController()
    {
        return new ezSugarRestApiViewController();
    }

}

?>
