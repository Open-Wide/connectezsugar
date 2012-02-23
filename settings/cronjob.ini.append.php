<?php /* #?ini charset="utf-8"?


[CronjobSettings]
ExtensionDirectories[]=connectezsugar
Scripts[]=synchronize_ezsugar.php
Scripts[]=cleanup_dev.php


[CronjobPart-synchro]
Scripts[]=synchronize_ezsugar.php

[CronjobPart-cleanup]
Scripts[]=cleanup_dev.php


# php runcronjobs.php synchro
# php runcronjobs.php cleanup

*/ ?>