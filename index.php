<? 
if (!ini_get('register_globals')) {
    $superglobals = array($_SERVER, $_ENV,
        $_FILES, $_COOKIE, $_POST, $_GET);
    if (isset($_SESSION)) {
        array_unshift($superglobals, $_SESSION);
    }
    foreach ($superglobals as $superglobal) {
        extract($superglobal, EXTR_SKIP);
    }
}
error_reporting(E_ALL);
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

//Get latest AWS deployment ID
global $deployment_id;
$deployment_id = rtrim(str_replace("-","",file_get_contents("deployment_id.txt")));

define('dir_prefix', "./");
require("engine/engine.php");
$js = [ 'js/shoplink.js?v='.$deployment_id ];
//$page_js[] = 'js/section.js';
switch($a) {
	case "login":	$content = doLogin();
			break;
			
	case "logout":	doLogout();
			break;
	//AMR Jun17: (i) New Help page (ii) New content for Help and Contact
	case "contact":	$content = displayContactPage();
			break;
	case "help":	$content = displayHelpPage();
			break;
	case "cookie-policy":	$content = displayCookiePolicy();
				break;
	case "privacy-statement":	$content = displayPrivacyStatement();
				break;
	case "ajaxSelect": processAjaxSelect();
			   die;
	case "rep_quick_order_search": quick_product_search($env, $quick_search);
			   die;
	case "rep_quick_order_create": quick_create_order($env, $quick_items);
			   die;
	case "processevntcode": processEventCode();
				header("location: index.php?a=choose");
				break;
    case "mixandmatch":
        if ( $ajax == "1") {
            if($pid) {
                processAjaxMixAndMatch($pid, $p);
            }

            die;
            break;
        }
        break;
	case "choose": 	
			if(!$ui['foodservice'])
			{
				unset ( $env );
				unset ( $ui['env'] );
				//$login = true;
				break;
			}
			else
			{
				header("location: index.php?a=landing&foodservice=1");
				break;
			}
	case "cart": 	if ( $ajax == "1" && !$ui)
			{
			  $str = <<<RECORD
LOGGED OUT
RECORD;
			  echo $str;
			  die;
			}
			break;

}

//die($content);
global $db, $cookie_session, $js, $page_js, $modal, $ent, $onload, $msg, $foodservice;

if(!$ui['RepCode']) 
{
	$ent['isHhtModal1'] = '';
	$ent['isHhtModal2'] = '';
}
else
{
	$ent['HideHHT'] = '1';
	$ent['isHhtModal1'] = '<!--';
	$ent['isHhtModal2'] = '-->';
	$ent['showQuickOrder'] = '1';

	if(!$ui['reasons']) {
		populateRepGlobalReasonCodes();
	}
}
$onload = '';
if ( $msg == 'favadd' )
{
  $onload = <<<RECORD
<script>
toastr["success"]("Added an item to your favourites list!", "Added to favourites!");
</script>
RECORD;
}
elseif ( $msg == 'favdel' )
{
  $onload = <<<RECORD
<script>
toastr["info"]("Removed an item from your favourites list!", "Removed from favourites!");
</script>
RECORD;
}

//Check/Update last viewed AWS deployment ID for user
$userVersion = getLastViewedVersionForUser($ui['UserID']);

if((empty($_REQUEST['newversion']) == false && $_REQUEST['newversion'] == 1) || $userVersion == '0')
{
	setLastViewedVersionForUser($ui['UserID'], $deployment_id);
}

