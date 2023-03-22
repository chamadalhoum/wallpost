<?php
require_once '../init.php';
include 'Mobile_Detect.php';
$detect = new Mobile_Detect();
$publisiteId = 2783;
$defaultLang = "fr_FR";
$defaultLocale = "fr";
$filter = new Zend_Filter_Encrypt($options);
$filterd = new Zend_Filter_Decrypt($options);
$tool = new Custom_Tools_String();
$publisiteDir = bin2hex($filter->filter($publisiteId));
// define our application directory
define('PUBLISITE_DIR', ROOT_PATH.'/build/'.$publisiteDir.'/');
if(file_exists(PUBLISITE_DIR.'libs/fr/Zend_Validate.php')) {
	$translator = new Zend_Translate(
		array(
			'adapter' => 'array',
			'content' => PUBLISITE_DIR.'libs/',
			'locale'  => $defaultLocale,
			'scan' => Zend_Translate::LOCALE_DIRECTORY
		)
	);
} else {
	$translator = new Zend_Translate(
		array(
			'adapter' => 'array',
			'content' => ROOT_PATH.'/build/modules/default/libs/',
			'locale'  => $defaultLocale,
			'scan' => Zend_Translate::LOCALE_DIRECTORY
		)
	);
}
define('PUBLISITE_PATH', '/');
if(file_exists(PUBLISITE_DIR . 'libs/publisite.lib.php')) {
	require_once(PUBLISITE_DIR . 'libs/publisite.lib.php');
} else {
	require_once(ROOT_PATH.'/build/modules/default/libs/publisite.lib.php');
}
$dbPublisiteModel = new Default_Model_DbTable_Publisite();
$dbPageModel = new Default_Model_DbTable_Page();
$dbHoraireModel = new Default_Model_DbTable_Horaire();
$dbGmapsModel = new Default_Model_DbTable_Gmaps();
$ganalyticsModel = new Default_Model_DbTable_Ganalytics();
$reductionModel = new Default_Model_DbTable_Reduction();
$moduleModel = new Default_Model_DbTable_Module();
$oCache = Zend_Registry::get('memcache');
$view = new Zend_View();
$view->setScriptPath(ROOT_PATH.'/build/'.$publisiteDir.'/templates/');
$view->addHelperPath('Custom/View/Helper', 'Custom_View_Helper');
$helper = new Zend_View();
$helper->setScriptPath(ROOT_PATH.'/build/'.$publisiteDir.'/templates/');
$helper->addScriptPath(ROOT_PATH."/build/modules/default/");
$helper->addScriptPath(ROOT_PATH."/build/".$publisiteDir."/templates/");
$helper->addHelperPath('Custom/View/Helper', 'Custom_View_Helper');
// create publisite object
$publisite = new Publisite($dbPublisiteModel,$dbPageModel,$dbHoraireModel,$dbGmapsModel,$ganalyticsModel,$reductionModel, $moduleModel,$oCache,$view);
$publisite->setId($publisiteId);
$publisite->destination = ROOT_PATH.'/build/'.$publisiteDir.'/uploads';
$publisite->publisiteDir = $publisiteDir;
$publisite->encrypt = $filter;
$publisite->decrypt = $filterd;
// set the current action
$_action = (isset($_REQUEST['action']) && !empty($_REQUEST['action']) && ($_REQUEST['action']!='html') && ($_REQUEST['action']!='shtml')) ? $_REQUEST['action'] : 'view';
$array = array();
$array["menus"] = $publisite->getMenus();
$array["principalmenus"] = $publisite->getPrincipalMenus();
$array["actif"] = $_action;
$array["path"] = PUBLISITE_PATH . 'templates';
$array["root"] = PUBLISITE_PATH;
$contact = new stdClass();
$contact->action =$myconfig["conf"]["domain"]."/contact";
$contact->print =$myconfig["conf"]["domain"]."/print";
$contact->rappel =$myconfig["conf"]["domain"]."/rappel";
$contact->id =$publisiteDir;
$array["homepage"] = false;
$array["devisform"] = $contact;
$array["detectMobile"] = $detect;
$array["adresse"] = "";
$array["table_horaire"] = "";
$array["map_plan"] = "";
$array["publisite"] = $publisite->getPublisiteInfo();
$array["params"] = $publisite->getPublisiteParams();
$array['reduction'] = $publisite->getReduction();
$array['reductions'] = $publisite->getAllReductions();
$array['module'] = $publisite->getModule();
$array['posts'] = $publisite->getPosts();
$defaultJSCSS = new Zend_View();
$defaultJSCSS->setScriptPath(ROOT_PATH.'/build/'.$publisiteDir.'/templates/');
$defaultJSCSS->addScriptPath(ROOT_PATH.'/build/modules/default/');
$defaultJSCSS->addHelperPath('Custom/View/Helper', 'Custom_View_Helper');
$defaultJSCSS->assign('data', $array);
$defaultJSCSS->assign('mobile', $detect->isMobile());
$array['js'] = $defaultJSCSS->render("scripts.phtml");
$array['contactmapScripts'] = $defaultJSCSS->partial("script-contact-map-js.phtml", array("class"=>"contact","data"=>$array));
$array['indexmapScripts'] = $defaultJSCSS->partial("script-contact-map-js.phtml", array("class"=>"index","data"=>$array));
$array['css'] = $defaultJSCSS->render("links.phtml");
$array['css'] .= $defaultJSCSS->render("custom.phtml");

