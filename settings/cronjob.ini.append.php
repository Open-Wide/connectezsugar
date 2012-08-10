<?php /* #?ini charset="utf-8"?


[CronjobSettings]
ExtensionDirectories[]=connectezsugar
Scripts[]=synchronize_ezsugar.php
Scripts[]=synchronize_module.php
Scripts[]=cleanup_dev.php
Scripts[]=cleanup_module.php
Scripts[]=synchronize_relations.php
Scripts[]=synchro_relations_module.php
Scripts[]=update_classes.php
# scripts de test
#Scripts[]=test_mere.php
#Scripts[]=test_fils.php


[CronjobPart-synchro]
Scripts[]=synchronize_ezsugar.php
#Scripts[]=synchronize_relations.php

[CronjobPart-synchromodule]
Scripts[]=synchronize_module.php

[CronjobPart-synchrorelations]
Scripts[]=synchronize_relations.php

[CronjobPart-synchrorelationsmodule]
Scripts[]=synchro_relations_module.php

[CronjobPart-cleanup]
Scripts[]=cleanup_dev.php

[CronjobPart-cleanupmodule]
Scripts[]=cleanup_module.php

[CronjobPart-upclasses]
Scripts[]=update_classes.php

[CronjobPart-export]
Scripts[]=export.php

# SCRIPTS DE TEST
#[CronjobPart-mere]
#Scripts[]=test_mere.php
#[CronjobPart-fils]
#Scripts[]=test_fils.php


# php runcronjobs.php synchro
# php runcronjobs.php synchromodule
# php runcronjobs.php synchrorelations
# php runcronjobs.php synchrorelationsmodule
# php runcronjobs.php cleanup
# php runcronjobs.php cleanupmodule
# php runcronjobs.php upclasses


*/ ?>