if($ui['UserType']==1) 
{
	$content = displayGathererHome();
	$top_contents = displayGathererTop();
}
else if($ui['UserType']==2) 
{
	$content = displayManagerHome();
	$top_contents = displayManagerTop();
}
else if($ui['UserType']==3) 
{
	$content = displayStoreUserHome();
	$top_contents = displayStoreUserTop();
}
else if($ui['UserType']==4 || $ui['UserType']==5) 
{
	$mainClass = ' clear';
	if ( $ui['env'] == '' && $env == '' && $a != 'contact' && $a != 'help' && $a != 'cookie-policy' && $a != 'privacy-statement' && !($ui['UserType']==5))
	{
	  $a = "choose";
	  $mainClass = ' v-center';
	  $datapage = 'landing';
	}
	elseif ( $_GET['env'] != '' )
	{
	  $ses = sessionByID($db, $cookie_session);
	  $ses->addeditParam($db, 'env', $env);
	  $ui['env'] = $env;
	  $bwgCode = getBWGStoreCodeForUser ( $ui['UserID'] );
	  $depotSql = "select d.depot_id, dc.depot_customer_code, d.table_suffix from Depots d inner join DepotCustomers dc on d.depot_id = dc.depot_id where dc.bwg_customer_code = '" . $bwgCode . "' and d.table_suffix = '" . $env . "'";
	  if ( $debug > 0 ) echo "<br />" . $depotSql . "<br />";
	  $res = $db->getData($depotSql);
	  if ( count ( $res ) > 0 )
	  {
	    $ses->addeditParam ( $db, 'depot_id', $res[0]['depot_id'] );
	    $ses->addeditParam ( $db, 'depot_store_code', $res[0]['depot_customer_code'] );
	    $ses->addeditParam ( $db, 'table_suffix', $res[0]['table_suffix'] );
	    if (empty ( $ui['depot_id'] ) )
	    {
	      $ui['depot_id'] = $res[0]['depot_id'];
	      $ui['depot_store_code'] = $res[0]['depot_customer_code'];
	      $ui['table_suffix'] = $res[0]['table_suffix'];
	    }
	  }
	}
	elseif ( $ui['env'] != '' && $env == '' ) 
	{
	  $env = $ui['env'];
	}
	
	if ($a == "splash" || $a == "landing")
	{
		//$top_contents = displaySplashTop( $env );
		$ses = sessionByID($db, $cookie_session);
		$ses->addeditParam($db, 'env', $env);
		$ses->addeditParam($db, 'foodservice', $foodservice);
		$ui['env'] = $env;
		$ui['foodservice'] = $foodservice;
		$bwgCode = getBWGStoreCodeForUser ( $ui['UserID'] );
		//pflynnx - second account changes
		//$depotSql = "select d.depot_id, dc.depot_customer_code, d.table_suffix from Depots d inner join DepotCustomers dc on d.depot_id = dc.depot_id where dc.bwg_customer_code = '" . $bwgCode . "' and d.table_suffix = '" . $env . "'";
		if ($dc != '')
		{
			$depotSql = "select d.depot_id, dc.depot_customer_code, d.table_suffix, d.depot_group from Depots d inner join DepotCustomers dc on d.depot_id = dc.depot_id where dc.depot_customer_code = '" . $dc . "' and d.table_suffix = '" . $env . "'";
			
		}
		else
		{	
			if($ui['foodservice'])
			{
				$depotSql = "select d.depot_id, dc.depot_customer_code, d.table_suffix, d.depot_group from Depots d inner join DepotCustomers dc on d.depot_id = dc.depot_id where dc.bwg_customer_code in ( 'X".$bwgCode."','".$bwgCode."' ) and d.table_suffix = '" .$env . "'";
			}
			else
			{
				$depotSql = "select d.depot_id, dc.depot_customer_code, d.table_suffix, d.depot_group from Depots d inner join DepotCustomers dc on d.depot_id = dc.depot_id where dc.bwg_customer_code = '" . $bwgCode . "' and d.table_suffix = '" . ($env == "WOW" ? "Ambient" : $env) . "'";
			
			}
		}
		
		
		// pflynn end changes
				
		if ( $debug > 0 ) echo "<br />" . $depotSql . "<br />";
		$res = $db->getData($depotSql);
		if ( count ( $res ) > 0 )
		{
		  $ses->addeditParam ( $db, 'depot_id', $res[0]['depot_id'] );
		  $ses->addeditParam ( $db, 'depot_store_code', $res[0]['depot_customer_code'] );
		  $ses->addeditParam ( $db, 'table_suffix', $res[0]['table_suffix'] );
		  if (empty ( $ui['depot_id'] ) )
		  {
		    $ui['depot_id'] = $res[0]['depot_id'];
		    $ui['depot_group'] = $res[0]['depot_group'];
		    $ui['depot_store_code'] = $res[0]['depot_customer_code'];
		    $ui['table_suffix'] = $res[0]['table_suffix'];
		  }
		}
	}
	elseif ( $a == "choose" )
	{
		//$top_contents = displayChooseTop();
	}
	else
	{
		//$top_contents = displayStoreManagerTop( $env );
	}
	if ( $a != 'contact' && $a != 'help' && $a != 'cookie-policy' && $a != 'privacy-statement' && $a != 'login' ) $content = displayStoreManagerHome( $env );
	switch ( $a ) {
	  case "":	$datapage = 'section';
			break;
	  case "planogram":	if ( $a1 == "view" )
				{
				  $datapage = 'section';
				  $js[] = 'vendor/mp/magnific.popup.min.js';
				  $css[] = 'vendor/mp/magnific.popup.css';
				}
				else
				{
				  $datapage = 'planogram';
				}
				break;
	  case "cart":		$datapage = 'cart';
				break;
	  case "fav":		$datapage = 'favorites';
				break;
	  case "orders":	$datapage = 'past-orders';
				break;
	  case "messages":	$datapage = 'hht-orders';
				break;
	  case "advanced":	$datapage = 'search';
				break;
	  case "login":
						$content = doLogin();
						break;
	  default:		if ( $datapage == "" ) $datapage = 'section';
	  
	}
}

