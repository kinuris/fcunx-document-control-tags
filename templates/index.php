<?php

declare(strict_types=1);

use OCP\Util;

Util::addScript(OCA\DocumentControlTags\AppInfo\Application::APP_ID, OCA\DocumentControlTags\AppInfo\Application::APP_ID . '-main');
Util::addStyle(OCA\DocumentControlTags\AppInfo\Application::APP_ID, OCA\DocumentControlTags\AppInfo\Application::APP_ID . '-main');

?>

<div id="documentcontroltags"></div>