$array['impression'] = $defaultJSCSS->render("impression.phtml");


if($array['module']['module_agendas']==1) {
	$array["services"] = $publisite->getServices();
	$array["evenements"] = $publisite->getJsonEvents();
	$array["servicesIntervenant"] = $publisite->getServiceIntervenants();
	foreach($array["services"] as $service){
		$entrys = new stdClass();
		$entrys->id = $service["service_id"];
		$entrys->duration = $service["service_duree"];
		$entrys->start = $service["service_debut"];
		$entrys->stop = $service["service_fin"];
		$calendar = Custom_Tools_Calendar::init($entrys);
		$array["calendar"][$service["service_id"]] = $calendar;
	}
}
if($array['module']['module_newsletter']==1) {
	$array['newsletter'] = $defaultJSCSS->render("newsletter.phtml");
}
if($array['module']['module_multilingue']==1) {
	$array['lang'] = $publisite->getLangMenus();
	if(in_array($_action, $array['lang']['key']) || in_array($_action.'/', $array['lang']['key'])) {
		setcookie("Locale", $_action);
		if(isset($_COOKIE["Locale"]) && ($_COOKIE["Locale"]!=$_action)) {
			//echo "<script>window.location.href='/".$_action."';</script>";
			header("Location: /".$_action);
		}
		$_action = "view";
	} else {
		if(!isset($_COOKIE["Locale"]))
		setcookie("Locale", "fr");
	}
	$translate = new Zend_Translate('gettext', 
                    PUBLISITE_DIR . "langs/", 
                    null, 
                    array('scan' => Zend_Translate::LOCALE_DIRECTORY));
    $registry = Zend_Registry::getInstance();
    $registry->set('Zend_Translate', $translate);
	isset($_COOKIE["Locale"])? $translate->setLocale($_COOKIE["Locale"]):$translate->setLocale($defaultLocale);
	$defaultLang = isset($_COOKIE["Locale"])?  $array['lang']['locale'][$_COOKIE["Locale"]]["lang_nom"]:$array['lang']['locale'][$defaultLocale]["lang_nom"];
	$array["menus"] = $publisite->getLnMenus($defaultLang);
	$array["principalmenus"] = $publisite->getPrincipalMenus($defaultLang);
	if(isset($_COOKIE["Locale"]) && file_exists(ROOT_PATH.'/build/modules/default/libs/'.$_COOKIE["Locale"].'/Zend_Validate.php')) {
		$translator = new Zend_Translate(
			array(
				'adapter' => 'array',
				'content' => ROOT_PATH.'/build/modules/default/libs/'.$_COOKIE["Locale"].'/Zend_Validate.php',
				'locale'  => $_COOKIE["Locale"],
				'scan' => Zend_Translate::LOCALE_FILENAME
			)
		);
	}
}
if(file_exists(ROOT_PATH.'/build/'.$publisiteDir.'/templates/cardminisite.phtml')) {
	$array['card'] = $defaultJSCSS->render("cardminisite.phtml");
} else {
	$array['card'] = $defaultJSCSS->render("card.phtml");
}
$view->assign('helper', $helper);
$view->assign('publisiteId', $publisiteId);
$view->addScriptPath(ROOT_PATH.'/build/modules/cookies/');
$view->addScriptPath(ROOT_PATH.'/build/modules/default/');

