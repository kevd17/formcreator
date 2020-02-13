<?php


include ('../../../inc/includes.php');

Html::header(
    __('Ticket Created', 'formcreator'),
    $_SERVER['PHP_SELF'],
    'helpdesk',
    'PluginFormcreatorFormlist'
 );

 echo Html::css('plugins/formcreator/css/fullform.css');
