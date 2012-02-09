<?php /*

[connexion]
ServerUrl=sugarcrm.local
ServerPath=/soap.php
ServerNamespace=http://www.sugarcrm.com/sugarcrm

[Users]
# ID de l'Admin User
AdminID=14
# je sais pas si c'est utile ....
DefaultGroupID=112

[Tree]
# ID du node parent par default pour la création des objets
DefaultParentNodeID=2
# ID de la section par default pour la création des objets
DefaultSectionID=1

[Names]
# si il y a un prefix à enlever du nom de module SUGAR pour nommer une class EZ
prefixRemove=true
# le prefix eventuel du nom de module SUGAR
prefixString=test_

[Mapping]
# mapping des correspondances des tables SUGAR avec les objets EZ
# ex.: mapping_tables[nom_module_sugar]=prefix_remoteID_ez/class_identifier_ez
mapping_tables[]
mapping_tables[test_Hotel]=hotel

# champs des tables SUGAR (field name) à ignorer pour les objets EZ
exclude_fields[]
exclude_fields[]=id
exclude_fields[]=date_entered
exclude_fields[]=date_modified
exclude_fields[]=modified_user_id
exclude_fields[]=modified_by_name
exclude_fields[]=created_by
exclude_fields[]=created_by_name
exclude_fields[]=team_id
exclude_fields[]=team_set_id
exclude_fields[]=team_count
exclude_fields[]=team_name
exclude_fields[]=assigned_user_id
exclude_fields[]=assigned_user_name

# mapping des correspondances des types de champs SUGAR avec les datatype EZ
# ex.: mapping_types[sugar_field_type]=ez_datatype
mapping_types[] 
mapping_types[id]=ezstring
mapping_types[bool]=ezboolean
mapping_types[text]=eztext
mapping_types[name]=ezstring
mapping_types[datetime]=ezdatetime

*/ ?>
