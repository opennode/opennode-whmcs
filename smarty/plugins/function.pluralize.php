
<?php
/**
 * Smarty plugin
 * @package pLog
 * @subpackage Smarty
 * @subsubpackage plugins
 */

/**
 * Smarty {pluralize} function plugin
 *
 * Type:     function<br>
 * Name:     pluralize<br>
 * Date:     April 22, 2004<br>
 * Purpose:  pluralize text according to given numeric value<br>
 * Input:
 *         - number = required numeric value (required)
 *         - sing = value used for singular interpretation (required)
 *         - plur = value used for plural interpretation (optional)
 *                  if not set 'sing' value is used for plural
 *         - plur2 = value used for plural interpretation (optional)
 *                   for example in Czech language there are two plurals
 *                   there is: 1 pivo (beer)
 *                             2,3,4 piva (beers)
 *                             5-20 piv (beers)
 *                             21 pivo (beer)
 *                             22,23,24 piva (beers)
 *                             25-30 piv (beers)
 *                             31 pivo (beer)
 *                             32,33,34 piva (beers)
 *                             ...
 *                             ...
 *                             ...
 *                             100 piv (beers)
 *                             101 pivo (beer)
 *                             102,103,104 piva (beers)
 *                             105-120 piv (beers)
 *                             121 pivo (beer)
 *                             ...
 *                             ...
 *                             ...
 *                   This "formula" is used in literary language, everyday
 *                   language uses another... :-)
 *
 *                   If parameter 'plur2' is not set, 'plur' or 'sing' is
 *                   used instead.
 *
 * Examples:<br>
 * <div>
 *   {pluralize number=3 sing="beer" plur="beers"}
 *   <!-- returns beers -->
 *
 *   {pluralize number=5 sing="comment" plur="comments"}
 *   <!-- returns comments -->
 *
 *   {pluralize number=3 sing="pivo" plur="piva" plur2="piv"}
 *   <!-- returns piva -->
 *
 *   {pluralize number=13 sing="pivo" plur="piva" plur2="piv"}
 *   <!-- returns piv -->
 * </div>
 * @author Martin Soucek <http://www.soucek.org/>
 * @version  1.0
 * @param array
 * @param Smarty
 * @return string|null
 */
function smarty_function_pluralize($params, &$smarty)
{
  if (!in_array('number', array_keys($params))) {
    //generates error message - if you do not want it - comment following line
    $smarty->trigger_error("pluralize: missing 'number' parameter");
    return;
  }
  else {
    if (is_numeric($number = $params['number'])) {
      $number = floor($number);
/* ************************************************************************** */
      if (!in_array('sing', array_keys($params))) {
        //generates error message - if you do not want it - comment this line
        $smarty->trigger_error("pluralize: missing 'sing' parameter");
        return;
      }
      else {
        $sing = $params['sing'];
/* ************************************************************************** */
        if (!in_array('plur', array_keys($params))) {
          $plur = $params['sing'];
        }
        else {
          $plur = $params['plur'];
        }

        if (!in_array('plur2', array_keys($params))) {
          $plur2 = $plur;
        }
        else {
          $plur2 = $params['plur2'];
        }
/* ************************************************************************** */
      }
/* ************************************************************************** */
    }
    else {
      //generates error message - if you do not want it - comment following line
      $smarty->trigger_error("pluralize: 'number' parameter must be numeric");
      return;
    }
  }

  if (($number % 10) == 0) {
    //for " zero ";
    $output = $plur2;
  }
  elseif ((($number % 100)) >= 10 and (($number % 100) < 20)) {
    //for " 10-19 ";
    $output = $plur2;
  }
  elseif (($number % 10) == 1) {
    //for " 1 ";
    $output = $sing;
  }
  elseif ((($number % 10) >= 2) and (($number % 10) <= 4)) {
    //for " 2,3,4 ";
    $output = $plur;
  }
  else {
    //for " anything else ";
    $output = $plur2;
  }
  return $output;
}
?>

