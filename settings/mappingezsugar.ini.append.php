<?php /*

# chaque bloc correspond à un module SUGAR
# [module_name]
# 2 tableau par bloc : 
# sugarez[] : correspondences SUGAR => EZ ( ex.: sugarez[field_name_sugar]=attribut_identifier_ez )
#exclude_fields[] : champs de la table SUGAR (field name) à ignorer pour ce module SUGAR

[test_Hotel]
sugarez[]
sugarez[name]=name
sugarez[description]=description_changed
sugarez[deleted]=deleted

# @TODO voir comment faire un merge de exclude_fields generic et le specifique par module
# champs de la table SUGAR (field name) à ignorer pour cet module SUGAR
# si il y en a en plus par rapport aux exclude_fields[] dans sugarcrm.ini
#exclude_fields[]

*/ ?>
