<?php
Phar::interceptFileFuncs();

require __DIR__ . '/' . __AUTOLOAD__;
require __DIR__ . '/' . __STUB__;
__HALT_COMPILER();
