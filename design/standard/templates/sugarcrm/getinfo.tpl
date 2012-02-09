{def $contentClassIdentifiant=$node.content_class.identifier}
<{$contentClassIdentifiant}>
   {foreach $node.data_map as $data}
       <{$data.contentclass_attribute_identifier}>
           <![CDATA[{attribute_view_gui attribute=$data} ]]>
       </{$data.contentclass_attribute_identifier}>
   {/foreach}
</{$contentClassIdentifiant}>

