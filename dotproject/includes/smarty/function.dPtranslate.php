<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/*
 * Smarty {translate word=word} function plugin
 *
 * Type:     function<br>
 * Name:     translate<br>
 * Purpose:  translate words through dp<br>
 *
 * @param array Format: array('var' => variable name, 'value' => value to assign)
 * @param Smarty
 */
function smarty_function_dPtranslate($params, &$smarty)
{
	global $AppUI;
	
    extract($params);

    if (empty($word) && empty($sentence)) {
        $smarty->trigger_error("dPtranslate: missing parameter");
        return;
    }
    if ($type == 'js')
    	return $AppUI->_($word . $sentence, UI_OUTPUT_JS);

    return $AppUI->_($word . $sentence);
}

/* vim: set expandtab: */

?>