$view->assign("dataStart", $array);
$array["cookies"] = $view->render( 'cookies.phtml' );
if($array['module']['module_blog']==1) {
$allblogs = $publisite->getBlogCategoriePosts();
$array["lastblog"] = $allblogs;
}
if($array['module']['module_avis']==1) {
	$aviscontent = $publisite->getAvisContent();
	$array["aviscontent"] = $aviscontent;
}
if ($detect->isMobile()) {
    // Any mobile device.
	$array['pages'] = $publisite->getPagesList();
	$view->assign('mobile', true);
	$formsMobile = array();
	$ar = array();
	foreach($publisite->getFormsMobile() as $fmId=>$fm) {
		$formsMobile[$fmId] = new Form_CustomData(array('form'=>$fmId,  'dir'=>$publisiteDir));
		$formsMobile[$fmId]->setTranslator($translator);
		$formsMobile[$fmId]->setView($view);
	}
	if(isset($_POST["formulaire_id"]) && isset($formsMobile[$_POST["formulaire_id"]])) {
		$ar[] = "submit";
		if($formsMobile[$_POST["formulaire_id"]]->isValid($_POST)){
			$ar[$_POST["formulaire_id"]] = "valid";
			$publisite->form($_POST,$formsMobile[$_POST["formulaire_id"]]);
		} else {
			$ar[$_POST["formulaire_id"]] = "invalid";
			$formsMobile[$_POST["formulaire_id"]]->populate($_POST);
		}
	}
	$view->assign('formsMobile', $formsMobile);
	$res = array_search("valid",$ar) ? array_search("valid",$ar):array_search("invalid",$ar);
	if($res!=false) {
		$o = new stdClass();
		$o->form = $formsMobile[$res]->render();
		$o->ar = $ar;
		$o->res = $ar[$res];
		$json = Zend_Json::encode($o);
		echo $json;
		exit;
	}
}
if($array["publisite"]["publisite_etat"]==0) {
    header("HTTP/1.0 404 Not Found");
	$view->assign('data', $array);
    echo $view->render( '404.phtml' );
} elseif($array["publisite"]["publisite_landing"]==1) {
	$array["content"] = $publisite->getLandingPage($defaultLang);
	$view->assign('data', $array);
	if(file_exists(ROOT_PATH.'/build/'.$publisiteDir.'/templates/customlanding.phtml')) {
		echo $view->render( 'customlanding.phtml' );
	} else {
		echo $view->render( 'landing.phtml' );
	}
	
} else {
   $ar = array(); 
   $arr = array();
		//mini form
		$ar[] = "form";
			$sidebarForm = $publisite->getSidebarForm();
			if($sidebarForm!=null) {
				$form = new Form_CustomData(array('form'=>$sidebarForm['formulaire_id'],  'dir'=>$publisiteDir));
				$form->setTranslator($translator);
				$titre = $formtitles[$sidebarForm['formulaire_id']];
				if(isset($_POST['submit']) && $sidebarForm['formulaire_id']==$_POST['formulaire_id']) {
					$arr[] = "submit";
					if($form->isValid($_POST)){
					$arr[] = "valid";
					$publisite->form($_POST,$form);
					} else {
					$arr[] = "invalid";
						$form->populate($_POST);
						Zend_Registry::get('logger')->info("Form Minisite invalid ");
						foreach($form->getErrors() as $error) {
							Zend_Registry::get('logger')->info('validation error '.join('######',$error));
						}
					}
				}
				$form->setView($view);
				$view->assign('titre', $titre);
				$view->assign('formsp', $form);
				$view->assign('arr', $arr);
			}
				//formulaire specifique
			$formspc = new Form_CustomData(array('form'=>1915,  'dir'=>$publisiteDir));
			$formspc->setTranslator($translate);
			if(isset($_POST['submit']) && ($_POST['formulaire_id']==1915)) {
				$arc[] = "submit";
				if($formspc->isValid($_POST)){
				$arc[] = "valid";
				$publisite->form($_POST,$formspc);
				} else {
				$arc[] = "invalid";
					$formspc->populate($_POST);
					Zend_Registry::get('logger')->info("Form Minisite invalid ");
					foreach($formspc->getErrors() as $error) {
						Zend_Registry::get('logger')->info('validation error '.join('######',$error));
					}
				}
			}
			$formspc->setView($view);
			$view->assign('formspc', $formspc);
			$view->assign('arc', $arc);
    include ROOT_PATH."/build/modules/default/route/Route.php";
switch($_action) {
	case 'intervenantparservice':
		$data = $publisite->getIntervenants($_GET);
		$view->assign('data', $data);
		echo $view->render( 'intervenants.phtml' );
		break;
    case 'contact':
        // contact page
		$array['css'] .= $defaultJSCSS->render("form-contact.phtml");
        $array['contact']= isset($_GET['sent'])? "<span style='color:green;'>Votre Message a &eacute;t&eacute; envoy&eacute;</span>":false;
		if($array['module']['module_avis']==1) {
			require_once(ROOT_PATH.'/build/modules/avis/home.php');
			$array['avisCount'] = $avisCount;
			$array['avisRate'] = $avisRate;
			$view->assign('data', $array);
			if(file_exists(ROOT_PATH.'/build/'.$publisiteDir.'/templates/avishomelocal.phtml')) {
				$content["avisClient"] = $view->render( 'avishomelocal.phtml' );
			} else {
				$view->addScriptPath(ROOT_PATH.'/build/modules/avis/');
				$content["avisClient"] = $view->render( 'avishome.phtml' );
			}
			$array = array_merge($array,$content);
		}
        $view->assign('data', $array);
        if ($detect->isMobile() && ($array['module']['module_mobile']==1)) {
	    // Any mobile device.
			echo $view->render( 'mobile.phtml' );
		} else {
			echo $view->render( 'contact.phtml' );
		}
        break;
    case 'reduction':
        // contact page
        if($array['reduction']!=null) {
            $view->assign('data', $array);
            echo $view->render( 'reduction.phtml' );
        } else {
            header("HTTP/1.0 404 Not Found");
            echo $view->render( '404.phtml' );
        }
        break;
    case 'view':
	// viewing the publisite homepage
		$array["actif"] = '';
		$array["homepage"] = true;
		if($array['module']['module_avis']==1) {
			require_once(ROOT_PATH.'/build/modules/avis/home.php');
			$array['avisCount'] = $avisCount;
			$array['avisRate'] = $avisRate;
			$view->assign('data', $array);
			if(file_exists(ROOT_PATH.'/build/'.$publisiteDir.'/templates/avishomelocal.phtml')) {
				$content["avisClient"] = $view->render( 'avishomelocal.phtml' );
			} else {
				$view->addScriptPath(ROOT_PATH.'/build/modules/avis/');
				$content["avisClient"] = $view->render( 'avishome.phtml' );
			}
			$array = array_merge($array,$content);
		}
        $array["content"] = $publisite->getHomePage($defaultLang);
        $view->assign('data', $array);
        if ($detect->isMobile() && ($array['module']['module_mobile']==1)) {
	    // Any mobile device.
			echo $view->render( 'mobile.phtml' );
		} else {
			echo $view->render( 'publisite.phtml' );
		}
        break;
	case 'sitemap.xml':
	// viewing the publisite homepage
        if(file_exists('sitemap.xml')) {
			print file_get_contents('sitemap.xml');
		} else {
			header("HTTP/1.0 404 Not Found");
            echo $view->render( '404.phtml' );
		}
        break;
    default:
		if($array['module']['module_avis']==1) {
			require_once(ROOT_PATH.'/build/modules/avis/home.php');
			$array['avisCount'] = $avisCount;
			$array['avisRate'] = $avisRate;
			$view->assign('data', $array);
			if(file_exists(ROOT_PATH.'/build/'.$publisiteDir.'/templates/avishomelocal.phtml')) {
				$content["avisClient"] = $view->render( 'avishomelocal.phtml' );
			} else {
				$view->addScriptPath(ROOT_PATH.'/build/modules/avis/');
				$content["avisClient"] = $view->render( 'avishome.phtml' );
			}
			$array = array_merge($array,$content);
		}
        // viewing the publisite services
        $formurls = $publisite->getFormsUrl();
        $formtitles = $publisite->getFormsTitle();
		$posturls = $publisite->getPostMenus();
		$caturls = $publisite->getCategorieMenus();
		$ar = array();
		if((int)preg_match("/^reduction\-([a-z0-9]+)$/", $_action, $matches) == 1) {
			$idRed = (int)$filterd->filter($tool->hex2bin(trim($matches[1])));
			if($idRed!=0) {
				$array['reduction'] = $publisite->getReduction($idRed);
				if($array['reduction']!=null) {
					$view->assign('data', $array);
					if(file_exists(ROOT_PATH.'/build/'.$publisiteDir.'/templates/reduction.phtml')) {
						echo $view->render( 'reduction.phtml' );
					} else {
						echo $view->render( 'mreduction.phtml' );
					}
				} else {
					header("HTTP/1.0 404 Not Found");
					echo $view->render( '404.phtml' );
				}
			} else {
				header("HTTP/1.0 404 Not Found");
				echo $view->render( '404.phtml' );
			}
		} elseif(in_array($_action, $formurls)) {
			$ar[] = "form";
			$kk = array_search ($_action, $formurls);
			$form = new Form_CustomData(array('form'=>$kk,  'dir'=>$publisiteDir));
			if(file_exists(PUBLISITE_DIR.'libs/fr/Zend_Validate.php')) {
				$form->setTranslator($translator);
			}
			$titre = $formtitles[$kk];
			if(isset($_POST['submit']) && $kk==$_POST['formulaire_id']) {
				$ar[] = "submit";
				if($form->isValid($_POST)){
				$ar[] = "valid";
				$publisite->form($_POST,$form);
				} else {
				$ar[] = "invalid";
					$form->populate($_POST);
					Zend_Registry::get('logger')->info("Form Minisite invalid ");
					foreach($form->getErrors() as $error) {
						Zend_Registry::get('logger')->info('validation error '.join('######',$error));
					}
				}
			}
			$form->setView($view);
			$view->assign('titre', $titre);
			$view->assign('form', $form);
			$view->assign('ar', $ar);
			$view->assign('data', $array);
			echo $view->render( 'form.phtml' );
		} elseif(in_array($_action, $posturls)) {
			$array["post"] = $publisite->getPostContentByUrl($_action);
			$view->assign('data', $array);
			if((int)$array["post"]["post"]["categorie_blog"]==1) {
				echo $view->render( 'blogpost.phtml' );
			} else {
				echo $view->render( 'post.phtml' );
			}
		} elseif(in_array($_action, $caturls)) {
			$page = (isset($_GET['page']) && is_numeric($_GET['page']))? $_GET['page']:1;
			$cc = array_search ($_action, $caturls);
			$array["categorie"] = $publisite->getCategorieInfo($cc);
			$array["blogposts"] = $publisite->getBlogCategoriePosts($page,$defaultLang);
			$array["page"] = $page;
			$view->assign('data', $array);
			if(file_exists(ROOT_PATH.'/build/'.$publisiteDir.'/templates/'.$_action.'.phtml')) {
					echo $view->render( $_action.'.phtml' );
				} else {
					echo $view->render( 'categorie.phtml' );
				}
		} else {
			if($array['module']['module_avis']==1  && $_action == $aviscontent['avis_content_url']) {
				require_once(ROOT_PATH.'/build/modules/avis/content.php');
				$array['avis'] = $paginator;
				$view->assign('data', $array);
				if(file_exists(ROOT_PATH.'/build/'.$publisiteDir.'/templates/avislocal.phtml')) {
					$content["content"]["page_contenu"] = $view->render( 'avislocal.phtml' );
					$array = array_merge($array,$content);
					$view->assign('data', $array);
					echo $view->render( 'publisite.phtml' );
				} else {
					$view->addScriptPath(ROOT_PATH.'/build/modules/avis/');
					$content["content"]["page_contenu"] = $view->render( 'avis.phtml' );
					$array = array_merge($array,$content);
					$view->assign('data', $array);
					echo $view->render( 'publisite.phtml' );
				}
			} else {
				$array["content"] = $publisite->getContentByUrl($_action);
				if($array["content"] == null) {
					header("HTTP/1.0 404 Not Found");
					require_once(ROOT_PATH.'/build/modules/default/message404.php');
					$content["content"]["page_contenu"] = $view->render( 'message404.phtml' );
					$array = array_merge($array,$content);
					$view->assign('data', $array);
					echo $view->render( 'publisite.phtml' );
				} else {
					if(($array['module']['module_multilingue']==1) && ($defaultLang!=$array["content"]["page_lang"])) {
						header("Location: /".substr($array["content"]["page_lang"],0,2));
					} else {
						$view->assign('data', $array);
						if(file_exists(ROOT_PATH.'/build/'.$publisiteDir.'/templates/'.$_action.'.phtml')) {
							echo $view->render( $_action.'.phtml' );
						} else {
							echo $view->render( 'publisite.phtml' );
						}
					}
				}
			}
		}
        break;
}
}
?>
