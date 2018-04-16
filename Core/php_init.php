<?php
namespace Core;
/**
 * [Isaac] Since some host seems not to have correctly based configuration servers
 * I have to create this (optional) file, where you can Set (overriding) the default
 * PHP INI Host based cuz, sometimes it is hidden from yourself either (wierd policy Host)
 * Anyways, if you can manipulate the host PHP_INI isn't necesary this file.
 * Note: If host doesn't let you tu use "ini_set" command, you must need to talk with host
 * to active this parameters manually.
 * 
 * If all is ok this module should be commented from controller.php
 * 
 * How To activate some parameter:
 * - delete the "//" (uncomment)
 * */
//phpinfo();

if (version_compare(PHP_VERSION, '5.6.0', '<')) {
    echo
    '
		Oboro Control Panel (C) requiere at least PHP 5.6.0 or Upper, your version: ' . PHP_VERSION . '<br/>
		Modules will not work correctly with this version <br/>
		Anyways you normaly can update your PHP version in the web control panel admin(CPANEL or similar) <br/>
		<b>Note:</b>  Sometimes it changes to default automatically (Host Based Policy)<br/>
		<br/>
		---------------------------------------------------------------------------------------------------------------
		<br/><br/>
		Oboro Control Panel (C) Requiere al menos PHP 5.6.0 o superior, su versi&oacute;n es: ' . PHP_VERSION . '<br/>
		Algunos m&oacute;dulos no trabajaran correctamente con esta versi&oacute;n<br/>
		Usted puede normalmente cambiar la versi&oacute;n del PHP en la web control panel administrador (CPANEL o similar)<br/>
		<b>Nota:</b>  Algunas veces puede cambiarse a default autom&aacute;ticamente (Pol&iacute;ticas de Host)
	';
    exit;
}

if (!extension_loaded('pdo_mysql')) {
    echo 'php_pdo_mysql.dll missing, please active or install the lib in PHP.ini, after instalation or activation will be needed a Apache Restart';
    exit;
}

if (ini_get('file_uploads') != 1) {
    echo 'HTTP Upload is set Disabled in PHP.ini, you need to put file_uploads = On, or tell the host to do it in order to use Oboro Control Panel';
    exit;
}

// Ambos deben de estar configurados para trabajar correctamente
ini_set('error_reporting', E_ALL);    //E_ALL = Modo Mantenimiento	| 0 = no muestra error 
ini_set("display_errors", 1);    // 1 = Modo Mantenimiento 		| 0 = no muestra error
//ini_set('memory_limit', '-1'); 			//Necesary for item_db allocation to Cache System
//ini_set('upload_max_filesize', '16G'); 	// ??
//ini_set('post_max_size', '16G'); 			// ???