//prepare link for eAssist
$username = $ui['Username'];
$secret = 'EFdsk45GLkR33Ui98fdGLKdT';//EFdsk45GLkR33UI98fdGLKdT
$username_enc = urlencode(base64_encode($username));
$date = date('YmdH');
$ip = $_SERVER['REMOTE_ADDR'];
if ( $ip == "178.250.113.164" )
{
  //$ip = "10.0.10.*";
  $ip = $_SERVER['HTTP_CLIENT'];
}
$conc = $username.$date.$ip.$secret;
$hash = md5($conc);
$helplink = "https://eassist.londis.ie/eService/eServiceHomePage.aspx?u=".$username_enc."&h=".$hash;

//echo($content);
error_reporting(E_ERROR);

?>
<?
  if ( empty ($env) ) 
  {
    $login = true;
    $mainClass = ' v-center';
  }
  else 
  {
    $login = false;
    $mainClass = ' clear';
  }
  if ( $a == 'contact' || $a == 'help' || $a == 'cookie-policy' || $a == 'privacy-statement') $mainClass = ' clear';
?>
<?php if($a1 != "modal") include('parts/header.php') ?>
<?php if($a1 != "modal") include('parts/nav.php') ?>

<?php if($a == "landing") { ?>
<div class="wrap-landing">
<?php } else { ?>
<div class="flexWrap<? echo $mainClass ?>">
<?php } ?>


<? if($ui) { ?>
	<script>
<!--
function doLogout() {
   if(<?=$ui['pendingOrders']?><1) document.location="./?a=logout";
   else if(confirm("You have <?=$ui['pendingOrders']?> order(s) pending.\nHit OK if you still wish to log out.")) document.location="./?a=logout";
}

function openOverlay()
{
  document.getElementById('intel_overlay').style.display='block';
  document.getElementById('fade').style.display='block'; 
  scroll(0,0);
}

function closeOverlay()
{
  document.getElementById('intel_overlay').style.display='none';
  document.getElementById('fade').style.display='none'; 
}

function showLegend()
{
  var leg = document.getElementById('icon_legend');
  leg.style.display = "block";
}

function hideLegend()
{
  var leg = document.getElementById('icon_legend');
  leg.style.display = "none";
}
//-->
</script>
<? } ?>
<?
if ( $env <> "" )
{
  $envName = getEnvName ( $env );
  $envHomeLink = " | <a href=\"/?a=splash\" class=\"main_menu_item\">" . strtoupper ( $envName ) . " HOME</a>";
}
else 
{
  $envHomeLink = "";  
}
function getEnvName ( $env )
{
  global $db;
  $res = $db->getCachedData("select environment from Depots where table_suffix = '" . $env . "'");
  return $res[0][0];
}
?>
		<? if($a!="login" && ( empty($env) || $env == '' ) && $a != 'contact' && $a != 'help'  && $a != 'cookie-policy' && $a != 'privacy-statement' ) echo displayUserInfo()?>
		<? echo($content); ?>

