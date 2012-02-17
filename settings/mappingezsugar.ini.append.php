<?php /*

# chaque bloc correspond à un module SUGAR
# [module_name]
#
# 4 tableaux possibles par bloc :
#
# 1) sugarez[] : correspondences SUGAR => EZ pour les cas ou les identifiants des attributes EZ ne correspondent pas aux fields_names SUGAR
# ( ex.: sugarez[field_name_sugar]=attribut_identifier_ez )
#
# 2) ezsugar_rename[] : correspondences EZ => SUGAR dans le cas d'un update d'une class EZ pour pouvoir renommer les identifiants des attributes
# ( ex.: ezsugar[attribut_identifier_ez]=field_name_sugar )
#
# 3) exclude_fields[] : champs de la table SUGAR (field name) à ignorer pour ce module SUGAR
#
#4) include_fields[] : champs de la table SUGAR (field name) à synchroniser avec EZ pour ce module SUGAR

[test_Hotel]
#sugarez[]
#sugarez[name]=name
#sugarez[description]=description
#sugarez[deleted]=deleted

#ezsugar_rename[]
#ezsugar_rename[name]=name
#ezsugar_rename[description]=description
#ezsugar_rename[deleted]=deleted

# @TODO voir comment faire un merge de exclude_fields generic et le specifique par module
# champs de la table SUGAR (field name) à ignorer pour cet module SUGAR
# si il y en a en plus par rapport aux exclude_fields[] dans sugarcrm.ini
#exclude_fields[]

include_fields[]
include_fields[]=name
include_fields[]=description
include_fields[]=deleted

*/ ?>
