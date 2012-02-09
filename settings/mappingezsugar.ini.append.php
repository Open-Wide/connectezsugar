<?php /*

# chaque bloc correspond à une class EZ
# identifiant de la class
# 2 tableau par bloc : 
# sugarez[] : correspondences sugar => EZ ( ex.: sugarez[field_name_sugar]=attribut_identifier_ez )
# ezsugar[] : correspondences EZ => sugar ( ex.: sugarez[attribut_identifier_ez]=field_name_sugar )

[hotel]
sugarez[]
sugarez[name]=name
sugarez[description]=description_changed
sugarez[deleted]=deleted

# correspondences EZ => sugar
ezsugar[]
ezsugar[name]=name
ezsugar[description_changed]=description
ezsugar[deleted]=deleted

# champs de la table SUGAR (field name) à ignorer pour cet objet EZ
# si il y en a en plus par rapport aux exclude_fields[] dans sugarcrm.ini
exclude_fields[]

*/ ?>