</div>
<? echo $modal ?>
<div class="modal fade" tabindex="-1" role="dialog" id="depotLoadProgress"
	 style="display: none; position: fixed; z-index: 900000;
		  left: 0; top: 0; width: 100%; height: 100%;">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">LOADING YOUR PRODUCT CATALOGUE... PLEASE WAIT</h4>
			</div>
			<div class="modal-body">
				<p>
					Please do not close or refresh this page
				</p>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="HHTProgress"
	 style="display: none; position: fixed; z-index: 900000;
		  left: 0; top: 0; width: 100%; height: 100%;">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">HHT Orders</h4>
			</div>
			<div class="modal-body">
				<p>
					Checking for HHT files... Please wait.
				</p>
				<p>
					Please do not close or refresh this page.
				</p>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="OfferHhtCheck" style="display: none;">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header bg-default">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>×</span></button>
				<h4 class="modal-title">HHT Orders</h4>
			</div>
			<div class="modal-body">
				<p>
					Would you like to check for HHT orders?<br/><br/>
					If you have recently uploaded a file, please allow 90 seconds for the file to become available.
				</p>
			</div>
			<div class="modal-footer">
				<button type="button" onclick="$('#OfferHhtCheck').hide(); show_hht_orders();" class="btn btn-outline btn-danger">Yes</button>
				<button type="button" class="btn btn-outline btn-danger" data-dismiss="modal">No</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="ReleaseNotification" style="display: none;">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header bg-default">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>×</span></button>
				<h4 class="modal-title">New Version</h4>
			</div>
			<div class="modal-body">
				<p>
					<b>Shoplink has been updated since your last visit!</b><br/><br/>
					Please clear your browser cache to see all the benefits of this upgrade.
				</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-outline btn-danger" data-dismiss="modal">Ok</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="SubsProduct" style="display: none;">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header bg-default">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>×</span></button>
				<h4 class="modal-title">Substitute on Favourites</h4>
			</div>
			<div class="modal-body">
				<p>
					Would you like to replace the item or would you like to keep the old item in your favourite list and add a new one?
				</p>
			</div>
			<div class="modal-footer">
				<!--<button type="button" onclick="$('#SubsProduct').hide();" class="btn btn-outline btn-danger">Replace</button>-->
				<!--<button type="button" class="btn btn-outline btn-danger" data-dismiss="modal">Keep old Item</button>-->
				<a class="btn btn-outline btn-danger" data-dismiss="modal" onclick="$('#SubsProduct').hide(); window.location.href=this.href;" href="/?a=fav&a1=doAdd&Code=<?=$Code?>&pid=<?=$Code?>&Name=<?=$Name?>&subsid=<?=$subsid?>&referer=<?=$referer?>">Replace</a>
				<a class="btn btn-outline btn-danger" data-dismiss="modal" onclick="$('#SubsProduct').hide(); window.location.href=this.href;" href="/?a=fav&a1=doAdd&Code=<?=$Code?>&pid=<?=$Code?>&Name=<?=$Name?>&referer=<?=$referer?>">Keep old Item</a>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div>
<?php if($a1 != "modal") include('parts/footer.php');
$db->close();
//var_dump($_GET);
// echo "%%%" . $_GET['radoo'];
if (isset($radoo)){
    echo "<pre>";
    echo $mylog ? $mylog : "no log";
    echo "</pre>";
}
?>