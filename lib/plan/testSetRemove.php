<?php
/** 
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * @version $Id: testSetRemove.php,v 1.17 2006/11/02 10:07:37 franciscom Exp $ 
 * 
 * Remove Test Cases from Test Case Suite 
 * 
 */         
require('../../config.inc.php');
require_once("../functions/common.php");
require_once("plan.inc.php");

testlinkInitPage($db);

$tree_mgr = new tree($db); 
$tsuite_mgr = new testsuite($db); 
$tplan_mgr = new testplan($db); 
$tcase_mgr = new testcase($db); 
$tplan_id = $_SESSION['testPlanId'];
$tplan_name = $_SESSION['testPlanName'];

// 20061029 - franciscom
$tproject_id =  $_SESSION['testprojectID'];
$tproject_name =  $_SESSION['testprojectName'];


$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
$version_id = isset($_REQUEST['version_id']) ? $_REQUEST['version_id'] : 0;
$level = isset($_REQUEST['level']) ? $_REQUEST['level'] : null;
$keyword_id = isset($_REQUEST['keyword_id']) ? $_REQUEST['keyword_id'] : 0;
$do_remove = isset($_POST['remove_tc']) ? 1 : 0;

$resultString = null;
$arrData = array();

// ---------------------------------------------------------------------------------------
if($do_remove)
{
  $a_tc = isset($_POST['achecked_tc']) ? $_POST['achecked_tc'] : null;
  if(!is_null($a_tc))
  {
      // remove without warning
      $tplan_mgr->unlink_tcversions($tplan_id,$a_tc);   
  }  
}

define('FILTER_BY_TC_OFF',null); 
define('WRITE_BUTTON_ONLY_IF_LINKED',1);
$dummy = null;
$out = null;


$map_node_tccount = get_testplan_nodes_testcount($db,$tproject_id,$tproject_name,
                                                     $tplan_id,$tplan_name,$keyword_id);
$total_tccount=0;
foreach($map_node_tccount as $elem)
{
  $total_tccount +=$elem['testcount'];
}		

switch($level)
{
	case 'testcase':
		
		if( $total_tccount > 0 && !$do_remove)
		{
  		// build data needed to call gen_spec_view
	  	$my_path = $tree_mgr->get_path($id);
		  $idx_ts = count($my_path)-1;
		  $tsuite_data= $my_path[$idx_ts-1];
		
		  $pp = $tcase_mgr->get_versions_status_quo($id, $version_id, $tplan_id);
		  $linked_items[$id] = $pp[$version_id];
		  $linked_items[$id]['testsuite_id'] = $tsuite_data['id'];
		  $linked_items[$id]['tc_id'] = $id;

  		$out = gen_spec_view($db,'testplan',$tplan_id,$tsuite_data['id'],$tsuite_data['name'],
	  			                 $linked_items,$map_node_tccount,$keyword_id,
	  			                 FILTER_BY_TC_OFF,WRITE_BUTTON_ONLY_IF_LINKED);
		}
		break;
		
	case 'testsuite':
		if( $total_tccount > 0 )
		{
  		$tsuite_data = $tsuite_mgr->get_by_id($id);

	  	$out = gen_spec_view($db,'testplan',$tplan_id,$id,$tsuite_data['name'],
                           $tplan_mgr->get_linked_tcversions($tplan_id,FILTER_BY_TC_OFF,$keyword_id),
                           $map_node_tccount,
                           $keyword_id,FILTER_BY_TC_OFF,WRITE_BUTTON_ONLY_IF_LINKED);
    }                       
		break;
		
	default:
		// show instructions
		redirect( $_SESSION['basehref'] . $g_rpath['instructions'].'/testSetRemove.html');
	break;
}


$smarty = new TLSmarty();
$smarty->assign('has_tc', 0);
$smarty->assign('arrData',null);

if( !is_null($out) )
{
  $smarty->assign('has_tc', ($out['num_tc'] > 0 ? 1:0));
  $smarty->assign('arrData', $out['spec_view']);
}

$smarty->assign('testPlanName', $tplan_name);
$smarty->assign('refreshTree', $do_remove ? 1 : 0);

$smarty->display('planRemoveTC_m1.tpl');
?>