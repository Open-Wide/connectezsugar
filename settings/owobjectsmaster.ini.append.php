<?php /* 

[Users]
# ID de l'Admin User
AdminID=14


[Tree]
# ID du node parent par default pour la création des objets
#DefaultParentNodeID=1198
DefaultParentNodeID=2
# ID de la section par default pour la création des objets
DefaultSectionID=1

# ID du node parent pour la création des objets d'une class specifique
ClassParentNodeID[]
ClassParentNodeID[room]=2
ClassParentNodeID[accommodation]=2
ClassParentNodeID[restaurant]=2


[Class]
# Group de Class
DefaultClassGroup=Content

ClassGroup[]
ClassGroup[room]=Content
ClassGroup[accommodation]=Content

# Class Objects is_container?
DefaultClassIsContainer=1

ClassIsContainer[room]=1
ClassIsContainer[accommodation]=1


# options relatives à la traduction et au multilangues
[Translation]
# valeur par default de 'can_translate' des attributes des classes EZ
# valeurs possibles : 0 ou 1
DefaultCanTranslate=0


# options relatives à la recherche
[Search]
# valeur par default de 'is_searchable' des attributes des classes EZ
# valeurs possibles : 0 ou 1
DefaultIsSearchable=1

*/ ?>