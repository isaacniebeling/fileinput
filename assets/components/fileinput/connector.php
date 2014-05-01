<?php
/**
 * file input for ContentBlocks
 *
 * based on work by Mark Hamstra <support@modmore.com>
 *
 * @package fileinput
 * @var modX $modx
 */

require_once dirname(dirname(dirname(dirname(__FILE__)))).'/config.core.php';
require_once MODX_CORE_PATH.'config/'.MODX_CONFIG_KEY.'.inc.php';
require_once MODX_CONNECTORS_PATH . 'index.php';

$cbPath = $modx->getOption('contentblocks.core_path',null,$modx->getOption('core_path').'components/contentblocks/');
require_once $cbPath.'model/contentblocks/contentblocks.class.php';
$modx->contentblocks = new ContentBlocks($modx);

$corePath = $modx->getOption('fileinput.core_path',null,$modx->getOption('core_path').'components/fileinput/');
$path = $corePath . 'processors/';
$modx->request->handleRequest(array(
    'processors_path' => $path,
    'location' => '',
));
