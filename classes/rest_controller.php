<?php

class ezSugarRestController extends ezcMvcController
{
    public function doGetinfo()
    {
        $objectMetadata=null;
        if ( isset ($this->sugarId))
        {
            $object = eZContentObject::fetchByRemoteID('hotel_'.$this->sugarId);
            $content=ezpContent::fromObjectId($object->ID);
            $objectMetadata = ezpRestContentModel::getMetadataByContent($content);
        }
        $res = new ezpRestMvcResult();
        $res->variables['metadata'] = $objectMetadata;
        $res->variables['fields'] = ezpRestContentModel::getFieldsByContent($content);
        $res->variables['message'] = 'OK trouve';
        return $res;
    }

}
?>

