<?php /*

# chaque bloc correspond à un module SUGAR
# [module_name]
#
# Tableaux possibles par bloc :
#
# 1) sugarez[] : correspondences SUGAR => EZ pour les cas ou les identifiants des attributes EZ ne correspondent pas aux fields_names SUGAR
# ( ex.: sugarez[field_name_sugar]=attribut_identifier_ez )
#
# 2) ezsugar_rename[] : correspondences EZ => SUGAR dans le cas d'un update d'une class EZ pour pouvoir renommer les identifiants des attributes
# ( ex.: ezsugar[attribut_identifier_ez]=field_name_sugar )
#
# 3) exclude_fields[] : champs de la table SUGAR (field name) à ignorer pour ce module SUGAR
#
# 4) include_fields[] : champs de la table SUGAR (field name) à synchroniser avec EZ pour ce module SUGAR
#
# !IMPORTANT! si les deux tableaux exclude_fields[] et include_fields[] sont definis
# include_fields[] est pris en compte et exclude_fields[] ignoré
#
# 5) translate_fields[] : champs de la table SUGAR (field name) pour lequel on decide qui auront la traduction activée ou pas côté eZ
# les valeurs possible sont 1(oui) ou 0(non)
# si cette liste est vide la valeur par default pour tous les attributs est retenu

[test_Hotel]
#sugarez[]
#sugarez[name]=name
#sugarez[description]=description
#sugarez[deleted]=deleted

#ezsugar_rename[]
#ezsugar_rename[name]=name
#ezsugar_rename[description]=description
#ezsugar_rename[deleted]=deleted

#exclude_fields[]

include_fields[]
include_fields[]=name
include_fields[]=description
include_fields[]=deleted

translate_fields[]
translate_fields[description]=1

*/ ?>
