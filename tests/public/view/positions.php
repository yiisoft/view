<?php

declare(strict_types=1);

/**
 * @var $this \Yiisoft\View\WebView
 */

echo '[BEGINPAGE]';
$this->beginPage();
echo '[/BEGINPAGE]';

echo "\n";

echo '[HEAD]';
$this->head();
echo '[/HEAD]';

echo "\n";

echo '[BEGINBODY]';
$this->beginBody();
echo '[/BEGINBODY]';

echo "\n";

echo '[ENDBODY]';
$this->endBody();
echo '[/ENDBODY]';

echo "\n";

echo '[ENDPAGE]';
$this->endPage();
echo '[/ENDPAGE]';
