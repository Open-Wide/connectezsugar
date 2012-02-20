<?php /*

[connexion]
ServerUrl=sugarcrm.local
ServerPath=/soap.php
ServerNamespace=http://www.sugarcrm.com/sugarcrm


[Language]
defaultLanguage=fr


[Names]
# si il y a un prefix à enlever du nom de module SUGAR pour nommer une class EZ en absence de mapping
prefixRemove=false
# le prefix eventuel du nom de module SUGAR
# pas pris en compte si 'prefixRemove' est à false
prefixString=test_


# mapping des correspondances des tables SUGAR avec les objets EZ
[Mapping]
# mapping des correspondances des noms de modules SUGAR avec les noms des classes EZ
# ex.: mapping_names[nom_module_sugar]=class_name_ez
mapping_names[]
mapping_names[test_Hotel]=Hotel

# mapping des correspondances des noms de modules SUGAR avec les identifiers des classes EZ
# ex.: mapping_identifiers[nom_module_sugar]=class_identifier_ez
mapping_identifiers[]
mapping_identifiers[test_Hotel]=hotel

# champs des tables SUGAR (field name) à ignorer pour les objets EZ
# generique pour tous les modules
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


[Synchro]
# liste des modules SUGAR qui sont concerné par la synchronisation
# @IMPORTANT! : le chronjob de synchronisation viens lire cette liste !!!
modulesListToSynchro[]
modulesListToSynchro[]=test_Hotel


# @IMPORTANT! : pour l'instant [RemoteIdModel] n'est pas utilisé !
# le modele de RemoteId des objets EZ synchronisés avec SUGAR est "eZClassIdentifier_SugarObjectId"
[RemoteIdModel]
# liste de variables pour le model de remote_id
var_list[]
var_list[]=ez_class_identifier
var_list[]=ez_class_name
var_list[]=sugar_object_id
var_list[]=sugar_module_name
var_list[]=sugar_module_libelle

# modele de remote_id EZ en fonction de données SUGAR
# le remote_id est construit avec la concatenation consecutive des elements du tableau "remote_id_model[]"
# les valeurs possibles doivent faire partie de "var_list[]"
remote_id_model[]
remote_id_model[]=ez_class_identifier
remote_id_model[]=_
remote_id_model[]=sugar_object_id

*/ ?>
