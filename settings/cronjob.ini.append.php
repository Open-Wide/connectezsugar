<?php /* #?ini charset="utf-8"?

[CronjobSettings]
ExtensionDirectories[]=connectezsugar
#Scripts[]=synchronize_ezsugar.php
#Scripts[]=synchronize_module.php
#Scripts[]=cleanup_dev.php
#Scripts[]=cleanup_module.php
#Scripts[]=synchronize_relations.php
#Scripts[]=synchro_relations_module.php
#Scripts[]=update_classes.php
#Scripts[]=export.php
#Scripts[]=export_module.php
#Scripts[]=import.php
#Scripts[]=import_module.php


[CronjobPart-synchro]
${SugarConnexion.CronjobPart-synchro}

[CronjobPart-synchrosugartoez]
Scripts[]=synchronize_ezsugar.php
#Scripts[]=synchronize_relations.php

[CronjobPart-synchromodule]
Scripts[]=synchronize_module.php

[CronjobPart-importrelations]
Scripts[]=synchronize_relations.php

[CronjobPart-importrelationsmodule]
Scripts[]=synchro_relations_module.php

[CronjobPart-cleanup]
Scripts[]=cleanup_dev.php

[CronjobPart-cleanupmodule]
Scripts[]=cleanup_module.php

[CronjobPart-upclasses]
Scripts[]=update_classes.php

[CronjobPart-export]
Scripts[]=export.php

[CronjobPart-exportmodule]
Scripts[]=export_module.php

[CronjobPart-import]
Scripts[]=import.php

[CronjobPart-importmodule]
Scripts[]=import_module.php


# php runcronjobs.php synchro
# php runcronjobs.php importrelations
# php runcronjobs.php importrelationsmodule [otcp_xxx]
# php runcronjobs.php export
# php runcronjobs.php exportmodule [otcp_xxx]
# php runcronjobs.php import
# php runcronjobs.php importmodule [otcp_xxx]

# php runcronjobs.php synchroezsugar
# php runcronjobs.php synchromodule [otcp_xxx]

# php runcronjobs.php cleanup
# php runcronjobs.php cleanupmodule [otcp_xxx]
# php runcronjobs.php upclasses


*/ ?>
