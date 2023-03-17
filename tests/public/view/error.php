<?php

declare(strict_types=1);

ob_start();
throw new LogicException('test');
